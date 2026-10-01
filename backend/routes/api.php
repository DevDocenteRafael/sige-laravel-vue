<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ItemLoteController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\PerdaController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\RelatorioAvancadoController;
use App\Http\Controllers\ImportacaoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CentroCustoController;

Route::apiResource('centros-custo', CentroCustoController::class)
    ->parameters(['centros-custo' => 'centroCusto']);
// ─── Auth ──────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());

    // ─── Dashboard ───────────────────────────────────────────
    Route::get('/dashboard',              [DashboardController::class, 'index']);
    Route::get('/dashboard/top-produtos', [DashboardController::class, 'topProdutos']);

    // ─── Lotes ───────────────────────────────────────────────
    Route::apiResource('/lotes', LoteController::class)->middleware('perfil:admin,operador');
    Route::delete('/lotes', [LoteController::class, 'destroyMultiplos'])->middleware('perfil:admin,operador');

    // ─── Itens de lote ───────────────────────────────────────
    Route::get('/lotes/{idLote}/itens',             [ItemLoteController::class, 'index']);
    Route::post('/lotes/{idLote}/itens',            [ItemLoteController::class, 'store'])->middleware('perfil:admin,operador');
    Route::patch('/lotes/{idLote}/itens/reordenar', [ItemLoteController::class, 'reordenar'])->middleware('perfil:admin,operador');
    Route::put('/itens/{id}',              [ItemLoteController::class, 'update'])->middleware('perfil:admin,operador');
    Route::patch('/itens/{id}/baixa',      [ItemLoteController::class, 'baixa'])->middleware('perfil:admin,operador');
    Route::patch('/itens/{id}/entrada',    [ItemLoteController::class, 'entrada'])->middleware('perfil:admin,operador');
    Route::patch('/itens/{id}/transferir', [ItemLoteController::class, 'transferir'])->middleware('perfil:admin,operador');
    Route::get('/itens', [ItemLoteController::class, 'todos']);
    Route::delete('/itens/{id}',           [ItemLoteController::class, 'destroy'])->middleware('perfil:admin,operador');
    Route::delete('/itens', [ItemLoteController::class, 'destroyMultiplos'])->middleware('perfil:admin,operador');
    Route::get('/itens/{id}/historico',    [ItemLoteController::class, 'historico']);
    Route::post('/itens/transferir-lote', [ItemLoteController::class, 'transferirEmLote'])->middleware('perfil:admin,operador');
    // ─── Perfil ──────────────────────────────────────────────
    Route::put('/perfil',       [PerfilController::class, 'atualizarPerfil']);
    Route::post('/perfil',      [PerfilController::class, 'atualizarPerfil']);
    Route::put('/perfil/senha', [PerfilController::class, 'atualizarSenha']);

    // ─── Produtos ────────────────────────────────────────────
    Route::get('/produtos',         [ProdutoController::class, 'index']);
        Route::get('/produtos/buscar-por-sku',   [ProdutoController::class, 'buscarPorSku']);
    Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy']);
    Route::delete('/produtos',      [ProdutoController::class, 'destroyMultiplos']);

    // ─── Perdas ──────────────────────────────────────────────
    Route::get('/perdas',              [PerdaController::class, 'index']);
    Route::post('/perdas',             [PerdaController::class, 'store'])->middleware('perfil:admin,operador');
     Route::post('/perdas/varios',      [PerdaController::class, 'storeVarios'])->middleware('perfil:admin,operador');
    Route::get('/perdas/estatisticas', [PerdaController::class, 'estatisticas']);

    // ─── Movimentações ───────────────────────────────────────
    Route::get('/movimentacoes',         [MovimentacaoController::class, 'index']);
    Route::delete('/movimentacoes/{id}', [MovimentacaoController::class, 'destroy'])->middleware('perfil:admin');

    // ─── Relatórios ──────────────────────────────────────────
    Route::get('/relatorios/estoque',     [RelatorioController::class, 'estoque']);
    Route::get('/relatorios/vencimentos', [RelatorioController::class, 'vencimentos']);
    Route::get('/relatorios/auditoria',   [RelatorioController::class, 'auditoria']);
    Route::get('/relatorios/itens',       [RelatorioController::class, 'itens']);

    Route::get('/relatorios-avancados/perdas', [RelatorioAvancadoController::class, 'perdas']);
    Route::get('/relatorios-avancados/abc',    [RelatorioAvancadoController::class, 'abc']);

    // ─── Importação ──────────────────────────────────────────
    Route::get('/importacao-exportacao/stats',        [ImportacaoController::class, 'stats']);
    Route::get('/importacao-exportacao/template-csv', [ImportacaoController::class, 'downloadTemplate']);
    Route::get('/importacao-exportacao/template',     [ImportacaoController::class, 'downloadTemplate']);
    Route::post('/importacao-exportacao/preview',     [ImportacaoController::class, 'previewImportacao']);
    Route::post('/importacao-exportacao/confirmar',   [ImportacaoController::class, 'confirmarImportacao'])->middleware('perfil:admin,operador');

    // ─── Exportação ──────────────────────────────────────────
    Route::get('/importacao-exportacao/exportar/produtos-csv',       [ExportController::class, 'exportarProdutosCSV']);
    Route::get('/importacao-exportacao/exportar/produtos-xlsx',      [ExportController::class, 'exportarProdutosXLSX']);
    Route::get('/importacao-exportacao/exportar/movimentacoes-csv',  [ExportController::class, 'exportarMovimentacoesCSV']);
    Route::get('/importacao-exportacao/exportar/movimentacoes-xlsx', [ExportController::class, 'exportarMovimentacoesXLSX']);

    // ─── Backup ──────────────────────────────────────────────
    Route::get('/importacao-exportacao/exportar/backup',   [BackupController::class, 'exportarBackup']);
    Route::post('/importacao-exportacao/restaurar/backup', [BackupController::class, 'restaurarBackup'])->middleware('perfil:admin');

    // ─── Auditoria ───────────────────────────────────────────
    Route::get('/audit-logs',        [AuditLogController::class, 'index'])->middleware('perfil:admin');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->middleware('perfil:admin');

    // ─── Usuários ────────────────────────────────────────────
    Route::get('/usuarios',               [UsuarioController::class, 'index'])->middleware('perfil:admin');
    Route::post('/usuarios',              [UsuarioController::class, 'store'])->middleware('perfil:admin');
    Route::put('/usuarios/{id}',          [UsuarioController::class, 'update'])->middleware('perfil:admin');
    Route::patch('/usuarios/{id}/status', [UsuarioController::class, 'alternarStatus'])->middleware('perfil:admin');

    // ─── Chatbot ─────────────────────────────────────────────
    Route::post('/chatbot', [ChatbotController::class, 'perguntar']);
    Route::post('/chatbot/stream', [ChatbotController::class, 'perguntarStream']);
});