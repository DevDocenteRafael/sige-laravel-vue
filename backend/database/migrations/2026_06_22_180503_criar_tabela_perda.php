<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perda', function (Blueprint $table) {
            $table->id('id_perda');
            $table->string('razao', 255);
            $table->string('observacao', 255)->nullable();
            $table->decimal('quantidade', 10, 2);
            $table->dateTime('data_perda')->useCurrent();
            $table->unsignedBigInteger('id_lote');
            $table->unsignedBigInteger('id_usuario');

            $table->foreign('id_lote')->references('id_lote')->on('lote')->onUpdate('cascade');
            $table->foreign('id_usuario')->references('id')->on('users')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perda');
    }
};