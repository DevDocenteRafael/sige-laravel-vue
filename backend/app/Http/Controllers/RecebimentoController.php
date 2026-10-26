<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Jobs\RecalcularAbcJob;
use App\Models\ItemLote;
use App\Models\Lote;
use App\Models\Movimentacao;
use App\Models\Categoria;
use App\Models\FornecedorProduto;
use App\Models\NotaItem;
use App\Models\NotaRecebimento;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Models\UnidadeEquivalencia;
use App\Services\ChaveAcessoNfe;
use App\Services\DescricaoMatcher;
use App\Services\NfeXmlParser;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecebimentoController extends Controller
{
    // Equivalências de unidade confirmadas (cache da requisição): "UNIDADE_NOTA|UNIDADE_PEDIDO" => índice
    private ?array $equivalencias = null;

    public function index()
    {
        $notas = NotaRecebimento::withCount('itens')
            ->withCount(['itens as itens_ok' => fn ($q) => $q->where('status', 'ok')])
            ->orderByDesc('id_nota')
            ->get();

        return response()->json($notas);
    }

    public function show(int $id)
    {
        return response()->json($this->montar(NotaRecebimento::findOrFail($id)));
    }

    /**
     * Notas e pedidos que aguardam ação, com o motivo de cada pendência.
     * Quem resolve e o prazo ainda não foram definidos pelo responsável (ver documento do módulo),
     * por isso aqui só se informa o estado atual, sem prazo nem bloqueio automático.
     */
    public function pendentes()
    {
        $notas = NotaRecebimento::with(['itens', 'pedido'])
            ->whereIn('status', ['pendente', 'parcial'])
            ->orderBy('id_nota')
            ->get()
            ->map(function (NotaRecebimento $nota) {
                $motivos = [];

                if (!$nota->numero_pedido) {
                    $motivos[] = 'Nota sem número de pedido.';
                } elseif (!$nota->pedido) {
                    $motivos[] = "Pedido {$nota->numero_pedido} não encontrado.";
                } elseif ($bloqueio = $this->bloqueioEntrada($nota)) {
                    $motivos[] = $bloqueio;
                }

                $contagem = $nota->itens->groupBy('status')->map->count();
                if (($contagem['sem_vinculo'] ?? 0) > 0) {
                    $motivos[] = "{$contagem['sem_vinculo']} item(ns) sem vínculo com o pedido.";
                }
                if (($contagem['divergente'] ?? 0) > 0) {
                    $motivos[] = "{$contagem['divergente']} item(ns) com quantidade acima do saldo do pedido.";
                }
                if (($contagem['pendente'] ?? 0) > 0) {
                    $motivos[] = "{$contagem['pendente']} item(ns) ainda não conferido(s).";
                }

                return [
                    'id_nota'         => $nota->id_nota,
                    'numero'          => $nota->numero,
                    'fornecedor_nome' => $nota->fornecedor_nome,
                    'numero_pedido'   => $nota->numero_pedido,
                    'status'          => $nota->status,
                    'itens_total'     => $nota->itens->count(),
                    'itens_entraram'  => $contagem['entrou'] ?? 0,
                    'motivos'         => $motivos,
                    'criada_em'       => $nota->created_at?->toDateTimeString(),
                ];
            })
            // Nota sem motivo nenhum está só aguardando a conferência física: não é pendência de divergência
            ->values();

        $pedidos = Pedido::withCount('itens')
            ->whereIn('status', ['aberto', 'parcial'])
            ->orderBy('id_pedido')
            ->get(['id_pedido', 'numero', 'fornecedor_nome', 'status', 'data_emissao', 'valor_total']);

        return response()->json(['notas' => $notas, 'pedidos' => $pedidos]);
    }

    // Usado quando o conferente bipa o código de barras da nota (chave de 44 caracteres)
    public function porChave(string $chave)
    {
        $chave = ChaveAcessoNfe::normalizar($chave);

        if ($erro = ChaveAcessoNfe::validar($chave)) {
            return response()->json(['message' => $erro], 422);
        }

        $nota = NotaRecebimento::where('chave_acesso', $chave)->first();

        if (!$nota) {
            return response()->json([
                'message'       => 'Esta nota ainda não foi importada. Envie o XML dela primeiro.',
                'nao_importada' => true,
            ], 404);
        }

        return response()->json($this->montar($nota));
    }

    public function importarXml(Request $request)
    {
        $request->validate([
            'arquivo'        => 'required|file|max:5120',
            'numero_pedido'  => 'nullable|string|max:30',
            'chave_esperada' => 'nullable|string|max:60',
        ], [
            'arquivo.required' => 'Envie o XML da nota fiscal.',
            'arquivo.max'      => 'O XML não pode passar de 5 MB.',
        ]);

        $conteudo = file_get_contents($request->file('arquivo')->getRealPath());

        return $this->registrarNota(
            $conteudo,
            $request->input('numero_pedido'),
            $request->input('chave_esperada')
        );
    }

    // Miolo da importação, reutilizável (upload manual hoje; fonte externa de XML depois)
    private function registrarNota(string $conteudo, ?string $numeroPedidoInformado, ?string $chaveEsperada): JsonResponse
    {
        try {
            $dados = app(NfeXmlParser::class)->parse($conteudo);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Importação iniciada a partir de uma chave bipada: o XML precisa ser dessa mesma nota
        $esperada = ChaveAcessoNfe::normalizar($chaveEsperada);
        if ($esperada !== '' && ChaveAcessoNfe::normalizar($dados['chave']) !== $esperada) {
            return response()->json([
                'message' => "O XML enviado é da chave {$dados['chave']}, diferente da chave bipada ({$esperada}). Nada foi importado.",
            ], 422);
        }

        $existente = NotaRecebimento::where('chave_acesso', $dados['chave'])->first();
        if ($existente) {
            return response()->json([
                'message' => "A nota {$dados['numero']} já foi importada.",
                'id_nota' => $existente->id_nota,
            ], 422);
        }

        $numeroPedido = $numeroPedidoInformado ?: $dados['numero_pedido'];
        $pedido       = $numeroPedido ? $this->buscarPedido($numeroPedido) : null;

        $caminho = 'notas/' . Str::uuid() . '.xml';
        Storage::put($caminho, $conteudo);

        try {
            $nota = DB::transaction(function () use ($dados, $pedido, $numeroPedido, $caminho) {
                $nota = NotaRecebimento::create([
                    'chave_acesso'    => $dados['chave'],
                    'numero'          => $dados['numero'],
                    'serie'           => $dados['serie'],
                    'data_emissao'    => $dados['data_emissao'],
                    'fornecedor_cnpj' => $dados['fornecedor_cnpj'],
                    'fornecedor_nome' => $dados['fornecedor_nome'],
                    'id_pedido'       => $pedido?->id_pedido,
                    'numero_pedido'   => $numeroPedido,
                    'valor_total'     => $dados['valor_total'],
                    'status'          => 'pendente',
                    'xml_path'        => $caminho,
                    'id_usuario'      => Auth::id(),
                ]);

                foreach ($dados['itens'] as $item) {
                    NotaItem::create([
                        'id_nota'           => $nota->id_nota,
                        'codigo_fornecedor' => $item['codigo'],
                        'descricao'         => $item['descricao'],
                        'unidade'           => $item['unidade'],
                        'quantidade'        => $item['quantidade'],
                        'valor_unitario'    => $item['valor_unitario'],
                        'status'            => 'pendente',
                        'lote_fornecedor'   => $item['lote'],
                        'data_validade'     => $item['validade'],
                    ]);
                }

                $this->conferir($nota);

                AuditHelper::log(
                    'Criacao',
                    "Nota fiscal {$dados['numero']} importada (" . count($dados['itens']) . ' itens, pedido ' . ($numeroPedido ?: 'não informado') . ').'
                );

                return $nota;
            });
        } catch (\Throwable $e) {
            Storage::delete($caminho);   // não deixa XML órfão no disco

            // Corrida: outra requisição cadastrou a mesma chave entre a checagem e o create
            if ($e instanceof QueryException) {
                $existente = NotaRecebimento::where('chave_acesso', $dados['chave'])->first();
                if ($existente) {
                    return response()->json([
                        'message' => "A nota {$dados['numero']} já foi importada.",
                        'id_nota' => $existente->id_nota,
                    ], 422);
                }
            }

            throw $e;
        }

        // Os avisos (pedido ausente, CNPJ divergente...) vêm do montar(), que os recalcula sempre
        return response()->json($this->montar($nota), 201);
    }

    // O operador diz qual item do pedido corresponde ao item da nota (de-para do fornecedor)
    public function vincular(Request $request, int $idNota, int $idNotaItem)
    {
        $request->validate([
            'id_pedido_item' => 'required|integer|exists:pedido_item,id_pedido_item',
        ], [
            'id_pedido_item.required' => 'Selecione o item do pedido.',
            'id_pedido_item.exists'   => 'O item do pedido selecionado não existe.',
        ]);

        $nota = NotaRecebimento::findOrFail($idNota);
        $item = NotaItem::where('id_nota', $idNota)->findOrFail($idNotaItem);

        if ($item->status === 'entrou') {
            return response()->json(['message' => 'Este item já deu entrada no estoque.'], 422);
        }

        $pedidoItem = PedidoItem::findOrFail($request->id_pedido_item);

        if (!$nota->id_pedido || $pedidoItem->id_pedido !== $nota->id_pedido) {
            return response()->json(['message' => 'Este item não pertence ao pedido desta nota.'], 422);
        }

        DB::transaction(function () use ($nota, $item, $pedidoItem) {
            $produtoCriado = false;

            if (!$pedidoItem->id_produto) {
                $produto = Produto::where('sku', $pedidoItem->sku)->first();

                if (!$produto) {
                    $produto       = $this->criarProduto($pedidoItem, $nota);
                    $produtoCriado = true;
                }

                $pedidoItem->update(['id_produto' => $produto->id_produto]);
            }

            // Mapeamento que existia antes (para a auditoria mostrar se o vínculo substituiu outro)
            $idProdutoAnterior = FornecedorProduto::where('fornecedor_cnpj', $nota->fornecedor_cnpj)
                ->where('codigo_fornecedor', $item->codigo_fornecedor)
                ->value('id_produto');

            FornecedorProduto::updateOrCreate(
                [
                    'fornecedor_cnpj'   => $nota->fornecedor_cnpj,
                    'codigo_fornecedor' => $item->codigo_fornecedor,
                ],
                [
                    'descricao_fornecedor' => $item->descricao,
                    'id_produto'           => $pedidoItem->id_produto,
                ]
            );

            $this->conferir($nota);

            $detalhe = "NF {$nota->numero}: item \"{$item->descricao}\" (cód. fornecedor {$item->codigo_fornecedor}) "
                . "vinculado ao item do pedido {$nota->numero_pedido}, SKU {$pedidoItem->sku} \"{$pedidoItem->descricao}\" "
                . "[id_nota_item {$item->id_nota_item}, id_pedido_item {$pedidoItem->id_pedido_item}, id_produto {$pedidoItem->id_produto}].";

            if ($produtoCriado) {
                $detalhe .= ' Produto criado a partir do pedido.';
            }
            if ($idProdutoAnterior && (int) $idProdutoAnterior !== (int) $pedidoItem->id_produto) {
                $detalhe .= " Substituiu o mapeamento anterior (id_produto {$idProdutoAnterior}).";
            }

            AuditHelper::log('Vinculo', $detalhe);
        });

        return response()->json($this->montar($nota->refresh()));
    }

    // Um responsável confirma que a unidade da nota equivale (1:1) à do pedido. Vale para as próximas notas.
    public function confirmarEquivalenciaUnidade(int $idNota, int $idNotaItem)
    {
        $nota = NotaRecebimento::findOrFail($idNota);
        $item = NotaItem::with('pedidoItem')->where('id_nota', $idNota)->findOrFail($idNotaItem);

        if (!$item->pedidoItem) {
            return response()->json(['message' => 'Vincule o item ao pedido antes de confirmar a equivalência de unidade.'], 422);
        }

        $a = mb_strtoupper(trim((string) $item->unidade));
        $b = mb_strtoupper(trim((string) $item->pedidoItem->unidade));

        if ($a === '' || $b === '' || $a === $b) {
            return response()->json(['message' => 'As unidades deste item já são iguais.'], 422);
        }

        UnidadeEquivalencia::firstOrCreate(
            ['unidade_nota' => $a, 'unidade_pedido' => $b],
            ['id_usuario'   => Auth::id()]
        );
        $this->equivalencias = null;   // recarrega na próxima consulta desta requisição

        AuditHelper::log(
            'Equivalencia de Unidade',
            "NF {$nota->numero}: unidade da nota \"{$a}\" confirmada como equivalente a \"{$b}\" do pedido (1:1) "
            . "[id_nota_item {$item->id_nota_item}]. Vale para as próximas notas."
        );

        return response()->json($this->montar($nota->refresh()));
    }

    // Dá entrada no estoque dos itens 'ok' selecionados (entrega parcial: só o que chegou)
    public function darEntrada(Request $request, int $id)
    {
        $request->validate([
            'itens'                 => 'required|array|min:1',
            'itens.*.id_nota_item'  => 'required|integer',
            'itens.*.localizacao'   => 'nullable|string|max:100',
            'itens.*.data_validade' => 'nullable|date|before:2100-01-01',
            'itens.*.numero_lote'   => 'nullable|string|max:50',
            'itens.*.prioridade_abc' => 'nullable|in:A,B,C',
        ], [
            'itens.required' => 'Selecione ao menos um item para dar entrada.',
            'itens.array'    => 'Selecione ao menos um item para dar entrada.',
            'itens.min'      => 'Selecione ao menos um item para dar entrada.',
        ]);

        $nota = NotaRecebimento::findOrFail($id);

        // Fornecedor da nota diferente do pedido (ou nota sem pedido): nada entra
        $bloqueio = $this->bloqueioEntrada($nota);
        if ($bloqueio) {
            return response()->json(['message' => $bloqueio], 422);
        }

        $extras    = collect($request->input('itens'))->keyBy('id_nota_item');
        $ignorados = [];
        $entraram  = 0;

        DB::transaction(function () use ($nota, $extras, &$ignorados, &$entraram) {
            $encontrados = NotaItem::where('id_nota', $nota->id_nota)
                ->whereIn('id_nota_item', $extras->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id_nota_item');

            // Só passam os itens 'ok'; os demais são informados, nunca descartados em silêncio
            $itens = collect();
            foreach ($extras->keys() as $idItem) {
                $item = $encontrados->get($idItem);

                if (!$item) {
                    $ignorados[] = ['id_nota_item' => $idItem, 'motivo' => 'Item não pertence a esta nota.'];
                } elseif ($item->status === 'entrou') {
                    $ignorados[] = ['id_nota_item' => $idItem, 'motivo' => 'Este item já deu entrada no estoque.'];
                } elseif ($item->status !== 'ok') {
                    $ignorados[] = ['id_nota_item' => $idItem, 'motivo' => "Item não está pronto para entrada (situação: {$item->status})."];
                } else {
                    $itens->push($item);
                }
            }

            if ($itens->isEmpty()) {
                return;
            }

            $lotes = [];   // lotes já resolvidos nesta entrada, por número

            foreach ($itens as $item) {
                $qtd = (float) $item->quantidade;

                if ($qtd <= 0 || fmod($qtd, 1.0) !== 0.0) {
                    $ignorados[] = ['id_nota_item' => $item->id_nota_item, 'motivo' => 'Quantidade fracionada ou zerada: o estoque só aceita inteiros.'];
                    continue;
                }

                $pedidoItem = PedidoItem::lockForUpdate()->find($item->id_pedido_item);
                if (!$pedidoItem || !$item->id_produto) {
                    $ignorados[] = ['id_nota_item' => $item->id_nota_item, 'motivo' => 'Item sem vínculo com o pedido/produto.'];
                    continue;
                }

                // Sem equivalência confirmada entre unidades, o item não entra (nada é convertido por suposição)
                if ($this->unidadesIncompativeis($item->unidade, $pedidoItem->unidade)) {
                    $ignorados[] = [
                        'id_nota_item' => $item->id_nota_item,
                        'motivo'       => "Unidade da nota ({$item->unidade}) diferente da unidade do pedido ({$pedidoItem->unidade}). Confirme a equivalência na linha do item para liberar a entrada.",
                    ];
                    continue;
                }

                $saldo = (float) $pedidoItem->quantidade_pedida - (float) $pedidoItem->quantidade_recebida;
                if ($qtd > $saldo + 0.0001) {
                    $item->update(['status' => 'divergente']);
                    $ignorados[] = ['id_nota_item' => $item->id_nota_item, 'motivo' => 'Quantidade maior que o saldo do pedido.'];
                    continue;
                }

                // O lote só é criado quando existe pelo menos um item válido.
                // Se o conferente informou o lote do item, usa ele; senão, um lote padrão por nota.
                $extra      = $extras->get($item->id_nota_item, []);
                $numeroLote = trim((string) ($extra['numero_lote'] ?? '')) ?: "NF{$nota->numero}-{$nota->id_nota}";
                $lote       = $lotes[$numeroLote] ??= Lote::firstOrCreate(
                    ['numero_lote' => $numeroLote],
                    [
                        'data_entrada' => now()->toDateString(),
                        'descricao'    => "Recebimento NF {$nota->numero} - {$nota->fornecedor_nome}",
                        'status'       => 'ATIVO',
                    ]
                );

                // Prioridade informada vira manual; sem ela, o cálculo ABC automático continua valendo
                $prioridade = ($extra['prioridade_abc'] ?? null) ?: null;
                $produto = Produto::findOrFail($item->id_produto);
                $qtdInt  = (int) $qtd;

                // Validade ausente continua nula: não inventamos data
                $itemLote = ItemLote::create([
                    'id_lote'           => $lote->id_lote,
                    'id_produto'        => $produto->id_produto,
                    'quantidade'        => $qtdInt,
                    'unidade_medida'    => $produto->unidade_medida ?: 'UN',
                    'data_validade'     => ($extra['data_validade'] ?? null) ?: $item->data_validade,
                    'localizacao'       => $extra['localizacao'] ?? null,
                    'prioridade_abc'    => $prioridade,
                    'prioridade_manual' => $prioridade !== null,
                ]);

                Produto::whereKey($produto->id_produto)->increment('estoque_atual', $qtdInt);

                Movimentacao::registrar(
                    'ENTRADA', $qtdInt, $lote->id_lote, $itemLote->id_item,
                    "NF {$nota->numero} / pedido {$nota->numero_pedido}"
                );

                $pedidoItem->increment('quantidade_recebida', $qtdInt);
                $item->update(['status' => 'entrou', 'quantidade_entrada' => $qtdInt]);

                AuditHelper::log(
                    'Entrada de Estoque',
                    "Recebimento NF {$nota->numero}: {$qtdInt} un. de \"{$produto->nome}\" no lote {$lote->numero_lote}."
                );
                $entraram++;
            }

            // Status da nota
            $total  = NotaItem::where('id_nota', $nota->id_nota)->count();
            $entrou = NotaItem::where('id_nota', $nota->id_nota)->where('status', 'entrou')->count();
            $nota->update(['status' => $entrou === $total ? 'conferida' : ($entrou > 0 ? 'parcial' : $nota->status)]);

            // Status do pedido
            if ($nota->id_pedido) {
                $pedido   = Pedido::find($nota->id_pedido);
                $faltando = PedidoItem::where('id_pedido', $nota->id_pedido)
                    ->whereColumn('quantidade_recebida', '<', 'quantidade_pedida')->exists();
                $pedido?->update(['status' => $faltando ? 'parcial' : 'concluido']);
            }
        });

        if ($entraram > 0) {
            RecalcularAbcJob::dispatch();
        }

        $resposta = $this->montar($nota->refresh());
        $resposta['entrada'] = ['entraram' => $entraram, 'ignorados' => $ignorados];

        return response()->json($resposta);
    }

    public function destroy(int $id)
    {
        $nota = NotaRecebimento::with('itens')->findOrFail($id);

        if ($nota->itens->where('status', 'entrou')->isNotEmpty()) {
            return response()->json([
                'message' => 'Esta nota já tem itens com entrada no estoque e não pode ser excluída.',
            ], 422);
        }

        DB::transaction(function () use ($nota) {
            AuditHelper::log('Exclusao', "Nota fiscal {$nota->numero} excluída.");
            if ($nota->xml_path) {
                Storage::delete($nota->xml_path);
            }
            $nota->delete();
        });

        return response()->json(['message' => 'Nota excluída com sucesso.']);
    }

    // ─── Auxiliares ─────────────────────────────────────────────

    private function soDigitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    private function equivalenciaConfirmada(string $a, string $b): bool
    {
        $this->equivalencias ??= UnidadeEquivalencia::get()
            ->map(fn ($e) => $e->unidade_nota . '|' . $e->unidade_pedido)
            ->flip()
            ->all();

        return isset($this->equivalencias["{$a}|{$b}"]);
    }

    /**
     * Incompatível só quando as duas unidades são conhecidas, diferentes
     * e ninguém confirmou a equivalência entre elas (tabela unidade_equivalencia).
     * A comparação ignora apenas maiúsculas/minúsculas e espaços.
     */
    private function unidadesIncompativeis(?string $unidadeNota, ?string $unidadePedido): bool
    {
        $a = mb_strtoupper(trim((string) $unidadeNota));
        $b = mb_strtoupper(trim((string) $unidadePedido));

        if ($a === '' || $b === '' || $a === $b) {
            return false;
        }

        return !$this->equivalenciaConfirmada($a, $b);
    }

    /**
     * Motivo pelo qual a nota inteira não pode dar entrada (null = liberada).
     * A consulta da nota continua permitida; só a entrada é bloqueada.
     */
    private function bloqueioEntrada(NotaRecebimento $nota): ?string
    {
        $pedido = $nota->pedido;

        if (!$pedido) {
            return 'Esta nota não está vinculada a nenhum pedido. Exclua a nota e importe o XML de novo informando o pedido correto.';
        }

        $cnpjPedido = $this->soDigitos($pedido->fornecedor_cnpj);

        if ($cnpjPedido !== '' && $cnpjPedido !== $this->soDigitos($nota->fornecedor_cnpj)) {
            return "O CNPJ do fornecedor da nota ({$nota->fornecedor_cnpj}) é diferente do CNPJ do pedido ({$pedido->fornecedor_cnpj}). A entrada fica bloqueada até a divergência ser resolvida.";
        }

        return null;
    }

    // Avisos recalculados a cada leitura da nota, para não sumirem depois da importação
    private function avisos(NotaRecebimento $nota): array
    {
        $avisos = [];

        if (!$nota->numero_pedido) {
            $avisos[] = 'Não encontrei o número do pedido na nota. Informe o número do pedido para conferir os itens.';
        } elseif (!$nota->pedido) {
            $avisos[] = "O pedido {$nota->numero_pedido} não foi encontrado. Importe o PDF do pedido antes de conferir a nota.";
        } else {
            $cnpjPedido = $this->soDigitos($nota->pedido->fornecedor_cnpj);

            if ($cnpjPedido === '') {
                $avisos[] = 'O pedido não tem CNPJ de fornecedor cadastrado, então não foi possível conferir o fornecedor da nota.';
            } elseif ($cnpjPedido !== $this->soDigitos($nota->fornecedor_cnpj)) {
                $avisos[] = 'O CNPJ do fornecedor da nota é diferente do CNPJ do pedido. A entrada está bloqueada até a divergência ser resolvida.';
            }
        }

        return $avisos;
    }

    /**
     * Cruza cada item da nota com o pedido:
     *  - sem_vinculo : não sabemos ainda qual item do pedido é (ou não está no pedido)
     *  - ok          : a quantidade cabe no saldo do pedido (entrega parcial é aceita)
     *  - divergente  : a quantidade passa do que ainda falta receber do pedido
     */
    private function conferir(NotaRecebimento $nota): void
    {
        $nota->load('itens');

        $mapa = FornecedorProduto::where('fornecedor_cnpj', $nota->fornecedor_cnpj)
            ->pluck('id_produto', 'codigo_fornecedor');

        $itensPedido = $nota->id_pedido
            ? PedidoItem::where('id_pedido', $nota->id_pedido)->get()
            : collect();

        $reservado = [];

        foreach ($nota->itens as $item) {
            if ($item->status === 'entrou') {
                continue;
            }

            $idProduto  = $mapa[$item->codigo_fornecedor] ?? null;
            $pedidoItem = $idProduto ? $itensPedido->firstWhere('id_produto', $idProduto) : null;

            if (!$pedidoItem) {
                $item->update([
                    'id_produto'     => $idProduto,
                    'id_pedido_item' => null,
                    'status'         => 'sem_vinculo',
                ]);
                continue;
            }

            $saldo = (float) $pedidoItem->quantidade_pedida - (float) $pedidoItem->quantidade_recebida;
            $total = ($reservado[$pedidoItem->id_pedido_item] ?? 0) + (float) $item->quantidade;
            $reservado[$pedidoItem->id_pedido_item] = $total;

                        $item->update([
                'id_produto'     => $idProduto,
                'id_pedido_item' => $pedidoItem->id_pedido_item,
                'status'         => $total <= $saldo + 0.0001 ? 'ok' : 'divergente',
            ]);
        }
    }

    // Compara o que a chave de 44 dígitos informa com o que o XML trouxe
    private function comparacaoChave(NotaRecebimento $nota): array
    {
        $c = $nota->chave_acesso;

        $linhas = [
            ['campo' => 'Número da NF',      'chave' => (string) (int) substr($c, 25, 9), 'xml' => (string) (int) $nota->numero],
            ['campo' => 'Série',             'chave' => (string) (int) substr($c, 22, 3), 'xml' => (string) (int) $nota->serie],
            ['campo' => 'Emissão (mês/ano)', 'chave' => substr($c, 4, 2) . '/20' . substr($c, 2, 2), 'xml' => $nota->data_emissao?->format('m/Y')],
            ['campo' => 'CNPJ do emitente',  'chave' => substr($c, 6, 14), 'xml' => $this->soDigitos($nota->fornecedor_cnpj)],
        ];

        return array_map(fn ($l) => $l + ['confere' => $l['chave'] === $l['xml']], $linhas);
    }

    private function montar(NotaRecebimento $nota): array
    {
        $nota->load(['itens.pedidoItem', 'pedido.itens']);

        $matcher     = app(DescricaoMatcher::class);
        $itensPedido = $nota->pedido ? $nota->pedido->itens : collect();
        $usados      = $nota->itens->pluck('id_pedido_item')->filter()->all();
        $livres      = $itensPedido->reject(fn ($p) => in_array($p->id_pedido_item, $usados));

        $paraArray = function (PedidoItem $p) {
            return [
                'id_pedido_item'      => $p->id_pedido_item,
                'sku'                 => $p->sku,
                'descricao'           => $p->descricao,
                'unidade'             => $p->unidade,
                'quantidade_pedida'   => (float) $p->quantidade_pedida,
                'quantidade_recebida' => (float) $p->quantidade_recebida,
                'saldo'               => (float) $p->quantidade_pedida - (float) $p->quantidade_recebida,
                'valor_unitario'      => (float) $p->valor_unitario,
            ];
        };

        $itens = $nota->itens->map(function (NotaItem $i) use ($matcher, $livres, $itensPedido, $paraArray) {
            $sugestoes = [];

            if ($i->status === 'sem_vinculo') {
                $candidatos = $livres->map(fn ($p) => ['id' => $p->id_pedido_item, 'descricao' => $p->descricao])
                    ->values()->all();

                foreach ($matcher->sugerir($i->descricao, $candidatos, 5) as $s) {
                    $p = $itensPedido->firstWhere('id_pedido_item', $s['id']);
                    if ($p) {
                        $sugestoes[] = $paraArray($p) + ['pontuacao' => $s['pontuacao']];
                    }
                }
            }

            // Valor unitário da nota diferente do pedido: só aviso (o vínculo pode estar errado)
            $valorDivergente = $i->pedidoItem
                && $i->status !== 'entrou'
                && abs((float) $i->valor_unitario - (float) $i->pedidoItem->valor_unitario) > 0.005;

            return [
                'id_nota_item'         => $i->id_nota_item,
                'codigo_fornecedor'    => $i->codigo_fornecedor,
                'descricao'            => $i->descricao,
                'unidade'              => $i->unidade,
                'quantidade'           => (float) $i->quantidade,
                'valor_unitario'       => (float) $i->valor_unitario,
                'status'               => $i->status,
                'quantidade_entrada'   => (float) $i->quantidade_entrada,
                'pedido_item'          => $i->pedidoItem ? $paraArray($i->pedidoItem) : null,
                // Item vinculado cuja unidade não bate com a do pedido (e sem equivalência confirmada): a entrada dele é bloqueada
                'unidade_incompativel' => $i->pedidoItem
                    && $i->status !== 'entrou'
                    && $this->unidadesIncompativeis($i->unidade, $i->pedidoItem->unidade),
                'valor_divergente'     => (bool) $valorDivergente,
                'sugestoes'            => $sugestoes,
            ];
        })->values();

        return [
            'nota' => [
                'id_nota'         => $nota->id_nota,
                'chave_acesso'    => $nota->chave_acesso,
                'numero'          => $nota->numero,
                'serie'           => $nota->serie,
                'data_emissao'    => $nota->data_emissao?->toDateString(),
                'fornecedor_nome' => $nota->fornecedor_nome,
                'fornecedor_cnpj' => $nota->fornecedor_cnpj,
                'numero_pedido'   => $nota->numero_pedido,
                'valor_total'     => (float) $nota->valor_total,
                'status'          => $nota->status,
            ],
            'pedido' => $nota->pedido ? [
                'id_pedido' => $nota->pedido->id_pedido,
                'numero'    => $nota->pedido->numero,
                'status'    => $nota->pedido->status,
            ] : null,
            'resumo' => [
                'total'                => $itens->count(),
                'ok'                   => $itens->where('status', 'ok')->count(),
                'divergente'           => $itens->where('status', 'divergente')->count(),
                'sem_vinculo'          => $itens->where('status', 'sem_vinculo')->count(),
                'entrou'               => $itens->where('status', 'entrou')->count(),
                'unidade_incompativel' => $itens->where('unidade_incompativel', true)->count(),
                'valor_divergente'     => $itens->where('valor_divergente', true)->count(),
            ],
                       'avisos'           => $this->avisos($nota),
            'comparacao_chave' => $this->comparacaoChave($nota),
            'bloqueio_entrada' => $this->bloqueioEntrada($nota),
            'itens'            => $itens,
        ];
    }

    private function buscarPedido(string $numero): ?Pedido
    {
        $pedido = Pedido::where('numero', $numero)->first();

        // "036072" e "36072" são o mesmo pedido
        if (!$pedido && ctype_digit($numero)) {
            $pedido = Pedido::whereRaw('CAST(numero AS UNSIGNED) = ?', [(int) $numero])->first();
        }

        return $pedido;
    }

    private function criarProduto(PedidoItem $pedidoItem, NotaRecebimento $nota): Produto
    {
        $categoria = Categoria::firstOrCreate(['nome' => 'Sem categoria']);

        return Produto::create([
            'sku'               => $pedidoItem->sku,
            'nome'              => $pedidoItem->descricao,
            'unidade_medida'    => $pedidoItem->unidade ?: 'UN',
            'preco_custo'       => $pedidoItem->valor_unitario,
            'estoque_minimo'    => 1,
            'percentual_alerta' => 20,
            'estoque_atual'     => 0,
            'id_categoria'      => $categoria->id_categoria,
            'id_fornecedor'     => $nota->pedido?->id_fornecedor,
        ]);
    }
}