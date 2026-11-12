<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE movimentacao MODIFY tipo ENUM('ENTRADA', 'SAIDA', 'PERDA', 'AJUSTE', 'TRANSFERENCIA') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE movimentacao MODIFY tipo ENUM('ENTRADA', 'SAIDA', 'PERDA', 'AJUSTE') NOT NULL");
    }
};