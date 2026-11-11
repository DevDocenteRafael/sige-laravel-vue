<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrada_estoque', function (Blueprint $table) {
            $table->id('id_entrada');
            $table->string('nota_fiscal', 50)->nullable();
            $table->decimal('quantidade', 10, 2);
            $table->dateTime('data_entrega')->useCurrent();
            $table->unsignedBigInteger('id_lote');
            $table->unsignedBigInteger('id_usuario');

            $table->foreign('id_lote')->references('id_lote')->on('lote')->onUpdate('cascade');
            $table->foreign('id_usuario')->references('id')->on('users')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrada_estoque');
    }
};