<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use App\Models\ItemLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerdaController extends Controller
{
    public function index()
{
    $perdas = Movimentacao::with('item.produto')
        ->where('tipo', 'PERDA')
        ->orderBy('data_movimentacao', 'desc')
        ->get();

    return response()->json($perdas);
}
    public function store(Request $request)
    {
        $request->validate([
            'id_item'    => 'required|integer|exists:item_lote,id_item',
            'quantidade' => 'required|integer|min:1',
            'motivo'     => 'required|string|max:255',
        ]);

        $item = ItemLote::findOrFail($request->id_item);

        if ($request->quantidade > $item->quantidade) {
            return response()->json([
                'message' => 'Quantidade maior que o estoque disponível.',
            ], 422);
        }

        $item->update([
            'quantidade' => $item->quantidade - $request->quantidade,
        ]);

        $perda = Movimentacao::registrar(
            'PERDA',
            $request->quantidade,
            $item->id_lote,
            $item->id_item,
            $request->motivo
        );

        return response()->json($perda->load('item'), 201);
    }

    public function storeVarios(Request $request)
    {
        $request->validate([
            'motivo'              => 'required|string|max:255',
            'itens'               => 'required|array|min:1',
            'itens.*.id_item'     => 'required|integer|exists:item_lote,id_item',
            'itens.*.quantidade'  => 'required|integer|min:1',
        ], [
            'motivo.required'             => 'Selecione o motivo da perda.',
            'itens.required'              => 'Selecione ao menos um item.',
            'itens.*.id_item.exists'      => 'Um dos itens selecionados não existe.',
            'itens.*.quantidade.required' => 'Informe a quantidade de todos os itens selecionados.',
            'itens.*.quantidade.min'      => 'A quantidade deve ser pelo menos 1.',
        ]);

        $resultados = [];

        try {
            DB::transaction(function () use ($request, &$resultados) {
                foreach ($request->itens as $entrada) {
                    $item        = ItemLote::with('produto')->findOrFail($entrada['id_item']);
                    $qtd         = (int) $entrada['quantidade'];
                    $nomeProduto = $item->produto->nome ?? "item #{$item->id_item}";

                    if ($qtd > $item->quantidade) {
                        throw new \RuntimeException("Quantidade informada para \"{$nomeProduto}\" é maior que o disponível ({$item->quantidade}).");
                    }

                    $item->update(['quantidade' => $item->quantidade - $qtd]);

                    $resultados[] = Movimentacao::registrar(
                        'PERDA', $qtd, $item->id_lote, $item->id_item, $request->motivo
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'    => count($resultados) . ' perda(s) registrada(s) com sucesso.',
            'resultados' => $resultados,
        ]);
    }

    public function estatisticas()
    {
        $total    = Movimentacao::where('tipo', 'PERDA')->count();
        $unidades = Movimentacao::where('tipo', 'PERDA')->sum('quantidade');
        $esteMes  = Movimentacao::where('tipo', 'PERDA')
            ->whereMonth('data_movimentacao', now()->month)
            ->whereYear('data_movimentacao', now()->year)
            ->count();

        return response()->json([
            'total'    => $total,
            'unidades' => $unidades,
            'esteMes'  => $esteMes,
        ]);
    }
}