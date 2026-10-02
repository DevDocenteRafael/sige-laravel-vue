<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Lote;
use App\Models\ItemLote;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class ImportacaoController extends Controller
{
    public function stats()
    {
        return response()->json([
            'lotes'         => Lote::count(),
            'produtos'      => Produto::count(),
            'movimentacoes' => Movimentacao::count(),
            'itens_estoque' => ItemLote::where('quantidade', '>', 0)->count(),
        ]);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planilha');

        $headers = ['CÓDIGO', 'DESCRIÇÃO', 'UNIDADE', 'SALDO', 'ESTOQUE MÍNIMO', 'VALIDADE', 'CENTRO DE CUSTO'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E2F3');

        $sheet->fromArray(
            ['ES0610000000001', 'ABAIXADOR DE MADEIRA PARA LÍNGUA', 'PCT', 6, 2, '31/12/2024', 'CC-001'],
            null,
            'A2'
        );
        $sheet->getStyle('A2:G2')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF2CC');

        $sheet->fromArray(['', 'ACETONA 500ML', 'UN', 2, 1, '06/2026', 'CC-002'], null, 'A3');
        $sheet->getStyle('A3:G3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF2CC');

        $sheet->setCellValue('B4', 'LETRA A');
        $sheet->getStyle('B4')->getFont()->setItalic(true)->setBold(true);

        foreach (['A' => 22, 'B' => 45, 'C' => 12, 'D' => 10, 'E' => 16, 'F' => 14, 'G' => 20] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A2');

        $instrucoes = $spreadsheet->createSheet();
        $instrucoes->setTitle('Instruções');
        $instrucoes->setCellValue('A1', 'Como preencher esta planilha');
        $instrucoes->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $texto = [
            '',
            'CÓDIGO: opcional. Se vazio, o sistema gera um SKU automático a partir da descrição.',
            'DESCRIÇÃO: obrigatório. Nome do produto.',
            'UNIDADE: opcional. Se vazio, assume "UN".',
            'SALDO: obrigatório. Quantidade em estoque. Linhas sem saldo são ignoradas.',
            'ESTOQUE MÍNIMO: opcional. Quantidade mínima antes do produto ser considerado crítico. Se vazio, assume 0.',
            'VALIDADE: opcional. Aceita datas como 31/12/2025, 2025-12-31, ou "12/2025" (assume dia 1º do mês).',
            'CENTRO DE CUSTO: opcional. Código ou nome do centro de custo do item.',
            '',
            'Linhas amarelas: exemplos de preenchimento correto — pode apagar antes de importar.',
            '',
            'Linhas de agrupamento (opcional): use uma linha só com "LETRA A", "LETRA B" etc. na coluna DESCRIÇÃO,',
            'deixando as demais colunas vazias, para separar visualmente grupos de produtos. O sistema ignora',
            'essas linhas automaticamente na importação — elas não geram produtos nem erros.',
            '',
            'Evite: linhas totalmente em branco no meio dos dados e alterar os nomes das colunas no cabeçalho.',
            '',
            'Se o CÓDIGO já existir no sistema, o estoque desse produto é incrementado (somado) em vez de duplicado.',
            'O ESTOQUE MÍNIMO só é aplicado na criação de produtos novos — se o produto já existir, o mínimo atual dele não é alterado.',
        ];
        foreach ($texto as $i => $linha) {
            $instrucoes->setCellValue('A' . ($i + 2), $linha);
        }
        $instrucoes->getColumnDimension('A')->setWidth(100);
        foreach ($instrucoes->getRowIterator() as $r) {
            $instrucoes->getStyle('A' . $r->getRowIndex())->getAlignment()->setWrapText(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            // Limpa qualquer saída que tenha vazado antes (espaços, warnings, BOM)
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $writer->save('php://output');
        }, 'modelo_importacao_almoxarifado.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function previewImportacao(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('arquivo')->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray(null, true, true, false);

            $headerIndex = null;
            foreach ($rows as $i => $row) {
                $joined = implode(' ', array_map('mb_strtoupper', array_filter($row, 'is_string')));
                if (str_contains($joined, 'DESCRI')) {
                    $headerIndex = $i;
                    break;
                }
            }

            if ($headerIndex === null) {
                return response()->json(['message' => 'Cabeçalho não encontrado na planilha.'], 422);
            }

            $header = array_map(fn($v) => mb_strtoupper(trim((string)$v)), $rows[$headerIndex]);
            $colMap = [];
            foreach ($header as $idx => $col) {
                if (str_contains($col, 'CODIGO') || str_contains($col, 'CÓDIGO')) $colMap['codigo']        = $idx;
                if (str_contains($col, 'DESCRI'))                                  $colMap['descricao']     = $idx;
                if (str_contains($col, 'UNIDADE'))                                 $colMap['unidade']       = $idx;
                if (str_contains($col, 'SALDO'))                                   $colMap['saldo']         = $idx;
                if (str_contains($col, 'MINIMO') || str_contains($col, 'MÍNIMO'))  $colMap['estoque_minimo'] = $idx;
                if (str_contains($col, 'VALIDADE'))                                $colMap['validade']      = $idx;
                if (str_contains($col, 'CENTRO'))                                  $colMap['centro_custo']  = $idx;
            }

            if (!isset($colMap['descricao'], $colMap['saldo'])) {
                return response()->json(['message' => 'Colunas obrigatórias (DESCRIÇÃO, SALDO) não encontradas.'], 422);
            }

            $dataRows  = array_slice($rows, $headerIndex + 1);
            $itens     = [];
            $ignorados = 0;

            foreach ($dataRows as $row) {
                $descricao = isset($colMap['descricao']) ? trim((string)($row[$colMap['descricao']] ?? '')) : '';
                $saldo     = isset($colMap['saldo'])     ? $row[$colMap['saldo']]                           : null;

                if ($descricao === '' || is_null($saldo) || $saldo === '') {
                    $ignorados++;
                    continue;
                }
                if (preg_match('/^LETRA\s+[A-Z]$/i', $descricao)) {
                    $ignorados++;
                    continue;
                }

                $codigo         = isset($colMap['codigo'])         ? trim((string)($row[$colMap['codigo']]         ?? '')) : '';
                $unidade        = isset($colMap['unidade'])        ? trim((string)($row[$colMap['unidade']]        ?? 'UN')) : 'UN';
                $validade       = isset($colMap['validade'])       ? $row[$colMap['validade']]                              : null;
                $estoqueMinimo  = isset($colMap['estoque_minimo']) ? $row[$colMap['estoque_minimo']]                        : null;
                $centroCusto    = isset($colMap['centro_custo'])
                    ? trim((string)($row[$colMap['centro_custo']] ?? ''))
                    : '';
                $quantidade     = (int) $saldo;
                $dataValidade   = $this->converterValidade($validade);

                $sku = $codigo !== ''
                    ? $codigo
                    : 'GEN-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $descricao), 0, 12));

                // Apenas consulta — nunca cria o produto no preview
                $produtoExistente = Produto::where('sku', $sku)->first();

                $itens[] = [
                    'produto_id'        => $produtoExistente->id_produto ?? null, // null = produto novo
                    'sku'               => $sku,
                    'nome'              => $descricao,
                    'unidade'           => $unidade ?: 'UN',
                    'quantidade'        => $quantidade,
                    'estoque_minimo'    => is_numeric($estoqueMinimo) ? (int) $estoqueMinimo : 0,
                    'validade'          => $dataValidade,
                    'centro_custo'      => $centroCusto !== '' ? $centroCusto : null,
                    'produto_existente' => (bool) $produtoExistente, // útil pro frontend avisar na tabela
                ];
            }

            return response()->json([
                'itens'     => $itens,
                'ignorados' => $ignorados,
                'total'     => count($itens),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao ler planilha: ' . $e->getMessage()], 500);
        }
    }

    public function confirmarImportacao(Request $request)
    {
        $request->validate([
            'modo'                        => 'required|in:unico,multiplo',
            'lote.numero_lote'            => 'required_if:modo,unico|nullable|string',
            'lote.data_validade'          => 'nullable|date',
            'itens'                       => 'required_if:modo,unico|array',
            'itens.*.sku'                 => 'required_with:itens|string',
            'itens.*.nome'                => 'required_with:itens|string',
            'itens.*.quantidade'          => 'required_with:itens|integer|min:0',
            'itens.*.unidade'             => 'nullable|string',
            'itens.*.estoque_minimo'      => 'nullable|integer|min:0',
            'itens.*.validade'            => 'nullable|date',
            'itens.*.centro_custo'        => 'nullable|string',
            'lotes'                       => 'required_if:modo,multiplo|array',
            'lotes.*.numero_lote'         => 'nullable|string',
            'lotes.*.data_validade'       => 'nullable|date',
            'lotes.*.itens'               => 'required_if:modo,multiplo|array',
            'lotes.*.itens.*.sku'         => 'required_with:lotes.*.itens|string',
            'lotes.*.itens.*.nome'        => 'required_with:lotes.*.itens|string',
            'lotes.*.itens.*.quantidade'  => 'required_with:lotes.*.itens|integer|min:0',
            'lotes.*.itens.*.unidade'     => 'nullable|string',
            'lotes.*.itens.*.estoque_minimo' => 'nullable|integer|min:0',
            'lotes.*.itens.*.centro_custo'   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $lotesCriados = 0;

            if ($request->modo === 'unico') {
                $this->criarLoteComItens(
                    $request->input('lote.numero_lote') ?: ('LOTE-' . now()->format('Ymd') . '-' . rand(1000, 9999)),
                    $request->input('lote.data_validade'),
                    $request->itens
                );
                $lotesCriados = 1;
            } else {
                foreach ($request->lotes as $loteData) {
                    $this->criarLoteComItens(
                        $loteData['numero_lote'] ?: ('LOTE-' . now()->format('Ymd') . '-' . rand(1000, 9999)),
                        $loteData['data_validade'] ?? null,
                        $loteData['itens']
                    );
                    $lotesCriados++;
                }
            }

            DB::commit();
            return response()->json([
                'message'       => 'Importação concluída com sucesso!',
                'lotes_criados' => $lotesCriados,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro na importação: ' . $e->getMessage()], 500);
        }
    }

    private function criarLoteComItens(string $numeroLote, ?string $dataValidade, array $itens): Lote
    {
        $lote = Lote::create([
            'numero_lote'    => $numeroLote,
            'quantidade'     => 0,
            'status'         => 'ativo',
            'data_entrada'   => now()->toDateString(),
            'data_validade'  => $dataValidade,
            'descricao'      => 'Importação em lote',
            'id_produto'     => null,
            'id_localizacao' => null,
        ]);

        foreach ($itens as $it) {
            if ((int) $it['quantidade'] <= 0) continue;

            // Resolve o produto AGORA (na confirmação), de forma atômica — não no preview
            $produto = Produto::firstOrCreate(
                ['sku' => $it['sku']],
                [
                    'nome'           => $it['nome'],
                    'unidade_medida' => $it['unidade'] ?? 'UN',
                    'preco_custo'    => 0,
                    'estoque_minimo' => (int) ($it['estoque_minimo'] ?? 0),
                    'estoque_atual'  => 0,
                    'prioridade_abc' => 'C',
                    'id_categoria'   => null,
                    'id_fornecedor'  => null,
                ]
            );

            $validadeItem = $it['validade'] ?? $dataValidade;

            // Agrupa por PRODUTO + VALIDADE, não por SKU sozinho — preserva lotes com validades diferentes
            $item = ItemLote::where('id_lote', $lote->id_lote)
                ->where('id_produto', $produto->id_produto)
                ->where('data_validade', $validadeItem)
                ->first();

            if ($item) {
                $item->increment('quantidade', $it['quantidade']);
            } else {
                $item = ItemLote::create([
                    'id_lote'           => $lote->id_lote,
                    'id_produto'        => $produto->id_produto,
                    'quantidade'        => $it['quantidade'],
                    'unidade_medida'    => $it['unidade'] ?? 'UN',
                    'data_validade'     => $validadeItem,
                    'localizacao'       => null,
                    'id_centro_custo'   => $this->resolverCentroCusto($it['centro_custo'] ?? null),
                    'prioridade_abc'    => null,
                    'prioridade_manual' => false,
                ]);
            }

            $lote->increment('quantidade', $it['quantidade']);
            $produto->increment('estoque_atual', $it['quantidade']);

            Movimentacao::registrar('ENTRADA', $it['quantidade'], $lote->id_lote, $item->id_item ?? null, 'Importação via planilha Excel');
        }

        return $lote;
    }

    /**
     * Converte o texto da planilha em id_centro_custo.
     * Procura pelo código ou pelo nome (sem diferenciar maiúsculas).
     * Só vincula centros já cadastrados. Se não achar, o item fica sem centro de custo.
     */
    private function resolverCentroCusto(?string $texto): ?int
    {
        $texto = trim((string) $texto);
        if ($texto === '') return null;

        $minusculo = mb_strtolower($texto);

        $centro = \App\Models\CentroCusto::whereRaw('LOWER(codigo) = ?', [$minusculo])
            ->orWhereRaw('LOWER(nome) = ?', [$minusculo])
            ->first();

        return $centro?->id_centro_custo;
    }

    private function converterValidade(mixed $valor): ?string
    {
        if (is_null($valor) || $valor === '' || $valor === 0) return null;

        if (is_numeric($valor) && $valor > 1000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$valor)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        if (preg_match('/F[:\s]*(\d{1,2})\/(\d{4})/i', (string)$valor, $m)) {
            return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
        }

        if (preg_match('/^(\d{1,2})\/(\d{4})$/', trim((string)$valor), $m)) {
            return $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-01';
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim((string)$valor), $m)) {
            try {
                return Carbon::createFromFormat('d/m/Y', "{$m[1]}/{$m[2]}/{$m[3]}")->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse((string)$valor)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}