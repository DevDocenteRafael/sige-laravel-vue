<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categoria', function (Blueprint $table) {
            if (!Schema::hasColumn('categoria', 'created_at')) {
                $table->timestamps();
            }
        });

        Schema::table('fornecedor', function (Blueprint $table) {
            if (!Schema::hasColumn('fornecedor', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('categoria', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        Schema::table('fornecedor', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};