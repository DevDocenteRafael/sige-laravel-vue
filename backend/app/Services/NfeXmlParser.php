<?php

namespace App\Services;

/**
 * Lê o XML de uma NF-e (nfeProc ou NFe) e devolve os dados estruturados.
 * Classe pura (sem Laravel) para poder ser testada isoladamente.
 */
class NfeXmlParser
{
    private const NS = 'http://www.portalfiscal.inf.br/nfe';

    public function parse(string $conteudo): array
    {
        $usarErrosInternos = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($conteudo, 'SimpleXMLElement', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($usarErrosInternos);

        if ($xml === false) {
            throw new \InvalidArgumentException('O arquivo enviado não é um XML válido.');
        }

        $xml->registerXPathNamespace('n', self::NS);
        $infs = $xml->xpath('//n:infNFe');
        if (empty($infs)) {
            throw new \InvalidArgumentException('Este XML não parece ser de uma NF-e (infNFe não encontrado).');
        }
        $inf = $infs[0];

        $chave = preg_replace('/\D/', '', (string) $inf['Id']);
        if (strlen($chave) !== 44) {
            $chave = preg_replace('/\D/', '', (string) ($this->valor($xml, '//n:protNFe/n:infProt/n:chNFe') ?? ''));
        }
        if (strlen($chave) !== 44) {
            throw new \InvalidArgumentException('Não encontrei a chave de acesso (44 dígitos) neste XML.');
        }

        $infCpl = $this->valor($inf, 'n:infAdic/n:infCpl') ?? '';

        $itens = [];
        foreach ($inf->xpath('n:det') as $det) {
            $quantidade = (float) $this->valor($det, 'n:prod/n:qCom');
            $itens[] = [
                'codigo'         => $this->valor($det, 'n:prod/n:cProd') ?? '',
                'descricao'      => $this->valor($det, 'n:prod/n:xProd') ?? '',
                'unidade'        => $this->valor($det, 'n:prod/n:uCom'),
                'quantidade'     => $quantidade,
                'valor_unitario' => (float) $this->valor($det, 'n:prod/n:vUnCom'),
                'numero_pedido'  => $this->valor($det, 'n:prod/n:xPed'),
                'lote'           => $this->valor($det, 'n:prod/n:rastro/n:nLote'),
                'validade'       => $this->valor($det, 'n:prod/n:rastro/n:dVal'),
            ];
        }

        if (empty($itens)) {
            throw new \InvalidArgumentException('Esta NF-e não tem itens.');
        }

        // O número do pedido vem no texto "PEDIDO DE COMPRA: 036072" ou no campo xPed dos itens
        $numeroPedido = null;
        if (preg_match('/PEDIDO\s+DE\s+COMPRA\s*:?\s*(\d+)/iu', $infCpl, $m)) {
            $numeroPedido = $m[1];
        } else {
            foreach ($itens as $item) {
                if (!empty($item['numero_pedido'])) {
                    $numeroPedido = $item['numero_pedido'];
                    break;
                }
            }
        }

        $emissao = $this->valor($inf, 'n:ide/n:dhEmi') ?? $this->valor($inf, 'n:ide/n:dEmi');

        return [
            'chave'           => $chave,
            'numero'          => ltrim($this->valor($inf, 'n:ide/n:nNF') ?? '', '0') ?: '0',
            'serie'           => $this->valor($inf, 'n:ide/n:serie'),
            'data_emissao'    => $emissao ? substr($emissao, 0, 10) : null,
            'fornecedor_cnpj' => preg_replace('/\D/', '', $this->valor($inf, 'n:emit/n:CNPJ') ?? ''),
            'fornecedor_nome' => $this->valor($inf, 'n:emit/n:xNome'),
            'valor_total'     => (float) $this->valor($inf, 'n:total/n:ICMSTot/n:vNF'),
            'numero_pedido'   => $numeroPedido,
            'itens'           => $itens,
        ];
    }

    private function valor(\SimpleXMLElement $no, string $caminho): ?string
    {
        $no->registerXPathNamespace('n', self::NS);
        $r = $no->xpath($caminho);

        return $r ? trim((string) $r[0]) : null;
    }
}