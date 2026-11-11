<?php

namespace Tests\Feature;

use App\Models\ItemLote;
use App\Models\Lote;
use App\Models\Movimentacao;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BackupEstoqueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['perfil' => 'admin']);
        Sanctum::actingAs($this->admin);
    }

    public function test_exporta_backup_com_escopo_de_estoque_explicito(): void
    {
        $resposta = $this->getJson('/api/importacao-exportacao/exportar/backup');

        $resposta->assertOk()
            ->assertJsonPath('tipo', 'backup_estoque')
            ->assertJsonPath('versao', 1)
            ->assertJsonStructure(['produtos', 'lotes', 'item_lotes', 'movimentacoes']);
    }

    public function test_rejeita_referencias_invalidas_sem_apagar_o_estoque_atual(): void
    {
        $produto = Produto::create([
            'nome' => 'Produto atual',
            'sku' => 'ATUAL-1',
            'unidade_medida' => 'UN',
            'estoque_atual' => 0,
        ]);
        $backup = [
            'produtos' => [],
            'lotes' => [],
            'item_lotes' => [[
                'id_item' => 1,
                'id_lote' => 999,
                'id_produto' => 998,
                'quantidade' => 1,
                'unidade_medida' => 'UN',
            ]],
            'movimentacoes' => [],
        ];

        $this->postJson('/api/importacao-exportacao/restaurar/backup', [
            'arquivo' => UploadedFile::fake()->createWithContent('backup.json', json_encode($backup)),
        ])->assertStatus(422);

        $this->assertDatabaseHas('produto', ['id_produto' => $produto->id_produto]);
    }

    public function test_restaura_estoque_preservando_usuarios_e_integridade_referencial(): void
    {
        $produto = Produto::create([
            'nome' => 'Produto do backup',
            'sku' => 'BACKUP-1',
            'unidade_medida' => 'UN',
            'estoque_atual' => 0,
        ]);
        $lote = Lote::create([
            'numero_lote' => 'LOTE-1',
            'quantidade' => 3,
            'status' => 'ATIVO',
            'data_entrada' => '2026-01-01',
            'id_produto' => $produto->id_produto,
        ]);
        $item = ItemLote::create([
            'id_lote' => $lote->id_lote,
            'id_produto' => $produto->id_produto,
            'quantidade' => 3,
            'unidade_medida' => 'UN',
        ]);
        Movimentacao::create([
            'tipo' => 'ENTRADA',
            'quantidade' => 3,
            'data_movimentacao' => '2026-01-01 10:00:00',
            'id_lote' => $lote->id_lote,
            'id_item' => $item->id_item,
            'id_usuario' => $this->admin->id,
        ]);
        $backup = [
            'tipo' => 'backup_estoque',
            'versao' => 1,
            'produtos' => [Produto::findOrFail($produto->id_produto)->toArray()],
            'lotes' => [Lote::findOrFail($lote->id_lote)->toArray()],
            'item_lotes' => [ItemLote::findOrFail($item->id_item)->toArray()],
            'movimentacoes' => [Movimentacao::firstOrFail()->toArray()],
        ];
        Produto::create([
            'nome' => 'Produto a substituir',
            'sku' => 'ANTIGO-1',
            'unidade_medida' => 'UN',
            'estoque_atual' => 0,
        ]);

        $this->postJson('/api/importacao-exportacao/restaurar/backup', [
            'arquivo' => UploadedFile::fake()->createWithContent('backup.json', json_encode($backup)),
        ])->assertOk();

        $this->assertDatabaseHas('produto', ['sku' => 'BACKUP-1']);
        $this->assertDatabaseMissing('produto', ['sku' => 'ANTIGO-1']);
        $this->assertDatabaseCount('movimentacao', 1);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
        $this->assertSame(3, (int) Produto::where('sku', 'BACKUP-1')->value('estoque_atual'));
    }

    public function test_visualizador_nao_pode_restaurar_backup(): void
    {
        Sanctum::actingAs(User::factory()->create(['perfil' => 'visualizador']));

        $this->postJson('/api/importacao-exportacao/restaurar/backup', [
            'arquivo' => UploadedFile::fake()->createWithContent('backup.json', '{}'),
        ])->assertForbidden();
    }
}
