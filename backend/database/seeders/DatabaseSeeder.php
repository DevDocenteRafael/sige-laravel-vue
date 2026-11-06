<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sige.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('Admin@2024'),
                'perfil'   => 'admin',
            ]
        );
    }
}