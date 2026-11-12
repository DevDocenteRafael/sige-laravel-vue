<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localizacao', function (Blueprint $table) {
            $table->id('id_localizacao');
            $table->string('corredor', 50);
            $table->string('prateleira', 50);
            $table->string('setor', 50);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localizacao');
    }
};