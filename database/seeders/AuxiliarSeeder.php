<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuxiliarSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuarios auxiliares de prueba
        User::create([
            'name' => 'María González',
            'email' => 'auxiliar@colegio.pe',
            'password' => Hash::make('password'),
            'role' => 'auxiliar',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Carlos Ramírez',
            'email' => 'auxiliar2@colegio.pe',
            'password' => Hash::make('password'),
            'role' => 'auxiliar',
            'is_active' => true,
        ]);

        $this->command->info('✓ Usuarios auxiliares creados correctamente');
    }
}
