<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use App\Models\ItemLote;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RelatorioAvancadoController extends Controller
{
    public function perdas(Request $request)
    {
        $dias  = $request->input('dias', 30);
        $desde = Carbon::now()->subDays($dias);

        $perdas = Movimentacao::with(['item.produto', 'usuario'])
            ->where('tipo', 'PERDA')
            ->where('data_movimentacao', '>=', $desde)
            ->orderBy('data_movimentacao', 'desc')
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id_movimentacao,
                'data'       => $m->data_movimentacao,
                'produto'    => $m->item?->produto?->nome ?? '—',
                'sku'        => $m->item?->produto?->sku  ?? '—',
                'motivo'     => $m->observacao ?? '—',
                'quantidade' => $m->quantidade,
                'usuario'    => $m->usuario?->name ?? '—',
            ]);

        $porMotivo = $perdas->groupBy('motivo')->map(fn($grupo, $motivo) => [
            'motivo'     => $motivo,
            'total'      => $grupo->sum('quantidade'),
            'ocorrencias'=> $grupo->count(),
        ])->values();

        return response()->json([
            'perdas'    => $perdas,
            'porMotivo' => $porMotivo,
            'resumo'    => [
                'total'    => $perdas->count(),
                'unidades' => $perdas->sum('quantidade'),
                'tipos'    => $porMotivo->count(),
            ],
        ]);
    }

    public function abc()
    {
        $movimentos = Movimentacao::whereIn('tipo', ['ENTRADA', 'SAIDA'])
            ->selectRaw('id_item, SUM(quantidade) as total')
            ->groupBy('id_item')
            ->get()
            ->keyBy('id_item');

        $itens = ItemLote::with('produto')->get()->map(function ($item) use ($movimentos) {
            return [
                'id_item'           => $item->id_item,
                'nome'              => $item->produto?->nome,
                'sku'               => $item->produto?->sku,
                'movimento'         => $movimentos[$item->id_item]->total ?? 0,
                'classe'            => $item->prioridade_abc ?? 'C',
                'prioridade_manual' => $item->prioridade_manual,
            ];
        })->sortByDesc('movimento')->values();

        $totalMovimento = $itens->sum('movimento');

        $acumulado = 0;
        $resultado = $itens->map(function ($item) use ($totalMovimento, &$acumulado) {
            $percentual = $totalMovimento > 0
                ? round(($item['movimento'] / $totalMovimento) * 100, 2)
                : 0;
            $acumulado += $percentual;

            return array_merge($item, [
                'percentual' => $percentual,
                'acumulado'  => round($acumulado, 2),
            ]);
        });

        $resumo = [
            'total' => $resultado->count(),
            'A'     => $resultado->where('classe', 'A')->count(),
            'B'     => $resultado->where('classe', 'B')->count(),
            'C'     => $resultado->where('classe', 'C')->count(),
        ];

        return response()->json([
            'itens'  => $resultado,
            'resumo' => $resumo,
        ]);
    }
}