<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedor', function (Blueprint $table) {
            $table->id('id_fornecedor');
            $table->string('nome', 100);
            $table->string('cnpj', 18);
            $table->string('telefone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('endereco', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedor');
    }
};