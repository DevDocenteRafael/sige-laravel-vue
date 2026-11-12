<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lote', function (Blueprint $table) {
            $table->id('id_lote');
            $table->string('numero_lote', 50);
            $table->decimal('quantidade', 10, 2)->default(0);
            $table->enum('status', ['ATIVO', 'VENCIDO', 'ESGOTADO'])->default('ATIVO');
            $table->date('data_entrada');
            $table->date('data_validade')->nullable();
            $table->dateTime('data_atualizacao')->useCurrent()->useCurrentOnUpdate();
            $table->string('descricao', 255)->nullable();
            $table->unsignedBigInteger('id_produto')->nullable();
            $table->unsignedBigInteger('id_localizacao')->nullable();

            $table->foreign('id_produto')->references('id_produto')->on('produto')->onUpdate('cascade');
            $table->foreign('id_localizacao')->references('id_localizacao')->on('localizacao')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lote');
    }
};