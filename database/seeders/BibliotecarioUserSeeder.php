<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BibliotecarioUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario bibliotecario
        User::create([
            'name' => 'Carmen Rosa Bibliotecaria',
            'email' => 'bibliotecario@colegio.pe',
            'password' => Hash::make('biblioteca2025'),
            'role' => 'bibliotecario',
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Usuario bibliotecario creado exitosamente');
        $this->command->info('📧 Email: bibliotecario@colegio.pe');
        $this->command->info('🔑 Password: biblioteca2025');
    }
}
