<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class VerifyUsers extends Command
{
    protected $signature = 'users:verify';

    protected $description = 'Verificar y actualizar usuarios de prueba';

    public function handle()
    {
        $this->info('Verificando usuarios...');
        $this->newLine();

        // Verificar/Crear Admin
        $admin = User::where('email', 'admin@colegio.pe')->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Administrador',
                'email' => 'admin@colegio.pe',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]);
            $this->info('✅ Usuario Admin creado');
        } else {
            $admin->update([
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]);
            $this->info('✅ Usuario Admin actualizado');
        }

        // Verificar/Crear Docente
        $docente = User::where('email', 'docente@colegio.pe')->first();
        if (! $docente) {
            $docente = User::create([
                'name' => 'Docente Test',
                'email' => 'docente@colegio.pe',
                'password' => Hash::make('password'),
                'role' => 'docente',
                'is_active' => true,
            ]);
            $this->info('✅ Usuario Docente creado');
        } else {
            $docente->update([
                'password' => Hash::make('password'),
                'role' => 'docente',
                'is_active' => true,
            ]);
            $this->info('✅ Usuario Docente actualizado');
        }

        // Verificar/Crear Padre
        $padre = User::where('email', 'padre@colegio.pe')->first();
        if (! $padre) {
            $padre = User::create([
                'name' => 'Padre Test',
                'email' => 'padre@colegio.pe',
                'password' => Hash::make('password'),
                'role' => 'padre',
                'is_active' => true,
            ]);
            $this->info('✅ Usuario Padre creado');
        } else {
            $padre->update([
                'password' => Hash::make('password'),
                'role' => 'padre',
                'is_active' => true,
            ]);
            $this->info('✅ Usuario Padre actualizado');
        }

        // Verificar/Crear Estudiante
        $estudiante = User::where('email', 'estudiante@colegio.pe')->first();
        if (! $estudiante) {
            $estudiante = User::create([
                'name' => 'Estudiante Test',
                'email' => 'estudiante@colegio.pe',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
                'is_active' => true,
            ]);
            $this->info('✅ Usuario Estudiante creado');
        } else {
            $estudiante->update([
                'password' => Hash::make('password'),
                'role' => 'estudiante',
                'is_active' => true,
            ]);
            $this->info('✅ Usuario Estudiante actualizado');
        }

        $this->newLine();
        $this->info('📊 Resumen de usuarios:');
        $this->newLine();

        $users = User::all();
        foreach ($users as $user) {
            $this->line("  {$user->email} - Rol: {$user->role} - Activo: ".($user->is_active ? 'Sí' : 'No'));
        }

        return 0;
    }
}
