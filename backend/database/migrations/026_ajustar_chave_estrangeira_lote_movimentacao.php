<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacao', function (Blueprint $table) {
            $table->dropForeign(['id_lote']);
            $table->foreign('id_lote', 'fk_mov_lote')
                ->references('id_lote')->on('lote')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('movimentacao', function (Blueprint $table) {
            $table->dropForeign('fk_mov_lote');
            $table->foreign('id_lote', 'fk_mov_lote')
                ->references('id_lote')->on('lote')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }
};