<?php

/*
 * Mapeamento das colunas da planilha de pedidos do SIGE <-> layout do pedido do MXM.
 *
 * ATENÇÃO: ainda não existe uma planilha oficial de exemplo do MXM. Os títulos abaixo
 * seguem as colunas do PDF "PEDIDO NR." do MXM (CÓDIGO, QTDE., UN., DESCRIÇÃO, VL. UNITÁRIO,
 * VL. TOTAL ITEM). Quando a planilha real chegar, basta ajustar os textos deste arquivo:
 * a importação e a exportação leem tudo daqui. Os títulos são comparados sem acento,
 * sem pontuação e sem diferenciar maiúsculas de minúsculas.
 */
return [
    // Cabeçalho do pedido: campo do SIGE => rótulo na coluna A (o valor fica na coluna B)
    'cabecalho' => [
        'numero'          => 'PEDIDO NR.',
        'fornecedor_nome' => 'FORNECEDOR',
        'fornecedor_cnpj' => 'CNPJ',
        'data_emissao'    => 'DATA DE EMISSÃO',
        'observacao'      => 'OBSERVAÇÃO',
    ],

    // Itens: campo do SIGE => título da coluna
    'colunas' => [
        'sku'            => 'CÓDIGO',
        'quantidade'     => 'QTDE.',
        'unidade'        => 'UN.',
        'descricao'      => 'DESCRIÇÃO',
        'valor_unitario' => 'VL. UNITÁRIO',
        'valor_total'    => 'VL. TOTAL ITEM',
    ],

    // Tamanho máximo de itens lidos por planilha (proteção contra arquivos enormes)
    'max_itens' => 500,
];
