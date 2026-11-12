<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacao', function (Blueprint $table) {
            $table->unsignedBigInteger('id_item')->nullable()->after('id_lote');

            $table->foreign('id_item')
                ->references('id_item')
                ->on('item_lote')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('movimentacao', function (Blueprint $table) {
            $table->dropForeign(['id_item']);
            $table->dropColumn('id_item');
        });
    }
};