<?php

namespace App\Http\Controllers;

use App\Models\ItemLote;
use App\Models\Lote;
use App\Models\Movimentacao;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JsonException;

class BackupController extends Controller
{
    public function exportarBackup()
    {
        $dados = [
            'tipo' => 'backup_estoque',
            'versao' => 1,
            'exportado_em' => now()->toISOString(),
            'produtos' => Produto::all(),
            'lotes' => Lote::all(),
            'item_lotes' => ItemLote::all(),
            'movimentacoes' => Movimentacao::all(),
        ];

        return response()->json($dados, 200, [
            'Content-Disposition' => 'attachment; filename="backup_estoque_sige_' . now()->format('Ymd_His') . '.json"',
        ]);
    }

    public function restaurarBackup(Request $request)
    {
        $request->validate(['arquivo' => 'required|file|mimes:json,txt']);

        try {
            $conteudo = json_decode(
                file_get_contents($request->file('arquivo')->getRealPath()),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return response()->json(['message' => 'O arquivo não contém um JSON válido.'], 422);
        }

        if (!is_array($conteudo)) {
            return response()->json(['message' => 'O formato do backup é inválido.'], 422);
        }

        $validator = Validator::make($conteudo, [
            'tipo' => 'sometimes|in:backup_estoque',
            'versao' => 'sometimes|integer|in:1',
            'produtos' => 'required|array',
            'produtos.*' => 'required|array',
            'produtos.*.id_produto' => 'required|integer|min:1|distinct',
            'produtos.*.nome' => 'required|string|max:100',
            'produtos.*.sku' => 'required|string|max:50|distinct',
            'produtos.*.unidade_medida' => 'required|string|max:20',
            'produtos.*.preco_custo' => 'sometimes|numeric|min:0',
            'produtos.*.estoque_minimo' => 'sometimes|numeric|min:0',
            'produtos.*.estoque_atual' => 'sometimes|numeric|min:0',
            'produtos.*.prioridade_abc' => 'nullable|in:A,B,C',
            'produtos.*.id_categoria' => 'nullable|integer|min:1',
            'produtos.*.id_fornecedor' => 'nullable|integer|min:1',
            'produtos.*.percentual_alerta' => 'sometimes|nullable|numeric|min:0|max:100',
            'lotes' => 'required|array',
            'lotes.*' => 'required|array',
            'lotes.*.id_lote' => 'required|integer|min:1|distinct',
            'lotes.*.numero_lote' => 'required|string|max:50',
            'lotes.*.quantidade' => 'sometimes|numeric|min:0',
            'lotes.*.status' => 'sometimes|in:ATIVO,VENCIDO,ESGOTADO',
            'lotes.*.data_entrada' => 'required|date',
            'lotes.*.data_validade' => 'nullable|date',
            'lotes.*.data_atualizacao' => 'sometimes|date',
            'lotes.*.descricao' => 'sometimes|nullable|string|max:255',
            'lotes.*.id_produto' => 'nullable|integer|min:1',
            'lotes.*.id_localizacao' => 'nullable|integer|min:1',
            'item_lotes' => 'required|array',
            'item_lotes.*' => 'required|array',
            'item_lotes.*.id_item' => 'required|integer|min:1|distinct',
            'item_lotes.*.id_lote' => 'required|integer|min:1',
            'item_lotes.*.id_produto' => 'required|integer|min:1',
            'item_lotes.*.quantidade' => 'required|numeric|min:0',
            'item_lotes.*.unidade_medida' => 'sometimes|string|max:20',
            'item_lotes.*.data_validade' => 'nullable|date',
            'item_lotes.*.localizacao' => 'sometimes|nullable|string|max:255',
            'item_lotes.*.prioridade_abc' => 'sometimes|nullable|in:A,B,C',
            'item_lotes.*.prioridade_manual' => 'sometimes|nullable|boolean',
            'item_lotes.*.ordem' => 'sometimes|nullable|integer|min:0',
            'item_lotes.*.id_centro_custo' => 'sometimes|nullable|integer|min:1',
            'item_lotes.*.created_at' => 'sometimes|nullable|date',
            'item_lotes.*.updated_at' => 'sometimes|nullable|date',
            'movimentacoes' => 'required|array',
            'movimentacoes.*' => 'required|array',
            'movimentacoes.*.id_movimentacao' => 'required|integer|min:1|distinct',
            'movimentacoes.*.tipo' => 'required|in:ENTRADA,SAIDA,PERDA,AJUSTE,TRANSFERENCIA',
            'movimentacoes.*.quantidade' => 'required|numeric|min:0',
            'movimentacoes.*.data_movimentacao' => 'required|date',
            'movimentacoes.*.observacao' => 'sometimes|nullable|string|max:255',
            'movimentacoes.*.id_lote' => 'required|integer|min:1',
            'movimentacoes.*.id_item' => 'sometimes|nullable|integer|min:1',
            'movimentacoes.*.id_usuario' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'O arquivo de backup contém dados inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $camposPermitidos = [
            'produtos' => ['id_produto', 'nome', 'sku', 'unidade_medida', 'preco_custo', 'estoque_minimo', 'estoque_atual', 'prioridade_abc', 'id_categoria', 'id_fornecedor', 'percentual_alerta'],
            'lotes' => ['id_lote', 'numero_lote', 'quantidade', 'status', 'data_entrada', 'data_validade', 'data_atualizacao', 'descricao', 'id_produto', 'id_localizacao'],
            'item_lotes' => ['id_item', 'id_lote', 'id_produto', 'quantidade', 'unidade_medida', 'data_validade', 'localizacao', 'prioridade_abc', 'prioridade_manual', 'ordem', 'id_centro_custo', 'created_at', 'updated_at'],
            'movimentacoes' => ['id_movimentacao', 'tipo', 'quantidade', 'data_movimentacao', 'observacao', 'id_lote', 'id_item', 'id_usuario'],
        ];
        $errosCampos = [];
        foreach ($camposPermitidos as $colecao => $campos) {
            foreach ($conteudo[$colecao] as $indice => $registro) {
                $camposDesconhecidos = array_diff(array_keys($registro), $campos);
                if ($camposDesconhecidos !== []) {
                    $errosCampos["$colecao.$indice"][] = 'O registro contém campos desconhecidos.';
                }
            }
        }
        if ($errosCampos !== []) {
            return response()->json([
                'message' => 'O arquivo de backup contém dados inválidos.',
                'errors' => $errosCampos,
            ], 422);
        }

        $errosReferenciais = $this->validarReferencias($conteudo);
        if ($errosReferenciais !== []) {
            return response()->json([
                'message' => 'O arquivo de backup contém referências inválidas.',
                'errors' => $errosReferenciais,
            ], 422);
        }

        try {
            DB::transaction(function () use ($conteudo) {
                DB::table('movimentacao')->delete();
                DB::table('item_lote')->delete();
                DB::table('lote')->delete();
                DB::table('produto')->delete();

                foreach ($conteudo['produtos'] as $produto) {
                    DB::table('produto')->insert($produto);
                }
                foreach ($conteudo['lotes'] as $lote) {
                    DB::table('lote')->insert($lote);
                }
                foreach ($conteudo['item_lotes'] as $item) {
                    DB::table('item_lote')->insert($item);
                }
                foreach ($conteudo['movimentacoes'] as $movimentacao) {
                    DB::table('movimentacao')->insert($movimentacao);
                }
            });
        } catch (\Throwable $exception) {
            $incidente = (string) Str::uuid();
            Log::error('Falha ao restaurar backup de estoque.', [
                'incident_id' => $incidente,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Não foi possível restaurar o backup. Informe o identificador ao administrador.',
                'incident_id' => $incidente,
            ], 500);
        }

        return response()->json(['message' => 'Backup de estoque restaurado com sucesso.']);
    }

    private function validarReferencias(array $conteudo): array
    {
        $erros = [];
        $idsProdutos = $this->ids($conteudo['produtos'], 'id_produto');
        $idsLotes = $this->ids($conteudo['lotes'], 'id_lote');
        $idsItens = $this->ids($conteudo['item_lotes'], 'id_item');
        $lotesPorId = array_column($conteudo['lotes'], null, 'id_lote');
        $itensPorId = array_column($conteudo['item_lotes'], null, 'id_item');
        $quantidadesPorProduto = [];

        foreach ($conteudo['item_lotes'] as $item) {
            $quantidadesPorProduto[$item['id_produto']] = ($quantidadesPorProduto[$item['id_produto']] ?? 0) + $item['quantidade'];
        }

        foreach ($conteudo['produtos'] as $indice => $produto) {
            $quantidadeItens = $quantidadesPorProduto[$produto['id_produto']] ?? 0;
            if (abs((float) ($produto['estoque_atual'] ?? 0) - (float) $quantidadeItens) > 0.01) {
                $erros["produtos.$indice.estoque_atual"][] = 'O saldo não corresponde à soma das quantidades dos itens do produto.';
            }
        }

        foreach ($conteudo['lotes'] as $indice => $lote) {
            if (!empty($lote['id_produto']) && !isset($idsProdutos[$lote['id_produto']])) {
                $erros["lotes.$indice.id_produto"][] = 'O produto informado não existe no backup.';
            }
        }

        foreach ($conteudo['item_lotes'] as $indice => $item) {
            if (!isset($idsLotes[$item['id_lote']])) {
                $erros["item_lotes.$indice.id_lote"][] = 'O lote informado não existe no backup.';
            }
            if (!isset($idsProdutos[$item['id_produto']])) {
                $erros["item_lotes.$indice.id_produto"][] = 'O produto informado não existe no backup.';
            }
            if (!empty($lotesPorId[$item['id_lote']]['id_produto']) && $lotesPorId[$item['id_lote']]['id_produto'] != $item['id_produto']) {
                $erros["item_lotes.$indice.id_produto"][] = 'O produto do item não corresponde ao produto do lote.';
            }
            if (!empty($item['id_centro_custo']) && !$this->existe('centro_custo', 'id_centro_custo', $item['id_centro_custo'])) {
                $erros["item_lotes.$indice.id_centro_custo"][] = 'O centro de custo informado não existe.';
            }
        }

        foreach ($conteudo['movimentacoes'] as $indice => $movimentacao) {
            if (!isset($idsLotes[$movimentacao['id_lote']])) {
                $erros["movimentacoes.$indice.id_lote"][] = 'O lote informado não existe no backup.';
            }
            if (!empty($movimentacao['id_item']) && !isset($idsItens[$movimentacao['id_item']])) {
                $erros["movimentacoes.$indice.id_item"][] = 'O item informado não existe no backup.';
            }
            if (!empty($movimentacao['id_item']) && $itensPorId[$movimentacao['id_item']]['id_lote'] != $movimentacao['id_lote']) {
                $erros["movimentacoes.$indice.id_item"][] = 'O item informado não corresponde ao lote da movimentação.';
            }
            if (!$this->existe('users', 'id', $movimentacao['id_usuario'])) {
                $erros["movimentacoes.$indice.id_usuario"][] = 'O usuário informado não existe nesta instalação.';
            }
        }

        foreach ($conteudo['produtos'] as $indice => $produto) {
            foreach ([
                'id_categoria' => ['categoria', 'id_categoria'],
                'id_fornecedor' => ['fornecedor', 'id_fornecedor'],
            ] as $campo => [$tabela, $coluna]) {
                if (!empty($produto[$campo]) && !$this->existe($tabela, $coluna, $produto[$campo])) {
                    $erros["produtos.$indice.$campo"][] = 'A referência informada não existe nesta instalação.';
                }
            }
        }

        foreach ($conteudo['lotes'] as $indice => $lote) {
            if (!empty($lote['id_localizacao']) && !$this->existe('localizacao', 'id_localizacao', $lote['id_localizacao'])) {
                $erros["lotes.$indice.id_localizacao"][] = 'A localização informada não existe nesta instalação.';
            }
        }

        return $erros;
    }

    private function ids(array $registros, string $campo): array
    {
        return array_fill_keys(array_column($registros, $campo), true);
    }

    private function existe(string $tabela, string $coluna, int|string $id): bool
    {
        return DB::table($tabela)->where($coluna, $id)->exists();
    }
}
