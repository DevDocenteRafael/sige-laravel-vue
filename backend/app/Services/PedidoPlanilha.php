<?php

namespace App\Services;

use App\Models\Pedido;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Lê e gera a planilha de pedido no layout configurado em config/pedido_mxm.php.
 * Não sabe nada de banco: recebe e devolve arrays/modelos já carregados.
 */
class PedidoPlanilha
{
    /**
     * Gera uma planilha com uma aba por pedido.
     *
     * @param iterable<Pedido> $pedidos (com a relação itens carregada)
     */
    public function exportar(iterable $pedidos): Spreadsheet
    {
        $cab    = config('pedido_mxm.cabecalho');
        $colunas = config('pedido_mxm.colunas');

        $planilha = new Spreadsheet();
        $planilha->removeSheetByIndex(0);

        $usados = [];
        foreach ($pedidos as $pedido) {
            $titulo = substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', (string) $pedido->numero), 0, 28) ?: 'Pedido';
            while (in_array($titulo, $usados, true)) {
                $titulo = substr($titulo, 0, 25) . '-' . (count($usados) + 1);
            }
            $usados[] = $titulo;

            $aba = $planilha->createSheet();
            $aba->setTitle($titulo);

            $linha = 1;
            $dados = [
                'numero'          => $pedido->numero,
                'fornecedor_nome' => $pedido->fornecedor_nome,
                'fornecedor_cnpj' => $pedido->fornecedor_cnpj,
                'data_emissao'    => $pedido->data_emissao?->format('d/m/Y'),
                'observacao'      => $pedido->observacao,
            ];
            foreach ($cab as $campo => $rotulo) {
                $aba->setCellValue("A{$linha}", $rotulo);
                // Texto explícito: evita que número do pedido (036072) e CNPJ percam os zeros à esquerda
                $aba->setCellValueExplicit("B{$linha}", (string) ($dados[$campo] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $aba->getStyle("A{$linha}")->getFont()->setBold(true);
                $linha++;
            }

            $linha++;                       // linha em branco
            $linhaCabecalho = $linha;
            $letras = [];
            $i = 1;
            foreach ($colunas as $campo => $titulo) {
                $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i++);
                $letras[$campo] = $letra;
                $aba->setCellValue("{$letra}{$linhaCabecalho}", $titulo);
            }
            $aba->getStyle("A{$linhaCabecalho}:" . end($letras) . $linhaCabecalho)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC20E']],
            ]);

            $linha = $linhaCabecalho + 1;
            foreach ($pedido->itens as $item) {
                $valores = [
                    'sku'            => $item->sku,
                    'quantidade'     => (float) $item->quantidade_pedida,
                    'unidade'        => $item->unidade,
                    'descricao'      => $item->descricao,
                    'valor_unitario' => (float) $item->valor_unitario,
                    'valor_total'    => round((float) $item->quantidade_pedida * (float) $item->valor_unitario, 2),
                ];
                foreach ($letras as $campo => $letra) {
                    if ($campo === 'sku') {
                        $aba->setCellValueExplicit("{$letra}{$linha}", (string) $valores[$campo], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    } else {
                        $aba->setCellValue("{$letra}{$linha}", $valores[$campo]);
                    }
                }
                $linha++;
            }

            foreach (['quantidade', 'valor_unitario', 'valor_total'] as $campo) {
                if (isset($letras[$campo])) {
                    $aba->getStyle("{$letras[$campo]}" . ($linhaCabecalho + 1) . ":{$letras[$campo]}{$linha}")
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
            foreach ($letras as $letra) {
                $aba->getColumnDimension($letra)->setAutoSize(true);
            }
            $aba->getStyle('A1:B5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        if ($planilha->getSheetCount() === 0) {
            $planilha->createSheet()->setTitle('Pedidos');
        }
        $planilha->setActiveSheetIndex(0);

        return $planilha;
    }

    /**
     * Lê a primeira aba da planilha. Nunca grava nada.
     *
     * @return array{dados: array, erros: array<int, string>}
     */
    public function ler(string $caminho): array
    {
        $erros = [];

        try {
            $planilha = IOFactory::load($caminho);
        } catch (\Throwable $e) {
            return ['dados' => [], 'erros' => ['Não foi possível ler a planilha. Envie um arquivo .xlsx, .xls ou .csv válido.']];
        }

        $linhas = $planilha->getSheet(0)->toArray(null, true, false, false);
        $cab    = config('pedido_mxm.cabecalho');
        $cols   = config('pedido_mxm.colunas');
        $limite = (int) config('pedido_mxm.max_itens', 500);

        // 1) Cabeçalho (rótulo na coluna A, valor na coluna B) e linha de títulos dos itens
        $dados = array_fill_keys(array_keys($cab), null);
        $rotulos = [];
        foreach ($cab as $campo => $rotulo) {
            $rotulos[$this->norm($rotulo)] = $campo;
        }
        $titulos = [];
        foreach ($cols as $campo => $titulo) {
            $titulos[$this->norm($titulo)] = $campo;
        }

        $linhaTitulos = null;
        $mapa = [];
        foreach ($linhas as $i => $linha) {
            $primeira = $this->norm($linha[0] ?? '');

            if (isset($rotulos[$primeira]) && $dados[$rotulos[$primeira]] === null) {
                $dados[$rotulos[$primeira]] = trim((string) ($linha[1] ?? ''));
            }

            $achados = [];
            foreach ($linha as $col => $celula) {
                $n = $this->norm($celula);
                if ($n !== '' && isset($titulos[$n])) {
                    $achados[$titulos[$n]] = $col;
                }
            }
            if (isset($achados['sku'], $achados['quantidade'], $achados['descricao'])) {
                $linhaTitulos = $i;
                $mapa = $achados;
                break;
            }
        }

        if ($linhaTitulos === null) {
            $faltam = implode(', ', array_map(fn ($c) => $cols[$c], ['sku', 'quantidade', 'descricao']));
            return ['dados' => [], 'erros' => ["Não encontrei a linha de títulos dos itens. As colunas obrigatórias são: {$faltam}."]];
        }

        // 2) Cabeçalho do pedido
        $numero = preg_replace('/\s+/', '', (string) $dados['numero']);
        if ($numero === '') {
            $erros[] = "Informe o número do pedido ({$cab['numero']}) no cabeçalho da planilha.";
        } elseif (mb_strlen($numero) > 30) {
            $erros[] = 'O número do pedido passa de 30 caracteres.';
        }

        $cnpj = preg_replace('/\D/', '', (string) $dados['fornecedor_cnpj']);
        if ($cnpj !== '' && strlen($cnpj) !== 14) {
            $erros[] = 'O CNPJ do fornecedor precisa ter 14 dígitos.';
        }

        $emissao = null;
        if (($dados['data_emissao'] ?? '') !== '') {
            $emissao = $this->data($dados['data_emissao']);
            if (!$emissao) {
                $erros[] = 'Data de emissão inválida. Use o formato dd/mm/aaaa.';
            }
        }

        // 3) Itens
        $itens = [];
        $vistos = [];
        $qtdLinhas = 0;
        for ($i = $linhaTitulos + 1; $i < count($linhas); $i++) {
            $linha = $linhas[$i];
            $sku   = trim((string) ($linha[$mapa['sku']] ?? ''));
            $desc  = trim((string) ($linha[$mapa['descricao']] ?? ''));
            $qtdBr = $linha[$mapa['quantidade']] ?? null;

            if ($sku === '' && $desc === '' && ($qtdBr === null || $qtdBr === '')) {
                continue;               // linha em branco
            }

            $numLinha = $i + 1;
            if (++$qtdLinhas > $limite) {
                $erros[] = "A planilha passa do limite de {$limite} itens.";
                break;
            }

            if ($sku === '') {
                $erros[] = "Linha {$numLinha}: código do produto ausente.";
                continue;
            }
            if (mb_strlen($sku) > 30) {
                $erros[] = "Linha {$numLinha}: código passa de 30 caracteres.";
            }
            if ($desc === '') {
                $erros[] = "Linha {$numLinha}: descrição ausente.";
            }
            if (isset($vistos[$sku])) {
                $erros[] = "Linha {$numLinha}: código {$sku} repetido (já aparece na linha {$vistos[$sku]}).";
                continue;
            }
            $vistos[$sku] = $numLinha;

            $qtd = $this->numero($qtdBr);
            if ($qtd === null || $qtd <= 0) {
                $erros[] = "Linha {$numLinha}: quantidade inválida (precisa ser um número maior que zero).";
            }

            $valorBruto = isset($mapa['valor_unitario']) ? ($linha[$mapa['valor_unitario']] ?? null) : null;
            $valor = ($valorBruto === null || $valorBruto === '') ? 0.0 : $this->numero($valorBruto);
            if ($valor === null || $valor < 0) {
                $erros[] = "Linha {$numLinha}: valor unitário inválido.";
                $valor = 0.0;
            }

            $unidade = isset($mapa['unidade']) ? mb_strtoupper(trim((string) ($linha[$mapa['unidade']] ?? ''))) : '';
            if (mb_strlen($unidade) > 10) {
                $erros[] = "Linha {$numLinha}: unidade passa de 10 caracteres.";
            }

            $itens[] = [
                'sku'            => $sku,
                'descricao'      => $desc,
                'unidade'        => $unidade !== '' ? $unidade : null,
                'quantidade'     => $qtd ?? 0,
                'valor_unitario' => round($valor, 2),
            ];
        }

        if (empty($itens) && empty($erros)) {
            $erros[] = 'A planilha não tem nenhum item.';
        }

        return [
            'dados' => [
                'numero'          => $numero,
                'fornecedor_nome' => ($dados['fornecedor_nome'] ?? '') !== '' ? $dados['fornecedor_nome'] : null,
                'fornecedor_cnpj' => $cnpj !== '' ? $cnpj : null,
                'data_emissao'    => $emissao,
                'observacao'      => ($dados['observacao'] ?? '') !== '' ? $dados['observacao'] : null,
                'itens'           => $itens,
            ],
            'erros' => $erros,
        ];
    }

    // Aceita número do Excel (1234.5) e texto brasileiro ("1.234,50"); null se não for número
    private function numero(mixed $valor): ?float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }
        $t = trim((string) $valor);
        if ($t === '') {
            return null;
        }
        $t = preg_replace('/\s+|R\$/', '', $t);
        if (str_contains($t, ',')) {
            $t = str_replace('.', '', $t);
            $t = str_replace(',', '.', $t);
        }
        return is_numeric($t) ? (float) $t : null;
    }

    private function data(string $texto): ?string
    {
        $texto = trim($texto);
        try {
            // Data digitada como data no Excel chega como número de série (ex.: 46311)
            if (ctype_digit($texto) && (int) $texto > 20000 && (int) $texto < 80000) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $texto)->format('Y-m-d');
            }
            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $texto, $m)) {
                return Carbon::createFromFormat('!d/m/Y', $texto)->format('Y-m-d');
            }
            if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $texto)) {
                return Carbon::createFromFormat('!Y-m-d', $texto)->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    // Normaliza títulos: sem acento, sem pontuação, maiúsculas
    private function norm(mixed $texto): string
    {
        $t = mb_strtoupper(trim((string) $texto));
        $t = strtr($t, ['Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I',
                        'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ç' => 'C']);
        return preg_replace('/[^A-Z0-9]/', '', $t);
    }
}
