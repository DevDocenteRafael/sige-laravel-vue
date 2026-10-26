<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\Fornecedor;
use App\Models\NotaRecebimento;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Services\PedidoPdfParser;
use App\Services\PedidoPlanilha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::withCount('itens')
            ->withSum('itens as qtd_pedida', 'quantidade_pedida')
            ->withSum('itens as qtd_recebida', 'quantidade_recebida')
            ->orderByDesc('id_pedido')
            ->get();

        return response()->json($pedidos);
    }

    public function show(int $id)
    {
        $pedido = Pedido::with(['itens.produto:id_produto,nome,sku'])->findOrFail($id);

        $pedido->itens->each(function ($item) {
            $item->saldo = $item->quantidade_pedida - $item->quantidade_recebida;
        });

        return response()->json($pedido);
    }

    public function importar(Request $request, PedidoPdfParser $parser)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:pdf|max:10240',
        ], [
            'arquivo.required' => 'Envie o PDF do pedido.',
            'arquivo.mimes'    => 'O arquivo precisa ser um PDF.',
            'arquivo.max'      => 'O PDF não pode passar de 10 MB.',
        ]);

        try {
            $texto = (new \Smalot\PdfParser\Parser())
                ->parseFile($request->file('arquivo')->getRealPath())
                ->getText();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Não foi possível ler o PDF enviado.'], 422);
        }

        $dados = $parser->parse($texto);

        if (!$dados['numero'] || empty($dados['itens'])) {
            return response()->json([
                'message' => 'Não encontrei o número do pedido ou os itens neste PDF. Confira se é o PDF do pedido de compra do MXM.',
            ], 422);
        }

        if (Pedido::where('numero', $dados['numero'])->exists()) {
            return response()->json([
                'message' => "O pedido {$dados['numero']} já foi importado.",
            ], 422);
        }

        $skus     = array_column($dados['itens'], 'sku');
        $produtos = Produto::whereIn('sku', $skus)->pluck('id_produto', 'sku');

        $idFornecedor = $dados['fornecedor_nome']
            ? Fornecedor::where('nome', $dados['fornecedor_nome'])->value('id_fornecedor')
            : null;

        $pedido = DB::transaction(function () use ($dados, $produtos, $idFornecedor) {
            $pedido = Pedido::create([
                'numero'          => $dados['numero'],
                'id_fornecedor'   => $idFornecedor,
                'fornecedor_nome' => $dados['fornecedor_nome'],
                'fornecedor_cnpj' => $dados['fornecedor_cnpj'],
                'data_emissao'    => $dados['data_emissao'],
                'valor_total'     => $dados['valor_total'],
                'status'          => 'aberto',
                'id_usuario'      => Auth::id(),
            ]);

            foreach ($dados['itens'] as $item) {
                PedidoItem::create([
                    'id_pedido'         => $pedido->id_pedido,
                    'id_produto'        => $produtos[$item['sku']] ?? null,
                    'sku'               => $item['sku'],
                    'descricao'         => $item['descricao'],
                    'unidade'           => $item['unidade'],
                    'quantidade_pedida' => $item['quantidade'],
                    'valor_unitario'    => $item['valor_unitario'],
                ]);
            }

            AuditHelper::log(
                'Criacao',
                "Pedido {$dados['numero']} importado do PDF ({$pedido->itens()->count()} itens)."
            );

            return $pedido;
        });

        $semCadastro = collect($dados['itens'])
            ->filter(fn ($i) => !isset($produtos[$i['sku']]))
            ->map(fn ($i) => ['sku' => $i['sku'], 'descricao' => $i['descricao']])
            ->values();

        return response()->json([
            'id_pedido'         => $pedido->id_pedido,
            'numero'            => $pedido->numero,
            'total_itens'       => count($dados['itens']),
            'avisos'            => $dados['avisos'],
            'produtos_sem_cadastro' => $semCadastro,
        ], 201);
    }

    public function destroy(int $id)
    {
        $pedido = Pedido::with('itens')->findOrFail($id);

        if ($pedido->itens->where('quantidade_recebida', '>', 0)->isNotEmpty()) {
            return response()->json([
                'message' => 'Este pedido já tem itens recebidos e não pode ser excluído.',
            ], 422);
        }

        DB::transaction(function () use ($pedido) {
            AuditHelper::log('Exclusao', "Pedido {$pedido->numero} excluído.");
            $pedido->delete();
        });

        return response()->json(['message' => 'Pedido excluído com sucesso.']);
    }

    // ─── Criação manual, edição e planilha ───────────────────────

    // Menu "Novo pedido": cadastro direto no SIGE
    public function store(Request $request)
    {
        $this->normalizarCnpj($request);

        $dados = $request->validate([
            'numero'                    => 'required|string|max:30|unique:pedido,numero',
            'fornecedor_nome'           => 'nullable|string|max:150',
            'fornecedor_cnpj'           => 'nullable|digits:14',
            'data_emissao'              => 'nullable|date',
            'observacao'                => 'nullable|string|max:2000',
            'itens'                     => 'required|array|min:1|max:500',
            'itens.*.sku'               => 'required|string|max:30|distinct',
            'itens.*.descricao'         => 'required|string|max:255',
            'itens.*.unidade'           => 'nullable|string|max:10',
            'itens.*.quantidade_pedida' => 'required|numeric|gt:0|max:99999999',
            'itens.*.valor_unitario'    => 'nullable|numeric|min:0|max:99999999',
        ], $this->mensagens());

        $pedido = $this->gravar([
            'numero'          => trim($dados['numero']),
            'fornecedor_nome' => $dados['fornecedor_nome'] ?? null,
            'fornecedor_cnpj' => $dados['fornecedor_cnpj'] ?? null,
            'data_emissao'    => $dados['data_emissao'] ?? null,
            'observacao'      => $dados['observacao'] ?? null,
            'itens'           => array_map(fn ($i) => [
                'sku'            => trim($i['sku']),
                'descricao'      => trim($i['descricao']),
                'unidade'        => isset($i['unidade']) && $i['unidade'] !== '' ? mb_strtoupper($i['unidade']) : null,
                'quantidade'     => (float) $i['quantidade_pedida'],
                'valor_unitario' => (float) ($i['valor_unitario'] ?? 0),
            ], $dados['itens']),
        ], 'cadastrado manualmente');

        return response()->json($this->detalhe($pedido->id_pedido), 201);
    }

    // Cabeçalho do pedido (só enquanto ele pode ser editado)
    public function update(Request $request, int $id)
    {
        $this->normalizarCnpj($request);

        $dados = $request->validate([
            'numero'          => "required|string|max:30|unique:pedido,numero,{$id},id_pedido",
            'fornecedor_nome' => 'nullable|string|max:150',
            'fornecedor_cnpj' => 'nullable|digits:14',
            'data_emissao'    => 'nullable|date',
            'observacao'      => 'nullable|string|max:2000',
        ], $this->mensagens());

        DB::transaction(function () use ($id, $dados) {
            $pedido = Pedido::lockForUpdate()->findOrFail($id);
            $this->exigirEditavel($pedido);

            $pedido->update([
                'numero'          => trim($dados['numero']),
                'fornecedor_nome' => $dados['fornecedor_nome'] ?? null,
                'fornecedor_cnpj' => $dados['fornecedor_cnpj'] ?? null,
                'data_emissao'    => $dados['data_emissao'] ?? null,
                'observacao'      => $dados['observacao'] ?? null,
                'id_fornecedor'   => !empty($dados['fornecedor_nome'])
                    ? Fornecedor::where('nome', $dados['fornecedor_nome'])->value('id_fornecedor')
                    : null,
            ]);

            AuditHelper::log('Edicao', "Pedido {$pedido->numero} editado.");
        });

        return response()->json($this->detalhe($id));
    }

    public function adicionarItem(Request $request, int $id)
    {
        $dados = $request->validate($this->regrasItem(), $this->mensagens());

        DB::transaction(function () use ($id, $dados) {
            $pedido = Pedido::lockForUpdate()->findOrFail($id);
            $this->exigirEditavel($pedido);

            $sku = trim($dados['sku']);
            if (PedidoItem::where('id_pedido', $id)->where('sku', $sku)->exists()) {
                throw ValidationException::withMessages(['sku' => "O código {$sku} já está neste pedido."]);
            }

            PedidoItem::create([
                'id_pedido'         => $id,
                'id_produto'        => Produto::where('sku', $sku)->value('id_produto'),
                'sku'               => $sku,
                'descricao'         => trim($dados['descricao']),
                'unidade'           => !empty($dados['unidade']) ? mb_strtoupper($dados['unidade']) : null,
                'quantidade_pedida' => $dados['quantidade_pedida'],
                'valor_unitario'    => $dados['valor_unitario'] ?? 0,
            ]);

            $this->recalcularTotal($pedido);
            AuditHelper::log('Edicao', "Pedido {$pedido->numero}: item {$sku} adicionado.");
        });

        return response()->json($this->detalhe($id), 201);
    }

    public function atualizarItem(Request $request, int $id, int $idItem)
    {
        $dados = $request->validate($this->regrasItem(), $this->mensagens());

        DB::transaction(function () use ($id, $idItem, $dados) {
            $pedido = Pedido::lockForUpdate()->findOrFail($id);
            $this->exigirEditavel($pedido);

            $item = PedidoItem::where('id_pedido', $id)->findOrFail($idItem);
            $sku  = trim($dados['sku']);

            if (PedidoItem::where('id_pedido', $id)->where('sku', $sku)->where('id_pedido_item', '!=', $idItem)->exists()) {
                throw ValidationException::withMessages(['sku' => "O código {$sku} já está neste pedido."]);
            }

            $item->update([
                'id_produto'        => Produto::where('sku', $sku)->value('id_produto'),
                'sku'               => $sku,
                'descricao'         => trim($dados['descricao']),
                'unidade'           => !empty($dados['unidade']) ? mb_strtoupper($dados['unidade']) : null,
                'quantidade_pedida' => $dados['quantidade_pedida'],
                'valor_unitario'    => $dados['valor_unitario'] ?? 0,
            ]);

            $this->recalcularTotal($pedido);
            AuditHelper::log('Edicao', "Pedido {$pedido->numero}: item {$sku} alterado.");
        });

        return response()->json($this->detalhe($id));
    }

    public function removerItem(int $id, int $idItem)
    {
        DB::transaction(function () use ($id, $idItem) {
            $pedido = Pedido::lockForUpdate()->findOrFail($id);
            $this->exigirEditavel($pedido);

            if ($pedido->itens()->count() <= 1) {
                throw ValidationException::withMessages([
                    'item' => 'O pedido precisa ter ao menos um item. Para remover tudo, exclua o pedido.',
                ]);
            }

            $item = PedidoItem::where('id_pedido', $id)->findOrFail($idItem);
            $item->delete();

            $this->recalcularTotal($pedido);
            AuditHelper::log('Edicao', "Pedido {$pedido->numero}: item {$item->sku} removido.");
        });

        return response()->json($this->detalhe($id));
    }

    /**
     * Importa pedidos de planilha. Com "previa=1" só valida e mostra o que seria criado.
     * Se houver qualquer erro, nada é gravado.
     */
    public function importarPlanilha(Request $request, PedidoPlanilha $planilha)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
            'previa'  => 'nullable|boolean',
        ], [
            'arquivo.required' => 'Envie a planilha do pedido.',
            'arquivo.mimes'    => 'A planilha precisa ser .xlsx, .xls ou .csv.',
            'arquivo.max'      => 'A planilha não pode passar de 5 MB.',
        ]);

        $lido  = $planilha->ler($request->file('arquivo')->getRealPath());
        $erros = $lido['erros'];
        $dados = $lido['dados'];

        if (empty($erros) && Pedido::where('numero', $dados['numero'])->exists()) {
            $erros[] = "O pedido {$dados['numero']} já foi importado.";
        }

        $semCadastro = [];
        if (!empty($dados['itens'])) {
            $existentes  = Produto::whereIn('sku', array_column($dados['itens'], 'sku'))->pluck('id_produto', 'sku');
            $semCadastro = collect($dados['itens'])
                ->reject(fn ($i) => isset($existentes[$i['sku']]))
                ->map(fn ($i) => ['sku' => $i['sku'], 'descricao' => $i['descricao']])
                ->values()->all();
        }

        if (!empty($erros)) {
            return response()->json(['message' => 'A planilha tem erros. Nada foi importado.', 'erros' => $erros], 422);
        }

        if ($request->boolean('previa')) {
            return response()->json([
                'previa'                => true,
                'numero'                => $dados['numero'],
                'fornecedor_nome'       => $dados['fornecedor_nome'],
                'total_itens'           => count($dados['itens']),
                'valor_total'           => round(array_sum(array_map(fn ($i) => $i['quantidade'] * $i['valor_unitario'], $dados['itens'])), 2),
                'produtos_sem_cadastro' => $semCadastro,
            ]);
        }

        $pedido = $this->gravar($dados, 'importado de planilha');

        return response()->json([
            'id_pedido'             => $pedido->id_pedido,
            'numero'                => $pedido->numero,
            'total_itens'           => count($dados['itens']),
            'produtos_sem_cadastro' => $semCadastro,
        ], 201);
    }

    // Planilha de um pedido (ou de todos, quando $id é null) no layout configurado em config/pedido_mxm.php
    public function exportar(PedidoPlanilha $planilha, ?int $id = null)
    {
        $pedidos = $id
            ? Pedido::with('itens')->where('id_pedido', $id)->get()
            : Pedido::with('itens')->orderBy('id_pedido')->get();

        if ($id && $pedidos->isEmpty()) {
            abort(404, 'Pedido não encontrado.');
        }

        $arquivo = $id ? "pedido-{$pedidos->first()->numero}.xlsx" : 'pedidos.xlsx';
        $arquivo = preg_replace('/[^A-Za-z0-9._-]/', '_', $arquivo);

        AuditHelper::log('Criacao', $id ? "Pedido {$pedidos->first()->numero} exportado para planilha." : 'Pedidos exportados para planilha.');

        return response()->streamDownload(function () use ($planilha, $pedidos) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($planilha->exportar($pedidos)))->save('php://output');
        }, $arquivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ─── Auxiliares ─────────────────────────────────────────────

    /**
     * Regra de edição (PENDENTE DE APROVAÇÃO do responsável): o pedido só pode ser alterado
     * enquanto está "aberto", sem item recebido e sem nota fiscal vinculada.
     */
    private function exigirEditavel(Pedido $pedido): void
    {
        $motivo = null;

        if ($pedido->status !== 'aberto') {
            $motivo = "O pedido está {$pedido->status} e não pode mais ser editado.";
        } elseif ($pedido->itens()->where('quantidade_recebida', '>', 0)->exists()) {
            $motivo = 'Este pedido já tem itens recebidos e não pode mais ser editado.';
        } elseif (NotaRecebimento::where('id_pedido', $pedido->id_pedido)->exists()) {
            $motivo = 'Há uma nota fiscal vinculada a este pedido. Exclua a nota antes de editar o pedido.';
        }

        if ($motivo) {
            throw ValidationException::withMessages(['pedido' => $motivo]);
        }
    }

    private function recalcularTotal(Pedido $pedido): void
    {
        $total = PedidoItem::where('id_pedido', $pedido->id_pedido)
            ->selectRaw('COALESCE(SUM(quantidade_pedida * valor_unitario), 0) as total')
            ->value('total');

        $pedido->update(['valor_total' => round((float) $total, 2)]);
    }

    // Grava pedido + itens numa transação (usado pelo cadastro manual e pela planilha)
    private function gravar(array $dados, string $origem): Pedido
    {
        $produtos = Produto::whereIn('sku', array_column($dados['itens'], 'sku'))->pluck('id_produto', 'sku');

        $idFornecedor = !empty($dados['fornecedor_nome'])
            ? Fornecedor::where('nome', $dados['fornecedor_nome'])->value('id_fornecedor')
            : null;

        return DB::transaction(function () use ($dados, $produtos, $idFornecedor, $origem) {
            $pedido = Pedido::create([
                'numero'          => $dados['numero'],
                'id_fornecedor'   => $idFornecedor,
                'fornecedor_nome' => $dados['fornecedor_nome'] ?? null,
                'fornecedor_cnpj' => $dados['fornecedor_cnpj'] ?? null,
                'data_emissao'    => $dados['data_emissao'] ?? null,
                'valor_total'     => round(array_sum(array_map(fn ($i) => $i['quantidade'] * $i['valor_unitario'], $dados['itens'])), 2),
                'status'          => 'aberto',
                'observacao'      => $dados['observacao'] ?? null,
                'id_usuario'      => Auth::id(),
            ]);

            foreach ($dados['itens'] as $item) {
                PedidoItem::create([
                    'id_pedido'         => $pedido->id_pedido,
                    'id_produto'        => $produtos[$item['sku']] ?? null,
                    'sku'               => $item['sku'],
                    'descricao'         => $item['descricao'],
                    'unidade'           => $item['unidade'],
                    'quantidade_pedida' => $item['quantidade'],
                    'valor_unitario'    => $item['valor_unitario'],
                ]);
            }

            AuditHelper::log('Criacao', "Pedido {$pedido->numero} {$origem} (" . count($dados['itens']) . ' itens).');

            return $pedido;
        });
    }

    private function detalhe(int $id): array
    {
        $pedido = Pedido::with(['itens.produto:id_produto,nome,sku'])->findOrFail($id);
        $pedido->itens->each(fn ($i) => $i->saldo = $i->quantidade_pedida - $i->quantidade_recebida);

        $arr = $pedido->toArray();
        $arr['editavel'] = $pedido->status === 'aberto'
            && $pedido->itens->where('quantidade_recebida', '>', 0)->isEmpty()
            && !NotaRecebimento::where('id_pedido', $pedido->id_pedido)->exists();

        return $arr;
    }

    private function normalizarCnpj(Request $request): void
    {
        if ($request->has('fornecedor_cnpj')) {
            $request->merge(['fornecedor_cnpj' => preg_replace('/\D/', '', (string) $request->input('fornecedor_cnpj')) ?: null]);
        }
    }

    private function regrasItem(): array
    {
        return [
            'sku'               => 'required|string|max:30',
            'descricao'         => 'required|string|max:255',
            'unidade'           => 'nullable|string|max:10',
            'quantidade_pedida' => 'required|numeric|gt:0|max:99999999',
            'valor_unitario'    => 'nullable|numeric|min:0|max:99999999',
        ];
    }

    private function mensagens(): array
    {
        return [
            'numero.required'                    => 'Informe o número do pedido.',
            'numero.unique'                      => 'Já existe um pedido com este número.',
            'fornecedor_cnpj.digits'             => 'O CNPJ precisa ter 14 dígitos.',
            'itens.required'                     => 'Adicione ao menos um item ao pedido.',
            'itens.min'                          => 'Adicione ao menos um item ao pedido.',
            'itens.*.sku.required'               => 'Todo item precisa de código.',
            'itens.*.sku.distinct'               => 'Há códigos repetidos nos itens do pedido.',
            'itens.*.descricao.required'         => 'Todo item precisa de descrição.',
            'itens.*.quantidade_pedida.required' => 'Informe a quantidade de cada item.',
            'itens.*.quantidade_pedida.gt'       => 'A quantidade de cada item precisa ser maior que zero.',
            'itens.*.quantidade_pedida.numeric'  => 'A quantidade precisa ser um número.',
            'sku.required'                       => 'Informe o código do item.',
            'descricao.required'                 => 'Informe a descrição do item.',
            'quantidade_pedida.required'         => 'Informe a quantidade.',
            'quantidade_pedida.gt'               => 'A quantidade precisa ser maior que zero.',
            'quantidade_pedida.numeric'          => 'A quantidade precisa ser um número.',
        ];
    }
}
