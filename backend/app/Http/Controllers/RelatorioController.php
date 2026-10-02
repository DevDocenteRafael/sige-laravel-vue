<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\Movimentacao;
use Carbon\Carbon;

class RelatorioController extends Controller
{
    // Posição atual do estoque agrupada por item
    public function estoque()
    {
        $itens = ItemLote::with(['produto.categoria'])->get()->map(fn($item) => [
            'produto_id'       => $item->id_item,
            'nome'             => $item->produto?->nome,
            'categoria'        => $item->produto?->categoria?->nome ?? '—',
            'quantidade_total' => $item->quantidade,
            'estoque_minimo'   => $item->produto?->estoque_minimo ?? 0,
            'unidade'          => $item->unidade_medida ?? 'UN',
        ]);

        return response()->json($itens);
    }

    // Itens vencidos ou que vencem nos próximos 30 dias
    public function vencimentos()
    {
        $limite = Carbon::now()->addDays(30);

        $itens = ItemLote::whereNotNull('data_validade')
            ->where('data_validade', '<=', $limite)
            ->where('quantidade', '>', 0)
            ->with(['lote', 'produto'])
            ->get()
            ->map(fn($item) => [
                'id'         => $item->id_item,
                'produto'    => ['nome' => $item->produto?->nome, 'unidade' => $item->unidade_medida],
                'lote'       => ['numero' => $item->lote?->numero_lote ?? '—'],
                'quantidade' => $item->quantidade,
                'validade'   => $item->data_validade,
            ]);

        return response()->json($itens);
    }

    // Log de auditoria baseado nas movimentações
    public function auditoria()
    {
        $logs = Movimentacao::with(['item.produto', 'usuario'])
            ->orderBy('data_movimentacao', 'desc')
            ->limit(200)
            ->get()
            ->map(fn($m) => [
                'id'       => $m->id_movimentacao,
                'usuario'  => ['nome' => $m->usuario?->name ?? 'Sistema'],
                'acao'     => $m->tipo,
                'detalhes' => [
                    'produto'    => $m->item?->produto?->nome ?? '—',
                    'quantidade' => $m->quantidade,
                    'observacao' => $m->observacao ?? '—',
                ],
                'criado_em' => $m->data_movimentacao,
            ]);

        return response()->json($logs);
    }

    // Lista de itens de lote para a tela de Relatórios (filtros, exportação)
    public function itens()
    {
        $itens = ItemLote::with(['lote', 'produto.fornecedor', 'centroCusto'])
            ->get()
            ->map(fn($item) => [
                'id_item'         => $item->id_item,
                'lote'            => [
                    'numero_lote'  => $item->lote?->numero_lote,
                    'data_entrada' => $item->lote?->data_entrada,
                ],
                'sku'             => $item->produto?->sku,
                'nome'            => $item->produto?->nome,
                'quantidade'      => $item->quantidade,
                'data_validade'   => $item->data_validade,
                'fornecedor'      => $item->produto?->fornecedor?->nome,
                'localizacao'     => $item->localizacao,
               'id_centro_custo' => $item->id_centro_custo,
'centro_custo'    => $item->centroCusto ? [
    'id_centro_custo' => $item->centroCusto->id_centro_custo,
    'codigo'          => $item->centroCusto->codigo,
    'nome'            => $item->centroCusto->nome,
] : null,
            ]);

        return response()->json($itens);
    }
}