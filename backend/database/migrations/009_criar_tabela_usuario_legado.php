<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nome', 100);
            $table->string('email', 100);
            $table->string('senha', 200);
            $table->binary('foto_perfil')->nullable();
            $table->enum('nivel_acesso', ['admin', 'visualizador'])->default('visualizador');
            $table->boolean('ativo')->default(true);
            $table->dateTime('data_criacao')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};