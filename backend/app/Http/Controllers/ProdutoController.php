<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\ItemLote;
use App\Helpers\AuditHelper;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'id_centro_custo' => ['nullable', 'regex:/^(sem|\d+)$/'],
        ]);

        // Aplica o filtro de centro de custo ('sem' = itens sem centro)
        $aplicarCentro = function ($q) use ($request) {
            if (!$request->filled('id_centro_custo')) return;
            $request->id_centro_custo === 'sem'
                ? $q->whereNull('id_centro_custo')
                : $q->where('id_centro_custo', (int) $request->id_centro_custo);
        };

        $query = Produto::query()
            ->withSum(['itensLote as itens_lote_sum_quantidade' => $aplicarCentro], 'quantidade')
            ->with([
                'categoria',
                'itensLote' => function ($q) use ($aplicarCentro) {
                    $aplicarCentro($q);
                    $q->with(['lote', 'centroCusto'])->orderBy('data_validade', 'asc');
                },
            ]);

        if ($request->filled('id_centro_custo')) {
            $query->whereHas('itensLote', $aplicarCentro);
        }

        if ($request->boolean('estoque_baixo')) {
            $query->havingRaw('COALESCE(itens_lote_sum_quantidade, 0) <= estoque_minimo');
        }

        if ($request->filled('busca')) {
            $query->where(function ($q) use ($request) {
                $q->where('nome', 'like', '%' . $request->busca . '%')
                  ->orWhere('sku', 'like', '%' . $request->busca . '%');
            });
        }

        $produtos = $query->get()->map(function ($produto) {
            $itensComValidade = $produto->itensLote->whereNotNull('data_validade')->sortBy('data_validade');

            $produto->quantidade     = $produto->itens_lote_sum_quantidade ?? 0;
            $produto->data_validade  = optional($itensComValidade->first())->data_validade;
            $produto->categoria_nome = optional($produto->categoria)->nome;
            $produto->lotes          = $produto->itensLote
                ->pluck('lote.numero_lote')
                ->filter()
                ->unique()
                ->values();

            $produto->centros_custo  = $produto->itensLote
                ->pluck('centroCusto.codigo')
                ->filter()
                ->unique()
                ->values();

            $produto->validades = $itensComValidade
                ->map(function ($item) {
                    return [
                        'id_item'         => $item->id_item,
                        'data_validade'   => $item->data_validade,
                        'quantidade'      => $item->quantidade,
                        'unidade'         => $item->unidade_medida,
                        'id_lote'         => $item->lote?->id_lote,
                        'numero_lote'     => optional($item->lote)->numero_lote,
                        'localizacao'     => $item->localizacao,
                        'id_centro_custo' => $item->id_centro_custo,
                        'centro_custo'    => $item->centroCusto?->codigo,
                    ];
                })
                ->values();

            // Status calculado com base em estoque_minimo + percentual_alerta do produto.
            // O front usa esse campo pronto ao invés de recalcular a mesma regra em 3 lugares.
            $produto->status_estoque = $produto->statusEstoque();

            return $produto;
        });

        return response()->json($produtos);
    }

    // Usado pelo ModalAdicionarItem pra detectar se o SKU digitado já pertence
    // a um produto existente (nesse caso, só adiciona um novo item de lote
    // pra ele, com sua própria quantidade/validade/localização).
    public function buscarPorSku(Request $request)
    {
        $request->validate([
            'sku' => 'required|string|max:50',
        ]);

        $produto = Produto::where('sku', $request->sku)->first();

        return response()->json($produto);
    }

    public function destroy(int $id)
    {
        $produto = Produto::findOrFail($id);

        AuditHelper::log('Exclusao', 'Produto "' . $produto->nome . '" (SKU: ' . $produto->sku . ') excluído.');

        $produto->delete();

        return response()->json(['message' => 'Produto excluído com sucesso.']);
    }

    public function destroyMultiplos(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:produto,id_produto',
        ], [
            'ids.required' => 'Selecione ao menos um produto para excluir.',
            'ids.*.exists' => 'Um dos produtos selecionados não existe.',
        ]);

        $produtos = Produto::whereIn('id_produto', $request->ids)->get();

        foreach ($produtos as $produto) {
            AuditHelper::log('Exclusao', 'Produto "' . $produto->nome . '" (SKU: ' . $produto->sku . ') excluído (exclusão em massa).');
        }

        Produto::whereIn('id_produto', $request->ids)->delete();

        return response()->json(['message' => count($produtos) . ' produto(s) excluído(s) com sucesso.']);
    }
}