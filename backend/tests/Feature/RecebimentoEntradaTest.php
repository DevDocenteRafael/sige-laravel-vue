<?php

namespace Tests\Feature;

use App\Jobs\RecalcularAbcJob;
use App\Models\ItemLote;
use App\Models\Lote;
use App\Models\Movimentacao;
use App\Models\NotaItem;
use App\Models\NotaRecebimento;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Entrada de estoque a partir da conferência da nota.
 * Precisa de banco MySQL de teste (as migrations usam ALTER TABLE ... MODIFY).
 */
class RecebimentoEntradaTest extends TestCase
{
    use RefreshDatabase;

    private Produto $produto;
    private Pedido $pedido;
    private PedidoItem $pedidoItem;
    private NotaRecebimento $nota;
    private NotaItem $notaItem;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Sanctum::actingAs(User::factory()->create(['perfil' => 'operador']));
    }

    /** Monta produto + pedido (100 un.) + nota com um item conferido ("ok"). */
    private function montar(float $qtdNota = 10, string $cnpjNota = '37937325000105'): void
    {
        $this->produto = Produto::create([
            'nome' => 'Base facial', 'sku' => 'ES001', 'unidade_medida' => 'UN', 'estoque_atual' => 0,
        ]);
        $this->pedido = Pedido::create([
            'numero' => '036072', 'fornecedor_nome' => 'Fornecedor', 'fornecedor_cnpj' => '37937325000105',
            'status' => 'aberto', 'valor_total' => 0,
        ]);
        $this->pedidoItem = PedidoItem::create([
            'id_pedido' => $this->pedido->id_pedido, 'id_produto' => $this->produto->id_produto,
            'sku' => 'ES001', 'descricao' => 'Base', 'unidade' => 'UN',
            'quantidade_pedida' => 100, 'valor_unitario' => 12,
        ]);
        $this->nota = NotaRecebimento::create([
            'chave_acesso' => str_repeat('2', 44), 'numero' => '8076', 'fornecedor_cnpj' => $cnpjNota,
            'fornecedor_nome' => 'Fornecedor', 'id_pedido' => $this->pedido->id_pedido,
            'numero_pedido' => '036072', 'status' => 'pendente',
        ]);
        $this->notaItem = NotaItem::create([
            'id_nota' => $this->nota->id_nota, 'codigo_fornecedor' => '2000000001545', 'descricao' => 'BASE FACIAL',
            'unidade' => 'UN', 'quantidade' => $qtdNota, 'valor_unitario' => 12,
            'id_produto' => $this->produto->id_produto, 'id_pedido_item' => $this->pedidoItem->id_pedido_item,
            'status' => 'ok',
        ]);
    }

    private function entrada(array $extra = [])
    {
        return $this->postJson("/api/recebimentos/{$this->nota->id_nota}/entrada", [
            'itens' => [array_merge(['id_nota_item' => $this->notaItem->id_nota_item], $extra)],
        ]);
    }

    public function test_entrada_registra_lote_validade_localizacao_prioridade_e_saldo(): void
    {
        $this->montar(10);

        $this->entrada([
            'numero_lote' => 'L-2026-01', 'data_validade' => '2027-03-31',
            'localizacao' => '1 A 3', 'prioridade_abc' => 'B',
        ])->assertOk()->assertJsonPath('entrada.entraram', 1);

        $lote = Lote::where('numero_lote', 'L-2026-01')->firstOrFail();
        $item = ItemLote::where('id_lote', $lote->id_lote)->firstOrFail();

        $this->assertSame('1 A 3', $item->localizacao);
        $this->assertSame('2027-03-31', $item->data_validade->format('Y-m-d'));
        $this->assertSame('B', $item->prioridade_abc);
        $this->assertTrue((bool) $item->prioridade_manual);
        $this->assertEquals(10, $this->produto->fresh()->estoque_atual);
        $this->assertEquals(10, $this->pedidoItem->fresh()->quantidade_recebida);
        $this->assertSame('entrou', $this->notaItem->fresh()->status);
        $this->assertSame('conferida', $this->nota->fresh()->status);
        $this->assertSame('parcial', $this->pedido->fresh()->status);   // 10 de 100 recebidos
        $this->assertSame(1, Movimentacao::where('tipo', 'ENTRADA')->count());
        Queue::assertPushed(RecalcularAbcJob::class);
    }

    public function test_sem_lote_informado_usa_lote_padrao_da_nota_e_prioridade_automatica(): void
    {
        $this->montar(5);

        $this->entrada()->assertOk();

        $this->assertSame(1, Lote::where('numero_lote', "NF8076-{$this->nota->id_nota}")->count());
        $this->assertFalse((bool) ItemLote::firstOrFail()->prioridade_manual);
    }

    public function test_nao_da_entrada_duas_vezes_no_mesmo_item(): void
    {
        $this->montar(10);

        $this->entrada()->assertOk()->assertJsonPath('entrada.entraram', 1);
        $segunda = $this->entrada()->assertOk();

        $segunda->assertJsonPath('entrada.entraram', 0);
        $this->assertSame('Este item já deu entrada no estoque.', $segunda->json('entrada.ignorados.0.motivo'));
        $this->assertEquals(10, $this->produto->fresh()->estoque_atual);
        $this->assertSame(1, Movimentacao::where('tipo', 'ENTRADA')->count());
        $this->assertSame(1, ItemLote::count());
    }

    public function test_quantidade_fracionada_nao_entra_no_estoque(): void
    {
        $this->montar(2.5);

        $r = $this->entrada()->assertOk();

        $r->assertJsonPath('entrada.entraram', 0);
        $this->assertStringContainsString('fracionada', $r->json('entrada.ignorados.0.motivo'));
        $this->assertEquals(0, $this->produto->fresh()->estoque_atual);
        $this->assertSame(0, ItemLote::count());
        $this->assertSame('ok', $this->notaItem->fresh()->status);   // continua aguardando decisão
    }

    public function test_quantidade_acima_do_saldo_do_pedido_vira_divergente(): void
    {
        $this->montar(150);   // pedido tem 100

        $r = $this->entrada()->assertOk();

        $r->assertJsonPath('entrada.entraram', 0);
        $this->assertSame('divergente', $this->notaItem->fresh()->status);
        $this->assertEquals(0, $this->produto->fresh()->estoque_atual);
    }

    public function test_cnpj_diferente_do_pedido_bloqueia_a_nota_inteira(): void
    {
        $this->montar(10, '11222333000181');

        $this->entrada()->assertStatus(422);

        $this->assertEquals(0, $this->produto->fresh()->estoque_atual);
        $this->assertSame(0, ItemLote::count());
    }

    public function test_prioridade_invalida_e_rejeitada(): void
    {
        $this->montar(10);

        $this->entrada(['prioridade_abc' => 'Z'])->assertStatus(422);

        $this->assertSame(0, ItemLote::count());
    }

    public function test_pendencias_lista_nota_com_item_divergente_e_o_motivo(): void
    {
        $this->montar(150);
        $this->entrada();

        $r = $this->getJson('/api/recebimentos/pendentes')->assertOk();

        $r->assertJsonPath('notas.0.numero', '8076');
        $this->assertStringContainsString('acima do saldo', implode(' ', $r->json('notas.0.motivos')));
        $r->assertJsonPath('pedidos.0.numero', '036072');
    }

    public function test_visualizador_nao_da_entrada(): void
    {
        $this->montar(10);
        Sanctum::actingAs(User::factory()->create(['perfil' => 'visualizador']));

        $this->entrada()->assertForbidden();

        $this->assertEquals(0, $this->produto->fresh()->estoque_atual);
    }
}
