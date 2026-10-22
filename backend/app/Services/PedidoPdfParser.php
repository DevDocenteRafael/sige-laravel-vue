<?php

namespace App\Services;

/**
 * Lê o texto extraído do PDF "PEDIDO NR." do MXM e devolve os dados estruturados.
 * Classe pura (sem Laravel) para poder ser testada isoladamente.
 */
class PedidoPdfParser
{
    // Unidades que aparecem coladas na descrição no texto do PDF (ex.: "150,00UNBASE ...").
    // PCT/KIT vêm antes de PC/KT para não cortar a unidade pela metade.
    private const UNIDADES = 'KIT|PCT|UN|FR|PC|CX|CJ|PT|BA|BG|KT|GL|LT|KG|RL|PR|FD|CT|SC|DZ|MT|PA|TB';

    public function parse(string $texto): array
    {
        $texto = str_replace("\r", '', $texto);
        $avisos = [];

        $numero = null;
        if (preg_match('/PEDIDO\s+NR\.?\s*(\d+)/i', $texto, $m)) {
            $numero = $m[1];
        }

        $emissao = null;
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $texto, $m)) {
            $emissao = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        $cnpj = null;
        if (preg_match('/CNPJ\s*\n\s*(\d{14})/', $texto, $m)) {
            $cnpj = $m[1];
        }

        $fornecedor = null;
        if (preg_match('/VL\.TOTAL ITEM\s*IPI\s*\n([^\n]+)/u', $texto, $m)) {
            $fornecedor = trim($m[1]);
        }

        $totalGeral = null;
        if (preg_match('/CIF\s*([\d.]+,\d{2})/', $texto, $m)) {
            $totalGeral = $this->num($m[1]);
        }

        $itens = [];
        $blocos = preg_split('/(?=^ES\d{13}\s)/m', $texto);
        foreach ($blocos as $bloco) {
            $regex = '/^(ES\d{13})\s*([\d.]+,\d{2})\s*(' . self::UNIDADES . ')(.*?)\d{2}\/\d{2}\/\d{2}\s*R\$([^\n]*)/s';
            if (!preg_match($regex, $bloco, $m)) {
                continue;
            }

            preg_match_all('/[\d.]+,\d{2}/', $m[5], $nums);
            if (empty($nums[0])) {
                continue;
            }

            $quantidade = $this->num($m[2]);
            $total      = $this->num(end($nums[0])); // o último número da linha é o total do item

            $itens[] = [
                'sku'            => $m[1],
                'quantidade'     => $quantidade,
                'unidade'        => $m[3],
                'descricao'      => trim(preg_replace('/\s+/', ' ', $m[4])),
                // o valor unitário sai embaralhado no PDF, então é recalculado pelo total
                'valor_unitario' => $quantidade > 0 ? round($total / $quantidade, 2) : 0,
                'total'          => $total,
            ];
        }

        $esperados = preg_match_all('/^ES\d{13}\s/m', $texto);
        if ($esperados !== count($itens)) {
            $avisos[] = "Foram encontrados {$esperados} códigos no PDF, mas só " . count($itens) . ' itens foram lidos. Confira o pedido.';
        }

        if ($totalGeral !== null) {
            $soma = round(array_sum(array_column($itens, 'total')), 2);
            if (abs($soma - $totalGeral) > 0.01) {
                $avisos[] = 'A soma dos itens (' . number_format($soma, 2, ',', '.') . ') não bate com o total do pedido (' . number_format($totalGeral, 2, ',', '.') . ').';
            }
        }

        return [
            'numero'          => $numero,
            'data_emissao'    => $emissao,
            'fornecedor_cnpj' => $cnpj,
            'fornecedor_nome' => $fornecedor,
            'valor_total'     => $totalGeral ?? round(array_sum(array_column($itens, 'total')), 2),
            'itens'           => $itens,
            'avisos'          => $avisos,
        ];
    }

    private function num(string $valor): float
    {
        return (float) str_replace(',', '.', str_replace('.', '', $valor));
    }
}