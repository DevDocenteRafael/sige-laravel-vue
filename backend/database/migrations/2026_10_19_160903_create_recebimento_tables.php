<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pedido de compra (vem do MXM)
        Schema::create('pedido', function (Blueprint $table) {
            $table->id('id_pedido');
            $table->string('numero', 30)->unique();
            $table->unsignedBigInteger('id_fornecedor')->nullable()->index();
            $table->string('fornecedor_nome', 150)->nullable();
            $table->string('fornecedor_cnpj', 14)->nullable()->index();
            $table->date('data_emissao')->nullable();
            $table->decimal('valor_total', 14, 2)->default(0);
            $table->string('status', 20)->default('aberto'); // aberto | parcial | concluido | cancelado
            $table->text('observacao')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->timestamps();
        });

        // Itens do pedido
        Schema::create('pedido_item', function (Blueprint $table) {
            $table->id('id_pedido_item');
            $table->unsignedBigInteger('id_pedido');
            $table->unsignedBigInteger('id_produto')->nullable()->index();
            $table->string('sku', 30)->index();
            $table->string('descricao', 255);
            $table->string('unidade', 10)->nullable();
            $table->decimal('quantidade_pedida', 12, 2);
            $table->decimal('quantidade_recebida', 12, 2)->default(0);
            $table->decimal('valor_unitario', 12, 2)->default(0);

            $table->unique(['id_pedido', 'sku']);
            $table->foreign('id_pedido')->references('id_pedido')->on('pedido')->cascadeOnDelete();
        });

        // De-para: código do fornecedor -> produto do SIGE (código do MXM)
        Schema::create('fornecedor_produto', function (Blueprint $table) {
            $table->id('id_fornecedor_produto');
            $table->string('fornecedor_cnpj', 14);
            $table->string('codigo_fornecedor', 60);
            $table->string('descricao_fornecedor', 255)->nullable();
            $table->unsignedBigInteger('id_produto')->index();
            $table->timestamps();

            $table->unique(['fornecedor_cnpj', 'codigo_fornecedor']);
        });

        // Nota fiscal recebida (cabeçalho)
        Schema::create('nota_recebimento', function (Blueprint $table) {
            $table->id('id_nota');
            $table->string('chave_acesso', 44)->unique();
            $table->string('numero', 20);
            $table->string('serie', 5)->nullable();
            $table->date('data_emissao')->nullable();
            $table->string('fornecedor_cnpj', 14)->nullable()->index();
            $table->string('fornecedor_nome', 150)->nullable();
            $table->unsignedBigInteger('id_pedido')->nullable()->index();
            $table->string('numero_pedido', 30)->nullable();
            $table->decimal('valor_total', 14, 2)->default(0);
            $table->string('status', 20)->default('pendente'); // pendente | parcial | conferida | cancelada
            $table->string('xml_path', 255)->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->timestamps();

            $table->foreign('id_pedido')->references('id_pedido')->on('pedido')->nullOnDelete();
        });

        // Itens da nota
        Schema::create('nota_item', function (Blueprint $table) {
            $table->id('id_nota_item');
            $table->unsignedBigInteger('id_nota');
            $table->string('codigo_fornecedor', 60);
            $table->string('descricao', 255);
            $table->string('unidade', 10)->nullable();
            $table->decimal('quantidade', 12, 4);
            $table->decimal('valor_unitario', 12, 4)->default(0);
            $table->unsignedBigInteger('id_produto')->nullable()->index();
            $table->unsignedBigInteger('id_pedido_item')->nullable()->index();
            $table->string('status', 20)->default('pendente'); // pendente | ok | divergente | entrou
            $table->decimal('quantidade_entrada', 12, 2)->default(0);
            $table->string('lote_fornecedor', 60)->nullable();
            $table->date('data_validade')->nullable();

            $table->foreign('id_nota')->references('id_nota')->on('nota_recebimento')->cascadeOnDelete();
            $table->foreign('id_pedido_item')->references('id_pedido_item')->on('pedido_item')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_item');
        Schema::dropIfExists('nota_recebimento');
        Schema::dropIfExists('fornecedor_produto');
        Schema::dropIfExists('pedido_item');
        Schema::dropIfExists('pedido');
    }
};