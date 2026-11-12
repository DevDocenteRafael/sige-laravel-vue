<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'pin_expira_em')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('pin_expira_em')->nullable()->after('pin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'pin_expira_em')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('pin_expira_em');
            });
        }
    }
};