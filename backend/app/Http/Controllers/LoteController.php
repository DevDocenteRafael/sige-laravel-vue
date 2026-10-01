<?php
namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\Produto;
use App\Helpers\AuditHelper;
use App\Jobs\RecalcularAbcJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoteController extends Controller
{
    public function index()
    {
        // Precisa carregar 'itens.produto.fornecedor' — sem isso, item.produto.fornecedor vem null no front
        $lotes = Lote::with(['itens.produto.fornecedor', 'itens.centroCusto'])->orderBy('id_lote', 'asc')->get();
        return response()->json($lotes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero'       => 'required|string|unique:lote,numero_lote',
            'data_entrada' => 'required|date',
        ]);

        $lote = Lote::create([
            'numero_lote'  => $request->numero,
            'data_entrada' => $request->data_entrada,
            'descricao'    => $request->descricao,
            'status'       => 'ATIVO',
        ]);

        AuditHelper::log('Criacao', 'Lote "' . $lote->numero_lote . '" criado.');

        return response()->json($lote, 201);
    }

    public function update(Request $request, int $id)
{
    $lote = Lote::findOrFail($id);

    $request->validate([
        'numero'       => 'required|string|unique:lote,numero_lote,' . $id . ',id_lote',
        'data_entrada' => 'required|date',
        'descricao'    => 'nullable|string',
    ]);

    $numeroAntigo = $lote->numero_lote;

    $mudancas = [];
    if ($lote->numero_lote !== $request->numero) {
        $mudancas[] = "número: \"{$lote->numero_lote}\" → \"{$request->numero}\"";
    }
    if ((string) $lote->data_entrada !== (string) $request->data_entrada) {
        $mudancas[] = "data de entrada: {$lote->data_entrada} → {$request->data_entrada}";
    }
    if ($lote->descricao !== $request->descricao) {
        $mudancas[] = "descrição alterada";
    }

    $lote->update([
        'numero_lote'  => $request->numero,
        'data_entrada' => $request->data_entrada,
        'descricao'    => $request->descricao,
    ]);

    $descricaoLog = count($mudancas) > 0
        ? 'Lote "' . $numeroAntigo . '" atualizado (' . implode('; ', $mudancas) . ').'
        : 'Lote "' . $numeroAntigo . '" atualizado (sem alterações detectadas).';

    AuditHelper::log('Edicao', $descricaoLog);

    return response()->json($lote);
}
    public function destroy(int $id)
    {
        $lote = Lote::with('itens')->findOrFail($id);
        $idsProdutosAfetados = $lote->itens->pluck('id_produto')->unique();

        DB::transaction(function () use ($lote, $id, $idsProdutosAfetados) {
            // Antes do cascade apagar os item_lote, desconta cada quantidade
            // do estoque_atual do produto correspondente — senão o produto
            // fica com estoque "fantasma" (valor antigo nunca decrementado).
            foreach ($lote->itens as $item) {
                Produto::whereKey($item->id_produto)->decrement('estoque_atual', $item->quantidade);
            }

            DB::table('movimentacao')->where('id_lote', $id)->delete();

            AuditHelper::log('Exclusao', 'Lote "' . $lote->numero_lote . '" excluido.');

            $lote->delete();

            // O cascade já removeu os item_lote junto com o lote. Agora
            // verifica quais produtos ficaram sem nenhum lote e os remove.
            foreach ($idsProdutosAfetados as $idProduto) {
                if (!\App\Models\ItemLote::where('id_produto', $idProduto)->exists()) {
                    Produto::whereKey($idProduto)->delete();
                }
            }
        });

        RecalcularAbcJob::dispatchSync();

        return response()->json(['message' => 'Lote excluido com sucesso.']);
    }

    public function destroyMultiplos(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:lote,id_lote',
        ], [
            'ids.required' => 'Selecione ao menos um lote para excluir.',
            'ids.*.exists' => 'Um dos lotes selecionados não existe.',
        ]);

        $lotes = Lote::with('itens')->whereIn('id_lote', $request->ids)->get();
        $idsProdutosAfetados = $lotes->flatMap->itens->pluck('id_produto')->unique();

        DB::transaction(function () use ($request, $lotes, $idsProdutosAfetados) {
            foreach ($lotes as $lote) {
                foreach ($lote->itens as $item) {
                    Produto::whereKey($item->id_produto)->decrement('estoque_atual', $item->quantidade);
                }
            }

            DB::table('movimentacao')->whereIn('id_lote', $request->ids)->delete();

            foreach ($lotes as $lote) {
                AuditHelper::log('Exclusao', 'Lote "' . $lote->numero_lote . '" excluído (exclusão em massa).');
            }

            Lote::whereIn('id_lote', $request->ids)->delete();

            // O cascade já removeu os item_lote junto com os lotes. Agora
            // verifica quais produtos ficaram sem nenhum lote e os remove.
            foreach ($idsProdutosAfetados as $idProduto) {
                if (!\App\Models\ItemLote::where('id_produto', $idProduto)->exists()) {
                    Produto::whereKey($idProduto)->delete();
                }
            }
        });

        RecalcularAbcJob::dispatchSync();

        return response()->json(['message' => count($lotes) . ' lote(s) excluído(s) com sucesso.']);
    }
}
