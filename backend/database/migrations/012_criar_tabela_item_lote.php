<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('item_lote')) {
            Schema::create('item_lote', function (Blueprint $table) {
                $table->id('id_item');
                $table->foreignId('id_lote')->constrained('lote', 'id_lote')->onDelete('cascade');
                $table->string('nome');
                $table->string('sku')->nullable();
                $table->integer('quantidade')->default(0);
                $table->integer('estoque_minimo')->default(0);
                $table->string('unidade_medida')->default('UN');
                $table->date('data_validade')->nullable();
                $table->string('fornecedor')->nullable();
                $table->string('localizacao')->nullable();
                $table->string('prioridade_abc', 1)->nullable();
                $table->string('categoria');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_lote');
    }
};