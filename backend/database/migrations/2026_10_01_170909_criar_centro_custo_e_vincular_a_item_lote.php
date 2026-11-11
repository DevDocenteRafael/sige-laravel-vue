<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centro_custo', function (Blueprint $table) {
            $table->id('id_centro_custo');
            $table->string('codigo', 20)->unique();
            $table->string('nome', 100);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('item_lote', function (Blueprint $table) {
            $table->unsignedBigInteger('id_centro_custo')->nullable();
            $table->foreign('id_centro_custo')
                ->references('id_centro_custo')->on('centro_custo')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->dropForeign(['id_centro_custo']);
            $table->dropColumn('id_centro_custo');
        });
        Schema::dropIfExists('centro_custo');
    }
};