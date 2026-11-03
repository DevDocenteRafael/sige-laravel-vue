<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Movimentacao;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportController extends Controller
{
    public function exportarProdutosCSV()
    {
        $produtos = Produto::with(['categoria', 'fornecedor'])->get();

        $csv = "SKU,Nome,Categoria,Fornecedor,Unidade,Estoque_Atual,Estoque_Minimo,Preco_Custo,Prioridade_ABC\n";
        foreach ($produtos as $p) {
            $csv .= implode(',', [
                "\"{$p->sku}\"",
                '"' . str_replace('"', '""', $p->nome) . '"',
                '"' . ($p->categoria->nome ?? '') . '"',
                '"' . ($p->fornecedor->nome ?? '') . '"',
                $p->unidade_medida,
                $p->estoque_atual,
                $p->estoque_minimo,
                $p->preco_custo,
                $p->prioridade_abc,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="produtos_' . now()->format('Ymd') . '.csv"',
        ]);
    }

    public function exportarProdutosXLSX()
    {
        $produtos = Produto::with(['categoria', 'fornecedor'])->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Produtos');

        $headers = ['SKU', 'Nome', 'Categoria', 'Fornecedor', 'Unidade', 'Estoque Atual', 'Estoque Mínimo', 'Preço Custo', 'Prioridade ABC'];
        $this->estilizarCabecalho($sheet, $headers, 'I1');

        $row = 2;
        foreach ($produtos as $p) {
            $sheet->fromArray([
                $p->sku,
                $p->nome,
                $p->categoria->nome ?? '',
                $p->fornecedor->nome ?? '',
                $p->unidade_medida,
                $p->estoque_atual,
                $p->estoque_minimo,
                $p->preco_custo,
                $p->prioridade_abc,
            ], null, "A{$row}");

            $sheet->getStyle("F{$row}:G{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');
            $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($p->estoque_atual <= $p->estoque_minimo) {
                $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('C62828');
            }

            $this->colorirCelula($sheet, "I{$row}", $this->corAbc($p->prioridade_abc));

            $row++;
        }

        $this->ajustarColunas($sheet, ['A' => 20, 'B' => 40, 'C' => 18, 'D' => 18, 'E' => 10, 'F' => 14, 'G' => 14, 'H' => 14, 'I' => 14]);
        $sheet->setAutoFilter("A1:I{$row}");

        return $this->baixarXlsx($spreadsheet, 'produtos_' . now()->format('Ymd') . '.xlsx');
    }

    public function exportarMovimentacoesCSV()
    {
        $movs = Movimentacao::with(['lote.produto'])->orderBy('data_movimentacao', 'desc')->get();

        $csv = "Data,Tipo,Produto,SKU,Quantidade,Observacao\n";
        foreach ($movs as $m) {
            $produto = $m->lote->produto ?? null;
            $csv .= implode(',', [
                '"' . ($m->data_movimentacao ?? '') . '"',
                '"' . $m->tipo . '"',
                '"' . ($produto->nome ?? '') . '"',
                '"' . ($produto->sku  ?? '') . '"',
                $m->quantidade,
                '"' . str_replace('"', '""', $m->observacao ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="movimentacoes_' . now()->format('Ymd') . '.csv"',
        ]);
    }

    public function exportarMovimentacoesXLSX()
    {
        $movs = Movimentacao::with(['lote.produto'])->orderBy('data_movimentacao', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Movimentações');

        $headers = ['Data', 'Tipo', 'Produto', 'SKU', 'Quantidade', 'Observação'];
        $this->estilizarCabecalho($sheet, $headers, 'F1');

        $row = 2;
        foreach ($movs as $m) {
            $produto = $m->lote->produto ?? null;
            $sheet->fromArray([
                $m->data_movimentacao ?? '',
                $m->tipo,
                $produto->nome ?? '',
                $produto->sku  ?? '',
                $m->quantidade,
                $m->observacao ?? '',
            ], null, "A{$row}");

            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $tipoNorm = mb_strtolower((string) $m->tipo);
            $corTipo = str_contains($tipoNorm, 'entrada') ? '2E7D32' : (str_contains($tipoNorm, 'saida') || str_contains($tipoNorm, 'saída') ? 'C62828' : '64748B');
            $this->colorirCelula($sheet, "B{$row}", $corTipo);

            $row++;
        }

        $this->ajustarColunas($sheet, ['A' => 14, 'B' => 12, 'C' => 32, 'D' => 20, 'E' => 12, 'F' => 40]);
        $sheet->setAutoFilter("A1:F{$row}");

        return $this->baixarXlsx($spreadsheet, 'movimentacoes_' . now()->format('Ymd') . '.xlsx');
    }


    private function estilizarCabecalho(Worksheet $sheet, array $headers, string $ultimaCelula): void
    {
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle("A1:{$ultimaCelula}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$ultimaCelula}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('3A6EA5');
        $sheet->getStyle("A1:{$ultimaCelula}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(20);
    }

    private function colorirCelula(Worksheet $sheet, string $celula, string $corRGB): void
    {
        $sheet->getStyle($celula)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($celula)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($corRGB);
        $sheet->getStyle($celula)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function corAbc(?string $prioridade): string
    {
        return match (mb_strtoupper((string) $prioridade)) {
            'A' => '2E7D32',
            'B' => 'EF6C00',
            'C' => 'C62828',
            default => '64748B',
        };
    }

    private function ajustarColunas(Worksheet $sheet, array $larguras): void
    {
        foreach ($larguras as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A2');
    }

    private function baixarXlsx(Spreadsheet $spreadsheet, string $nomeArquivo)
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nomeArquivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}