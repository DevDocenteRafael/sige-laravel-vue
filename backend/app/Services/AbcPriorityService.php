<?php
namespace App\Services;

use App\Models\ItemLote;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;

class AbcPriorityService
{
    public function recalcularTodos(): void
    {
        $itens = ItemLote::where('prioridade_manual', false)->get(['id_item', 'prioridade_abc']);

        if ($itens->isEmpty()) {
            return;
        }

        $movimentos = Movimentacao::whereIn('tipo', ['ENTRADA', 'SAIDA'])
            ->whereIn('id_item', $itens->pluck('id_item'))
            ->selectRaw('id_item, SUM(quantidade) as total')
            ->groupBy('id_item')
            ->pluck('total', 'id_item');

        $totaisPorItem = [];
        foreach ($itens as $item) {
            $totaisPorItem[$item->id_item] = $movimentos[$item->id_item] ?? 0;
        }

        $itensOrdenados = $itens->sortByDesc(fn($item) => $totaisPorItem[$item->id_item])->values();
        $totalGeral = array_sum($totaisPorItem);

        $classesPorItem = [];

        if ($totalGeral <= 0) {
            foreach ($itensOrdenados as $item) {
                $classesPorItem[$item->id_item] = 'C';
            }
        } else {
            $acumulado = 0;
            foreach ($itensOrdenados as $item) {
                $acumulado += $totaisPorItem[$item->id_item];
                $percentual = $acumulado / $totalGeral;

                $classesPorItem[$item->id_item] = $percentual <= 0.80 ? 'A' : ($percentual <= 0.95 ? 'B' : 'C');
            }
        }

        $paraAtualizar = $itens->filter(fn($item) => $item->prioridade_abc !== $classesPorItem[$item->id_item]);

        if ($paraAtualizar->isEmpty()) {
            return;
        }

        $cases = $paraAtualizar
            ->map(fn($item) => "WHEN {$item->id_item} THEN '{$classesPorItem[$item->id_item]}'")
            ->implode(' ');
        $ids = $paraAtualizar->pluck('id_item')->implode(',');

        DB::statement(
            "UPDATE item_lote SET prioridade_abc = CASE id_item {$cases} END WHERE id_item IN ({$ids})"
        );
    }
}