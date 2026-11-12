<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->index('data_validade');
            $table->index('categoria');
            $table->index(['quantidade', 'estoque_minimo']);
        });

        Schema::table('movimentacao', function (Blueprint $table) {
            $table->index('data_movimentacao');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->dropIndex(['data_validade']);
            $table->dropIndex(['categoria']);
            $table->dropIndex(['quantidade', 'estoque_minimo']);
        });

        Schema::table('movimentacao', function (Blueprint $table) {
            $table->dropIndex(['data_movimentacao']);
            $table->dropIndex(['tipo']);
        });
    }
};