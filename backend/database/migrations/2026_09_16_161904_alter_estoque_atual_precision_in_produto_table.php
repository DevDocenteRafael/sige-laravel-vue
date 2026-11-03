<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('produto', function (Blueprint $table) {
        $table->decimal('estoque_atual', 15, 2)->change();
    });
}

public function down(): void
{
    Schema::table('produto', function (Blueprint $table) {
        $table->decimal('estoque_atual', 8, 2)->change();
    });
}
};
