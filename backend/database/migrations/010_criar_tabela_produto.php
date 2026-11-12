<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto', function (Blueprint $table) {
            $table->id('id_produto');
            $table->string('nome', 100);
            $table->string('sku', 50);
            $table->string('unidade_medida', 20);
            $table->decimal('preco_custo', 10, 2)->default(0);
            $table->decimal('estoque_minimo', 10, 2)->default(0);
            $table->decimal('estoque_atual', 10, 2)->default(0);
            $table->enum('prioridade_abc', ['A', 'B', 'C'])->nullable();
            $table->unsignedBigInteger('id_categoria')->nullable();
            $table->unsignedBigInteger('id_fornecedor')->nullable();

            $table->foreign('id_categoria')->references('id_categoria')->on('categoria')->onDelete('set null');
            $table->foreign('id_fornecedor')->references('id_fornecedor')->on('fornecedor')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto');
    }
};