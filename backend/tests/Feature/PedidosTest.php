<?php

namespace Tests\Feature;

use App\Models\NotaRecebimento;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * ATENÇÃO: as migrations do projeto têm comandos específicos do MySQL (ALTER TABLE ... MODIFY),
 * então estes testes precisam de um banco MySQL de teste (ex.: DB_CONNECTION=mysql e um
 * DB_DATABASE separado de desenvolvimento). O phpunit.xml padrão usa sqlite em memória e
 * não consegue rodar todas as migrations.
 */
class PedidosTest extends TestCase
{
    use RefreshDatabase;

    private function logar(string $perfil = 'operador'): User
    {
        $user = User::factory()->create(['perfil' => $perfil]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'numero'          => '036072',
            'fornecedor_nome' => 'Fornecedor Teste',
            'fornecedor_cnpj' => '37.937.325/0001-05',
            'data_emissao'    => '2026-10-16',
            'itens'           => [
                ['sku' => 'ES001', 'descricao' => 'Base', 'unidade' => 'un', 'quantidade_pedida' => 10, 'valor_unitario' => 5.5],
                ['sku' => 'ES002', 'descricao' => 'Cola', 'unidade' => 'FR', 'quantidade_pedida' => 2.5, 'valor_unitario' => 10],
            ],
        ], $extra);
    }

    public function test_cria_pedido_manual_e_calcula_total(): void
    {
        $this->logar();

        $r = $this->postJson('/api/pedidos', $this->payload());

        $r->assertCreated()->assertJsonPath('numero', '036072')->assertJsonPath('editavel', true);
        $this->assertEquals(80.0, Pedido::first()->valor_total);                 // 10*5,5 + 2,5*10
        $this->assertSame('37937325000105', Pedido::first()->fornecedor_cnpj);   // pontuação removida
        $this->assertSame('UN', PedidoItem::where('sku', 'ES001')->value('unidade'));
    }

    public function test_valida_campos_obrigatorios_quantidade_e_duplicados(): void
    {
        $this->logar();

        $this->postJson('/api/pedidos', $this->payload(['itens' => []]))->assertStatus(422);
        $this->postJson('/api/pedidos', $this->payload(['numero' => '']))->assertStatus(422);

        $zero = $this->payload();
        $zero['itens'][0]['quantidade_pedida'] = 0;
        $this->postJson('/api/pedidos', $zero)->assertStatus(422);

        $repetido = $this->payload();
        $repetido['itens'][1]['sku'] = 'ES001';
        $this->postJson('/api/pedidos', $repetido)->assertStatus(422);

        $this->postJson('/api/pedidos', $this->payload())->assertCreated();
        $this->postJson('/api/pedidos', $this->payload())->assertStatus(422)   // número já existe
            ->assertJsonValidationErrors('numero');
    }

    public function test_edita_itens_enquanto_aberto_e_bloqueia_depois(): void
    {
        $this->logar();
        $id = $this->postJson('/api/pedidos', $this->payload())->json('id_pedido');
        $idItem = PedidoItem::where('sku', 'ES001')->value('id_pedido_item');

        $this->postJson("/api/pedidos/{$id}/itens", ['sku' => 'ES003', 'descricao' => 'Novo', 'quantidade_pedida' => 4, 'valor_unitario' => 2])
            ->assertCreated();
        $this->putJson("/api/pedidos/{$id}/itens/{$idItem}", ['sku' => 'ES001', 'descricao' => 'Base', 'quantidade_pedida' => 20, 'valor_unitario' => 5.5])
            ->assertOk();
        $this->deleteJson("/api/pedidos/{$id}/itens/" . PedidoItem::where('sku', 'ES003')->value('id_pedido_item'))->assertOk();

        // Com nota vinculada, o pedido deixa de ser editável
        NotaRecebimento::create([
            'chave_acesso' => str_repeat('1', 44), 'numero' => '1', 'id_pedido' => $id, 'status' => 'pendente',
        ]);

        $this->postJson("/api/pedidos/{$id}/itens", ['sku' => 'ES009', 'descricao' => 'X', 'quantidade_pedida' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('pedido');
    }

    public function test_nao_remove_o_ultimo_item(): void
    {
        $this->logar();
        $id = $this->postJson('/api/pedidos', $this->payload(['itens' => [
            ['sku' => 'ES001', 'descricao' => 'Base', 'quantidade_pedida' => 1],
        ]]))->json('id_pedido');

        $this->deleteJson("/api/pedidos/{$id}/itens/" . PedidoItem::first()->id_pedido_item)->assertStatus(422);
    }

    public function test_exporta_planilha_do_pedido(): void
    {
        $this->logar();
        $id = $this->postJson('/api/pedidos', $this->payload())->json('id_pedido');

        $r = $this->get("/api/pedidos/{$id}/exportar");

        $r->assertOk();
        $this->assertStringContainsString('spreadsheetml', $r->headers->get('Content-Type'));
    }

    public function test_importa_planilha_com_previa_e_rejeita_planilha_invalida(): void
    {
        $this->logar();

        $ok = \Illuminate\Http\UploadedFile::fake()->createWithContent('p.csv', implode("\n", [
            'PEDIDO NR.,900', 'FORNECEDOR,ACME', '', 'CÓDIGO,QTDE.,UN.,DESCRIÇÃO,VL. UNITÁRIO', 'A1,3,UN,Item,2',
        ]));
        $this->postJson('/api/pedidos/importar-planilha', ['arquivo' => $ok, 'previa' => 1])
            ->assertOk()->assertJsonPath('previa', true)->assertJsonPath('total_itens', 1);
        $this->assertSame(0, Pedido::count());                                    // prévia não grava

        $this->postJson('/api/pedidos/importar-planilha', ['arquivo' => $ok])->assertCreated();
        $this->assertSame(1, Pedido::count());

        $dup = \Illuminate\Http\UploadedFile::fake()->createWithContent('p.csv', implode("\n", [
            'PEDIDO NR.,900', '', 'CÓDIGO,QTDE.,UN.,DESCRIÇÃO,VL. UNITÁRIO', 'A1,3,UN,Item,2',
        ]));
        $this->postJson('/api/pedidos/importar-planilha', ['arquivo' => $dup])
            ->assertStatus(422)->assertJsonPath('erros.0', 'O pedido 900 já foi importado.');

        $ruim = \Illuminate\Http\UploadedFile::fake()->createWithContent('p.csv', "A,B\n1,2\n");
        $this->postJson('/api/pedidos/importar-planilha', ['arquivo' => $ruim])->assertStatus(422);
    }

    public function test_visualizador_nao_cria_e_sem_login_nao_acessa(): void
    {
        $this->getJson('/api/pedidos')->assertUnauthorized();

        $this->logar('visualizador');
        $this->getJson('/api/pedidos')->assertOk();
        $this->postJson('/api/pedidos', $this->payload())->assertForbidden();
        $this->postJson('/api/pedidos/importar-planilha', [])->assertForbidden();
    }
}
