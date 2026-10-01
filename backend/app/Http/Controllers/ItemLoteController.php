<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Fornecedor;
use App\Models\Movimentacao;
use App\Helpers\AuditHelper;
use App\Jobs\RecalcularAbcJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemLoteController extends Controller
{
    // Maior inteiro que o PHP consegue representar com precisão (64-bit).
    // Acima disso o BIGINT UNSIGNED do MySQL ainda aguenta, mas o PHP
    // já viraria float e perderia precisão — então este é o teto seguro.
    private const QUANTIDADE_MAX = 9223372036854775807;

    public function store(Request $request, int $idLote)
    {
        if ($request->filled('id_produto')) {
            $request->request->remove('nome');
            $request->request->remove('sku');
            $request->request->remove('categoria');
            $request->request->remove('estoque_minimo');
            $request->request->remove('percentual_alerta');
        }

        $request->validate([
            'id_produto'        => 'nullable|integer|exists:produto,id_produto',
            'nome'              => 'required_without:id_produto|string|min:2|max:255',
            'sku'               => 'required_without:id_produto|string|max:50',
            'quantidade'        => 'required|integer|min:1|max:' . self::QUANTIDADE_MAX,
            'categoria'         => 'required_without:id_produto|string',
            'data_validade'     => 'nullable|date|after:today|before:2100-01-01',
            'estoque_minimo'    => 'required_without:id_produto|integer|min:1',
            'percentual_alerta' => 'nullable|integer|min:1|max:100',
            'id_centro_custo'   => 'nullable|integer|exists:centro_custo,id_centro_custo',
        ], [
            'nome.required_without'           => 'Informe o produto (id_produto) ou os dados de um produto novo.',
            'categoria.required_without'      => 'A categoria é obrigatória ao cadastrar um produto novo.',
            'quantidade.required'             => 'A quantidade é obrigatória.',
            'quantidade.integer'              => 'A quantidade deve ser um número inteiro.',
            'quantidade.min'                  => 'A quantidade não pode ser negativa.',
            'quantidade.max'                  => 'A quantidade informada é grande demais.',
            'data_validade.date'              => 'Informe uma data válida.',
            'data_validade.after'             => 'A data de validade deve ser futura.',
            'data_validade.before'            => 'A data de validade informada é inválida.',
            'estoque_minimo.required_without' => 'O estoque mínimo é obrigatório ao cadastrar um produto novo.',
            'estoque_minimo.integer'          => 'O estoque mínimo deve ser um número inteiro.',
            'estoque_minimo.min'              => 'O estoque mínimo deve ser pelo menos 1.',
            'percentual_alerta.integer'       => 'A porcentagem de alerta deve ser um número inteiro.',
            'percentual_alerta.min'           => 'A porcentagem de alerta deve ser pelo menos 1%.',
            'percentual_alerta.max'           => 'A porcentagem de alerta não pode ser maior que 100%.',
        ]);

        try {
            $item = DB::transaction(function () use ($request, $idLote) {
                $produtoNovo = false;

                if ($request->id_produto) {
                    $idProduto = $request->id_produto;
                    $nomeProduto = Produto::whereKey($idProduto)->value('nome');
                } else {
                    $existente = Produto::where('sku', $request->sku)->first();

                    if ($existente) {
                        throw new \RuntimeException(json_encode([
                            'message'              => "O SKU \"{$request->sku}\" já está cadastrado para o produto \"{$existente->nome}\". Selecione-o na lista em vez de criar um novo.",
                            'id_produto_existente' => $existente->id_produto,
                        ]));
                    }

                    $dadosProduto = [
                        'sku'               => $request->sku,
                        'nome'              => $request->nome,
                        'unidade_medida'    => $request->unidade_medida ?? 'UN',
                        'estoque_minimo'    => $request->estoque_minimo,
                        'percentual_alerta' => $request->percentual_alerta ?? 20,
                        'estoque_atual'     => 0,
                    ];

                    if ($request->filled('categoria')) {
                        $categoria = Categoria::firstOrCreate(
                            ['nome' => trim($request->categoria)]
                        );
                        $dadosProduto['id_categoria'] = $categoria->id_categoria;
                    }

                    if ($request->filled('fornecedor')) {
                        $fornecedor = Fornecedor::firstOrCreate(
                            ['nome' => trim($request->fornecedor)]
                        );
                        $dadosProduto['id_fornecedor'] = $fornecedor->id_fornecedor;
                    }

                    try {
                        $produto = Produto::create($dadosProduto);
                    } catch (\Illuminate\Database\QueryException $e) {
                        $existente = Produto::where('sku', $request->sku)->first();

                        if ($existente) {
                            throw new \RuntimeException(json_encode([
                                'message'              => "O SKU \"{$request->sku}\" já está cadastrado para o produto \"{$existente->nome}\". Selecione-o na lista em vez de criar um novo.",
                                'id_produto_existente' => $existente->id_produto,
                            ]));
                        }

                        throw $e;
                    }

                    $idProduto   = $produto->id_produto;
                    $nomeProduto = $produto->nome;
                    $produtoNovo = true;
                }

                $ehManual = $request->filled('prioridade_abc');

                $item = ItemLote::create([
                    'id_lote'           => $idLote,
                    'id_produto'        => $idProduto,
                    'quantidade'        => $request->quantidade,
                    'unidade_medida'    => $request->unidade_medida ?? 'UN',
                    'data_validade'     => $request->data_validade ?: null,
                    'localizacao'       => $request->localizacao,
                    'id_centro_custo'   => $request->id_centro_custo ?: null,
                    'prioridade_abc'    => $ehManual ? $request->prioridade_abc : null,
                    'prioridade_manual' => $ehManual,
                ]);

                Produto::whereKey($idProduto)->increment('estoque_atual', $request->quantidade);

                $numeroLote = \App\Models\Lote::whereKey($idLote)->value('numero_lote') ?? $idLote;

                if ($produtoNovo) {
                    AuditHelper::log(
                        'Criacao',
                        "Produto \"{$nomeProduto}\" cadastrado e adicionado ao lote {$numeroLote} (qtd: {$request->quantidade})."
                    );
                } else {
                    AuditHelper::log(
                        'Criacao',
                        "Produto \"{$nomeProduto}\" adicionado ao lote {$numeroLote} (qtd: {$request->quantidade})."
                    );
                }

                return $item;
            });
        } catch (\RuntimeException $e) {
             \Illuminate\Support\Facades\Log::error('ERRO AO ADICIONAR ITEM: ' . $e->getMessage(), [
                'classe' => get_class($e),
                'arquivo' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json(json_decode($e->getMessage(), true), 422);
        }

        RecalcularAbcJob::dispatch();

        return response()->json($item->refresh()->load(['produto.fornecedor', 'centroCusto']), 201);
    }

    public function index(int $idLote)
    {
        $itens = ItemLote::with(['produto.fornecedor', 'centroCusto'])
            ->where('id_lote', $idLote)
            ->orderBy('ordem')
            ->get();
        return response()->json($itens);
    }

    public function update(Request $request, int $id)
    {
        $item = ItemLote::with('produto')->findOrFail($id);

        $request->validate([
            'quantidade'     => 'required|integer|min:0|max:' . self::QUANTIDADE_MAX,
            'data_validade'  => 'nullable|date|before:2100-01-01',
            'id_centro_custo' => 'nullable|integer|exists:centro_custo,id_centro_custo',
        ], [
            'quantidade.required'  => 'A quantidade é obrigatória.',
            'quantidade.integer'   => 'A quantidade deve ser um número inteiro.',
            'quantidade.min'       => 'A quantidade não pode ser negativa.',
            'quantidade.max'       => 'A quantidade informada é grande demais.',
            'data_validade.date'   => 'Informe uma data válida.',
            'data_validade.before' => 'A data de validade informada é inválida.',
        ]);

        DB::transaction(function () use ($request, $item) {
            $qtdAntiga = $item->quantidade;
            $ehManual  = $request->filled('prioridade_abc');

             $dados = $request->only(['quantidade', 'unidade_medida', 'data_validade', 'localizacao', 'id_centro_custo']);
            $dados['prioridade_manual'] = $ehManual;
            $dados['prioridade_abc']    = $ehManual ? $request->prioridade_abc : null;

            $item->update($dados);

            $diferenca = $item->quantidade - $qtdAntiga;
            if ($diferenca !== 0) {
                Produto::whereKey($item->id_produto)->increment('estoque_atual', $diferenca);
            }

            AuditHelper::log(
                'Edicao',
                "Item do produto \"{$item->produto?->nome}\" editado (qtd: {$qtdAntiga} → {$item->quantidade})."
            );
        });

        RecalcularAbcJob::dispatch();

        return response()->json($item->refresh()->load(['produto.fornecedor', 'centroCusto']));
    }

    public function baixa(Request $request, int $id)
    {
        $item = ItemLote::with('produto')->findOrFail($id);

        $request->validate([
            'quantidade' => 'required|integer|min:1|max:' . $item->quantidade,
            'motivo'     => 'nullable|string|max:255',
        ], [
            'quantidade.required' => 'A quantidade é obrigatória.',
            'quantidade.integer'  => 'A quantidade deve ser um número inteiro.',
            'quantidade.min'      => 'A quantidade deve ser pelo menos 1.',
            'quantidade.max'      => 'A quantidade não pode ser maior que o estoque disponível.',
            'motivo.max'          => 'O motivo não pode ter mais de 255 caracteres.',
        ]);

        DB::transaction(function () use ($request, $item) {
            $item->update(['quantidade' => $item->quantidade - $request->quantidade]);
            Produto::whereKey($item->id_produto)->decrement('estoque_atual', $request->quantidade);

            Movimentacao::registrar('SAIDA', $request->quantidade, $item->id_lote, $item->id_item, $request->motivo);

            $descricao = "Baixa de {$request->quantidade} unidade(s) do produto \"{$item->produto?->nome}\".";
            if ($request->filled('motivo')) {
                $descricao .= " Motivo: {$request->motivo}";
            }
            AuditHelper::log('Saída de Estoque', $descricao);
        });

        RecalcularAbcJob::dispatch();

        return response()->json($item->refresh());
    }

    public function entrada(Request $request, int $id)
    {
        $request->validate([
            'quantidade' => 'required|integer|min:1|max:' . self::QUANTIDADE_MAX,
            'motivo'     => 'nullable|string|max:255',
        ], [
            'quantidade.required' => 'A quantidade é obrigatória.',
            'quantidade.integer'  => 'A quantidade deve ser um número inteiro.',
            'quantidade.min'      => 'A quantidade deve ser pelo menos 1.',
            'quantidade.max'      => 'A quantidade informada é grande demais.',
            'motivo.max'          => 'O motivo não pode ter mais de 255 caracteres.',
        ]);

        $item = ItemLote::with('produto')->findOrFail($id);

        DB::transaction(function () use ($request, $item) {
            $item->quantidade += $request->quantidade;
            $item->save();

            Produto::whereKey($item->id_produto)->increment('estoque_atual', $request->quantidade);

            Movimentacao::registrar('ENTRADA', $request->quantidade, $item->id_lote, $item->id_item, $request->motivo);

            $descricao = "Entrada de {$request->quantidade} unidade(s) do produto \"{$item->produto?->nome}\".";
            if ($request->filled('motivo')) {
                $descricao .= " Motivo: {$request->motivo}";
            }
            AuditHelper::log('Entrada de Estoque', $descricao);
        });

        RecalcularAbcJob::dispatch();

        return response()->json($item->refresh());
    }

    public function reordenar(Request $request, int $idLote)
    {
        $request->validate([
            'itens'           => 'required|array',
            'itens.*.id_item' => 'required|integer|exists:item_lote,id_item',
            'itens.*.ordem'   => 'required|integer|min:0',
        ], [
            'itens.required'           => 'A lista de itens é obrigatória.',
            'itens.array'              => 'A lista de itens está em formato inválido.',
            'itens.*.id_item.required' => 'O ID do item é obrigatório.',
            'itens.*.id_item.exists'   => 'Um dos itens informados não existe.',
            'itens.*.ordem.required'   => 'A ordem é obrigatória.',
            'itens.*.ordem.min'        => 'A ordem não pode ser negativa.',
        ]);

        $idsDoLote = ItemLote::where('id_lote', $idLote)->pluck('id_item')->toArray();

        foreach ($request->itens as $item) {
            if (!in_array($item['id_item'], $idsDoLote)) {
                return response()->json(['message' => 'Item não pertence a este lote.'], 422);
            }
        }

        DB::transaction(function () use ($request) {
            foreach ($request->itens as $item) {
                ItemLote::where('id_item', $item['id_item'])->update(['ordem' => $item['ordem']]);
            }
        });

        return response()->json(['message' => 'Ordem atualizada com sucesso.']);
    }

    public function destroy(int $id)
    {
        DB::transaction(function () use ($id) {
            $item = ItemLote::with('produto')->findOrFail($id);
            $idProduto = $item->id_produto;
            $nomeProduto = $item->produto?->nome;
            $numeroLote = \App\Models\Lote::whereKey($item->id_lote)->value('numero_lote') ?? $item->id_lote;

            Produto::whereKey($idProduto)->decrement('estoque_atual', $item->quantidade);

            AuditHelper::log(
                'Exclusao',
                "Item do produto \"{$nomeProduto}\" (qtd: {$item->quantidade}) excluído do lote {$numeroLote}."
            );

            $item->delete();

            // Se o produto ficou sem nenhum lote, remove o cadastro também.
            if (!ItemLote::where('id_produto', $idProduto)->exists()) {
                Produto::whereKey($idProduto)->delete();
            }
        });

        RecalcularAbcJob::dispatch();

        return response()->json(['message' => 'Item excluído com sucesso.']);
    }

    public function destroyMultiplos(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:item_lote,id_item',
        ], [
            'ids.required' => 'Selecione ao menos um item para excluir.',
            'ids.*.exists' => 'Um dos itens selecionados não existe.',
        ]);

        $itens = ItemLote::with('produto')->whereIn('id_item', $request->ids)->get();
        $idsProdutosAfetados = $itens->pluck('id_produto')->unique();

        DB::transaction(function () use ($itens, $idsProdutosAfetados) {
            foreach ($itens as $item) {
                Produto::whereKey($item->id_produto)->decrement('estoque_atual', $item->quantidade);

                $numeroLote = \App\Models\Lote::whereKey($item->id_lote)->value('numero_lote') ?? $item->id_lote;
                AuditHelper::log(
                    'Exclusao',
                    "Item do produto \"{$item->produto?->nome}\" (qtd: {$item->quantidade}) excluído do lote {$numeroLote} (exclusão em massa)."
                );
            }
            ItemLote::whereIn('id_item', $itens->pluck('id_item'))->delete();

            // Remove produtos que ficaram sem nenhum lote.
            foreach ($idsProdutosAfetados as $idProduto) {
                if (!ItemLote::where('id_produto', $idProduto)->exists()) {
                    Produto::whereKey($idProduto)->delete();
                }
            }
        });

        RecalcularAbcJob::dispatch();

        return response()->json(['message' => count($itens) . ' item(ns) excluído(s) com sucesso.']);
    }

    public function historico(int $id)
    {
        $item = ItemLote::findOrFail($id);
        return response()->json(
            Movimentacao::where('id_item', $item->id_item)
                ->orderBy('data_movimentacao', 'desc')
                ->get()
        );
    }

    public function transferir(Request $request, int $id)
    {
        $itemOrigem = ItemLote::findOrFail($id);

        $request->validate([
            'id_lote_destino' => 'required|integer|exists:lote,id_lote|different:' . $itemOrigem->id_lote,
            'quantidade'       => 'required|integer|min:1|max:' . $itemOrigem->quantidade,
        ], [
            'id_lote_destino.required'   => 'Selecione o lote de destino.',
            'id_lote_destino.different'  => 'O lote de destino deve ser diferente do lote atual.',
            'quantidade.required'        => 'A quantidade é obrigatória.',
            'quantidade.max'             => 'A quantidade não pode ser maior que a disponível no item.',
        ]);

        if (!$itemOrigem->data_validade) {
            return response()->json([
                'message' => 'Este item não possui data de validade cadastrada. Atualize a validade do item antes de transferi-lo.'
            ], 422);
        }

        $resultado = DB::transaction(function () use ($itemOrigem, $request) {
            return $this->executarTransferencia($itemOrigem, $request->id_lote_destino, $request->quantidade);
        });

        return response()->json($resultado);
    }

    public function transferirEmLote(Request $request)
    {
        $request->validate([
            'transferencias'                       => 'required|array|min:1',
            'transferencias.*.id_item'              => 'required|integer|exists:item_lote,id_item',
            'transferencias.*.id_lote_destino'      => 'required|integer|exists:lote,id_lote',
            'transferencias.*.quantidade'           => 'required|integer|min:1',
        ], [
            'transferencias.required'                  => 'Selecione ao menos um item para transferir.',
            'transferencias.*.id_item.exists'           => 'Um dos itens selecionados não existe.',
            'transferencias.*.id_lote_destino.exists'   => 'Um dos lotes de destino não existe.',
        ]);

        $resultados = [];

        try {
            DB::transaction(function () use ($request, &$resultados) {
                foreach ($request->transferencias as $transferencia) {
                    $itemOrigem    = ItemLote::with('produto')->findOrFail($transferencia['id_item']);
                    $qtd           = (int) $transferencia['quantidade'];
                    $idLoteDestino = (int) $transferencia['id_lote_destino'];
                    $nomeProduto   = $itemOrigem->produto->nome ?? "item #{$itemOrigem->id_item}";

                    if ($idLoteDestino === $itemOrigem->id_lote) {
                        throw new \RuntimeException("\"{$nomeProduto}\" já está no lote de destino selecionado.");
                    }

                    if ($qtd > $itemOrigem->quantidade) {
                        throw new \RuntimeException("Quantidade informada para \"{$nomeProduto}\" é maior que o disponível ({$itemOrigem->quantidade}).");
                    }

                    if (!$itemOrigem->data_validade) {
                        throw new \RuntimeException("\"{$nomeProduto}\" não possui data de validade cadastrada.");
                    }

                    $resultados[] = $this->executarTransferencia($itemOrigem, $idLoteDestino, $qtd);
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'    => count($resultados) . ' transferência(s) realizada(s) com sucesso.',
            'resultados' => $resultados,
        ]);
    }

    private function executarTransferencia(ItemLote $itemOrigem, int $idLoteDestino, int $qtd): array
    {
        $nomeProduto = $itemOrigem->produto?->nome ?? $itemOrigem->id_produto;

        $itemOrigem->decrement('quantidade', $qtd);

        $itemDestino = ItemLote::where('id_lote', $idLoteDestino)
            ->where('id_produto', $itemOrigem->id_produto)
            ->first();

        if ($itemDestino) {
            $itemDestino->increment('quantidade', $qtd);
            if (!$itemDestino->id_centro_custo && $itemOrigem->id_centro_custo) {
                $itemDestino->update(['id_centro_custo' => $itemOrigem->id_centro_custo]);
            }

            if (!$itemDestino->data_validade ||
                ($itemOrigem->data_validade && $itemOrigem->data_validade->lt($itemDestino->data_validade))) {
                $itemDestino->update(['data_validade' => $itemOrigem->data_validade]);
            }
        } else {
            $itemDestino = ItemLote::create([
                'id_lote'        => $idLoteDestino,
                'id_produto'     => $itemOrigem->id_produto,
                'quantidade'     => $qtd,
                'unidade_medida' => $itemOrigem->unidade_medida,
                'data_validade'  => $itemOrigem->data_validade,
                'localizacao'    => $itemOrigem->localizacao,
                'id_centro_custo' => $itemOrigem->id_centro_custo,
            ]);
        }

        $numeroLoteOrigem  = \App\Models\Lote::find($itemOrigem->id_lote)?->numero_lote ?? $itemOrigem->id_lote;
        $numeroLoteDestino = \App\Models\Lote::find($idLoteDestino)?->numero_lote ?? $idLoteDestino;

        Movimentacao::registrar('TRANSFERENCIA', $qtd, $itemOrigem->id_lote, $itemOrigem->id_item,
            "Transferido para lote {$numeroLoteDestino}");
        Movimentacao::registrar('TRANSFERENCIA', $qtd, $itemDestino->id_lote, $itemDestino->id_item,
            "Recebido do lote {$numeroLoteOrigem}");

        AuditHelper::log(
            'Transferência',
            "Transferido {$qtd} unidade(s) do produto \"{$nomeProduto}\" do lote {$numeroLoteOrigem} para o lote {$numeroLoteDestino}."
        );

        RecalcularAbcJob::dispatch();

        return [
            'item_origem'  => $itemOrigem->refresh()->load(['produto.fornecedor', 'centroCusto']),
           'item_destino' => $itemDestino->refresh()->load(['produto.fornecedor', 'centroCusto']),
        ];
    }

    public function todos()
    {
       $itens = ItemLote::with(['produto', 'lote', 'centroCusto'])
            ->where('quantidade', '>', 0)
            ->orderBy('data_validade', 'asc')
            ->get();

        return response()->json($itens);
    }
}