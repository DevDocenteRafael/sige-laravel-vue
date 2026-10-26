<?php

namespace Tests\Unit;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Services\PedidoPlanilha;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Não usa banco: só o serviço que lê/gera a planilha (precisa do Laravel apenas por causa do config()).
 */
class PedidoPlanilhaTest extends TestCase
{
    private function pedidoDeExemplo(): Pedido
    {
        $pedido = new Pedido([
            'numero'          => '036072',
            'fornecedor_nome' => 'KARLA KAROLINE FONTES MENESES',
            'fornecedor_cnpj' => '37937325000105',
            'data_emissao'    => '2026-10-16',
            'observacao'      => 'AQUISIÇÃO DE INSUMOS',
        ]);
        $pedido->setRelation('itens', collect([
            new PedidoItem(['sku' => 'ES0260000000045', 'descricao' => 'BASE EM BASTAO', 'unidade' => 'UN', 'quantidade_pedida' => 150, 'valor_unitario' => 58]),
            new PedidoItem(['sku' => 'ES0260000000099', 'descricao' => 'COLA PARA CILIOS', 'unidade' => 'FR', 'quantidade_pedida' => 20.5, 'valor_unitario' => 12.34]),
        ]));

        return $pedido;
    }

    private function salvarXlsx(Pedido $pedido): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'ped') . '.xlsx';
        (new Xlsx((new PedidoPlanilha())->exportar([$pedido])))->save($caminho);

        return $caminho;
    }

    public function test_exportar_e_importar_de_volta_preserva_os_dados(): void
    {
        $lido = (new PedidoPlanilha())->ler($this->salvarXlsx($this->pedidoDeExemplo()));

        $this->assertSame([], $lido['erros']);
        $this->assertSame('036072', $lido['dados']['numero']);          // zero à esquerda preservado
        $this->assertSame('37937325000105', $lido['dados']['fornecedor_cnpj']);
        $this->assertSame('2026-10-16', $lido['dados']['data_emissao']);
        $this->assertCount(2, $lido['dados']['itens']);
        $this->assertSame('ES0260000000099', $lido['dados']['itens'][1]['sku']);
        $this->assertEquals(20.5, $lido['dados']['itens'][1]['quantidade']);   // quantidade fracionada
        $this->assertEquals(12.34, $lido['dados']['itens'][1]['valor_unitario']);
    }

    public function test_planilha_sem_colunas_obrigatorias_gera_erro_claro(): void
    {
        $caminho = tempnam(sys_get_temp_dir(), 'ped') . '.csv';
        file_put_contents($caminho, "PEDIDO NR.,123\nFOO,BAR\n1,2\n");

        $lido = (new PedidoPlanilha())->ler($caminho);

        $this->assertNotEmpty($lido['erros']);
        $this->assertStringContainsString('linha de títulos', $lido['erros'][0]);
        $this->assertSame([], $lido['dados']);
    }

    public function test_valida_quantidade_codigo_repetido_e_numero_do_pedido(): void
    {
        $caminho = tempnam(sys_get_temp_dir(), 'ped') . '.csv';
        file_put_contents($caminho, implode("\n", [
            'FORNECEDOR,ACME',
            '',
            'CÓDIGO,QTDE.,UN.,DESCRIÇÃO,VL. UNITÁRIO',
            'A1,0,UN,Item zero,1,00',
            'A2,abc,UN,Item texto,1',
            'A3,5,UN,Item bom,"1.234,50"',
            'A3,5,UN,Item repetido,1',
        ]));

        $erros = implode(' | ', (new PedidoPlanilha())->ler($caminho)['erros']);

        $this->assertStringContainsString('número do pedido', $erros);
        $this->assertStringContainsString('Linha 4: quantidade inválida', $erros);
        $this->assertStringContainsString('Linha 5: quantidade inválida', $erros);
        $this->assertStringContainsString('código A3 repetido', $erros);
    }

    public function test_aceita_numero_no_formato_brasileiro(): void
    {
        $caminho = tempnam(sys_get_temp_dir(), 'ped') . '.csv';
        file_put_contents($caminho, implode("\n", [
            'PEDIDO NR.,777',
            '',
            'CÓDIGO,QTDE.,UN.,DESCRIÇÃO,VL. UNITÁRIO',
            'A3,"1.000,50",UN,Item,"1.234,50"',
        ]));

        $lido = (new PedidoPlanilha())->ler($caminho);

        $this->assertSame([], $lido['erros']);
        $this->assertEquals(1000.5, $lido['dados']['itens'][0]['quantidade']);
        $this->assertEquals(1234.5, $lido['dados']['itens'][0]['valor_unitario']);
    }

    public function test_arquivo_ilegivel_nao_estoura_excecao(): void
    {
        $caminho = tempnam(sys_get_temp_dir(), 'ped') . '.xlsx';
        file_put_contents($caminho, 'isto não é uma planilha');

        $lido = (new PedidoPlanilha())->ler($caminho);

        $this->assertNotEmpty($lido['erros']);
    }
}
