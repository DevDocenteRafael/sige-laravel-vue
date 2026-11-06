<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\Lote;
use App\Models\Produto;
use App\Models\Movimentacao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $produtosEstoqueCritico = $this->buscarProdutosEstoqueCritico();
        $produtosVencendo       = $this->buscarProdutosVencendo();
        $totalCategorias        = $this->contarCategorias();
        $movimentosRecentes     = $this->buscarMovimentosRecentes();
        $evolucao               = $this->calcularEvolucaoEstoque();
        $distribuicao           = $this->calcularDistribuicaoCategorias();
        $topProdutos            = $this->buscarTopProdutos();

        $resumo = [
            'totalProdutos'    => ItemLote::count(),
            'totalLotes'       => Lote::count(),
            'estoqueCritico'   => $produtosEstoqueCritico->count(),
            'vencendoEm30Dias' => $produtosVencendo->count(),
            'totalCategorias'  => $totalCategorias,
        ];

        return response()->json([
            'resumo'                 => $resumo,
            'produtosEstoqueCritico' => $produtosEstoqueCritico,
            'produtosVencendo'       => $produtosVencendo,
            'movimentosRecentes'     => $movimentosRecentes,
            'evolucaoEstoque'        => $evolucao,
            'distribuicaoCategorias' => $distribuicao,
            'topProdutos'            => $topProdutos,
        ]);
    }

    public function topProdutos(Request $request)
    {
        $limite = (int) $request->query('limite', 10);
        if ($limite < 1) {
            $limite = 10;
        } elseif ($limite > 1000) {
            $limite = 1000;
        }

        $categoria = $request->query('categoria');
        if ($categoria === 'todas') {
            $categoria = null;
        }

        return response()->json(
            $this->buscarTopProdutos($limite, $categoria)
        );
    }

    /**
     * Estoque baixo: só produtos com mínimo definido (> 0) cujo saldo
     * (soma dos itens de lote) é menor ou igual ao mínimo.
     * Mesma regra e mesma fonte da página Produtos.
     */
    private function buscarProdutosEstoqueCritico()
    {
        return Produto::withSum('itensLote as saldo', 'quantidade')
            ->where('estoque_minimo', '>', 0)
            ->havingRaw('COALESCE(saldo, 0) <= estoque_minimo')
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id_produto,
                'nome'           => $p->nome,
                'quantidade'     => $p->saldo ?? 0,
                'estoque_minimo' => $p->estoque_minimo,
                'unidade_medida' => $p->unidade_medida,
            ]);
    }

    private function buscarProdutosVencendo()
    {
        $hoje     = Carbon::today();
        $em30dias = Carbon::today()->addDays(30);

        return ItemLote::with(['lote', 'produto'])
            ->whereNotNull('data_validade')
            ->whereBetween('data_validade', [$hoje, $em30dias])
            ->orderBy('data_validade')
            ->get()
            ->map(fn($i) => [
                'id'             => $i->id_item,
                'nome'           => $i->produto?->nome,
                'lote'           => $i->lote?->numero_lote,
                'data_validade'  => $i->data_validade,
                'dias_restantes' => Carbon::today()->diffInDays(Carbon::parse($i->data_validade), false),
                'quantidade'     => $i->quantidade,
                'unidade_medida' => $i->unidade_medida,
            ]);
    }

    private function contarCategorias(): int
    {
        return Produto::whereNotNull('id_categoria')->distinct()->count('id_categoria');
    }

    private function buscarMovimentosRecentes()
    {
        return Movimentacao::with(['item.produto', 'usuario'])
            ->orderByDesc('data_movimentacao')
            ->limit(10)
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id_movimentacao,
                'tipo'           => strtolower($m->tipo),
                'quantidade'     => $m->quantidade,
                'data_movimento' => $m->data_movimentacao,
                'usuario'        => $m->usuario ? ['nome' => $m->usuario->name ?? $m->usuario->nome ?? null] : null,
                'item_lote'      => $m->item ? [
                    'produto' => ['nome' => $m->item->produto?->nome]
                ] : null,
            ]);
    }

    private function calcularEvolucaoEstoque()
    {
        $inicio = Carbon::today()->subDays(29);

        $movs = Movimentacao::selectRaw("DATE(data_movimentacao) as dia, tipo, SUM(quantidade) as total")
            ->where('data_movimentacao', '>=', $inicio)
            ->groupBy('dia', 'tipo')
            ->get()
            ->groupBy('dia');

        $saldoInicial = Movimentacao::where('data_movimentacao', '<', $inicio)
            ->selectRaw("SUM(CASE WHEN tipo = 'ENTRADA' THEN quantidade ELSE -quantidade END) as saldo")
            ->value('saldo') ?? 0;

        $evolucao = collect();
        $acumulado = $saldoInicial;

        for ($i = 29; $i >= 0; $i--) {
            $dia = Carbon::today()->subDays($i);
            $chave = $dia->format('Y-m-d');
            $doDia = $movs->get($chave, collect());

            $entradas = $doDia->where('tipo', 'ENTRADA')->sum('total');
            $saidas   = $doDia->whereIn('tipo', ['SAIDA', 'PERDA'])->sum('total');
            $acumulado += $entradas - $saidas;

            $evolucao->push([
                'label'        => $dia->format('d/m'),
                'entradas'     => (int) $entradas,
                'saidas'       => (int) $saidas,
                'estoqueTotal' => max(0, (int) $acumulado),
            ]);
        }

        return $evolucao;
    }

    private function calcularDistribuicaoCategorias()
    {
        $distribuicao = DB::table('item_lote')
            ->join('produto', 'produto.id_produto', '=', 'item_lote.id_produto')
            ->join('categoria', 'categoria.id_categoria', '=', 'produto.id_categoria')
            ->selectRaw('categoria.nome as categoria, COUNT(*) as total')
            ->groupBy('categoria.nome')
            ->get();

        $totalParaPercentual = $distribuicao->sum('total');

        return $distribuicao->map(fn($c) => [
            'categoria'  => $c->categoria,
            'quantidade' => $c->total,
            'percentual' => $totalParaPercentual > 0
                ? round(($c->total / $totalParaPercentual) * 100, 1)
                : 0,
        ])->sortByDesc('percentual')->values();
    }

    /**
     * Top produtos por saldo real (soma dos itens de lote),
     * mantendo o mesmo formato de resposta de antes.
     */
    private function buscarTopProdutos(int $limite = 10, ?string $categoria = null)
    {
        $query = Produto::with('categoria')
            ->withSum('itensLote as saldo', 'quantidade')
            ->orderByDesc('saldo');

        if (!empty($categoria)) {
            $query->whereHas('categoria', fn($q) => $q->where('nome', $categoria));
        }

        return $query->limit($limite)
            ->get()
            ->map(fn($p) => [
                'id_produto'     => $p->id_produto,
                'nome'           => $p->nome,
                'estoque_atual'  => $p->saldo ?? 0,
                'estoque_minimo' => $p->estoque_minimo,
                'categoria'      => $p->categoria ? ['nome' => $p->categoria->nome] : null,
            ]);
    }
}