<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->unsignedBigInteger('quantidade')->change();
        });
    }

    public function down(): void
    {
        Schema::table('item_lote', function (Blueprint $table) {
            $table->integer('quantidade')->change();
        });
    }
};