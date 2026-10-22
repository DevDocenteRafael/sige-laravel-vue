<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\Fornecedor;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Services\PedidoPdfParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
}