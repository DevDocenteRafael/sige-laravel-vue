<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_de_auditoria', function (Blueprint $table) {
            $table->id('id_log');
            $table->string('nome_entidade', 100);
            $table->unsignedBigInteger('id_entidade');
            $table->enum('acao', ['INSERT', 'UPDATE', 'DELETE']);
            $table->string('descricao', 255)->nullable();
            $table->text('dados_antes')->nullable();
            $table->text('dados_depois')->nullable();
            $table->dateTime('data_criacao')->useCurrent();
            $table->unsignedBigInteger('id_usuario');

            $table->foreign('id_usuario')->references('id')->on('users')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_de_auditoria');
    }
};