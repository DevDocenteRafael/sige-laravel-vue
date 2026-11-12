<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saida_estoque', function (Blueprint $table) {
            $table->id('id_saida');
            $table->string('destino', 100);
            $table->string('documento_destino', 20)->nullable();
            $table->decimal('quantidade', 10, 2);
            $table->dateTime('data_saida')->useCurrent();
            $table->unsignedBigInteger('id_lote');
            $table->unsignedBigInteger('id_usuario');

            $table->foreign('id_lote')->references('id_lote')->on('lote')->onUpdate('cascade');
            $table->foreign('id_usuario')->references('id')->on('users')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saida_estoque');
    }
};