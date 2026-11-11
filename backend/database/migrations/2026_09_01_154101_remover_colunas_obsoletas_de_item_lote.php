<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->dropIndex('item_lote_categoria_index');
            $table->dropIndex('item_lote_quantidade_estoque_minimo_index');
        });

        Schema::table('item_lote', function (Blueprint $table) {
            $colunas = ['nome', 'sku', 'estoque_minimo', 'fornecedor', 'categoria'];
            foreach ($colunas as $coluna) {
                if (Schema::hasColumn('item_lote', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->string('nome')->nullable();
            $table->string('sku')->nullable();
            $table->integer('estoque_minimo')->nullable();
            $table->string('fornecedor')->nullable();
            $table->string('categoria')->nullable();
        });
    }
};