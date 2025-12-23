<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Docente;
use App\Models\Padre;
use App\Models\Estudiante;
use Illuminate\Support\Facades\Hash;

class SyncUsersRelations extends Command
{
    protected $signature = 'users:sync {--fresh : Crear usuarios nuevos para cada registro}';
    protected $description = 'Sincroniza las relaciones entre users y docentes/padres/estudiantes';

    public function handle()
    {
        $this->info('🔄 Sincronizando relaciones con usuarios...');
        $this->newLine();

        $fresh = $this->option('fresh');

        // 1. Sincronizar Docentes
        $this->info('👨‍🏫 Procesando Docentes...');
        $docentes = Docente::whereNull('user_id')->get();
        
        foreach ($docentes as $docente) {
            if ($fresh) {
                // Crear un usuario para este docente
                $user = User::create([
                    'name' => $docente->nombre,
                    'email' => $this->generateEmail($docente->nombre, 'docente'),
                    'password' => Hash::make('password'),
                    'role' => 'docente',
                    'is_active' => true,
                ]);
                
                $docente->user_id = $user->id;
                $docente->save();
                
                $this->line("  ✓ Creado usuario para: {$docente->nombre}");
            }
        }
        
        $this->info("  Total sincronizados: " . $docentes->count());
        $this->newLine();

        // 2. Sincronizar Padres
        $this->info('👪 Procesando Padres...');
        $padres = Padre::whereNull('user_id')->get();
        
        foreach ($padres as $padre) {
            if ($fresh) {
                $user = User::create([
                    'name' => $padre->nombre,
                    'email' => $this->generateEmail($padre->nombre, 'padre'),
                    'password' => Hash::make('password'),
                    'role' => 'padre',
                    'is_active' => true,
                ]);
                
                $padre->user_id = $user->id;
                $padre->save();
                
                $this->line("  ✓ Creado usuario para: {$padre->nombre}");
            }
        }
        
        $this->info("  Total sincronizados: " . $padres->count());
        $this->newLine();

        // 3. Sincronizar Estudiantes
        $this->info('👨‍🎓 Procesando Estudiantes...');
        $estudiantes = Estudiante::whereNull('user_id')->get();
        
        foreach ($estudiantes as $estudiante) {
            if ($fresh) {
                $user = User::create([
                    'name' => $estudiante->nombre,
                    'email' => $this->generateEmail($estudiante->nombre, 'estudiante'),
                    'password' => Hash::make('password'),
                    'role' => 'estudiante',
                    'is_active' => true,
                ]);
                
                $estudiante->user_id = $user->id;
                $estudiante->save();
                
                $this->line("  ✓ Creado usuario para: {$estudiante->nombre}");
            }
        }
        
        $this->info("  Total sincronizados: " . $estudiantes->count());
        $this->newLine();

        // Resumen final
        $this->info('📊 RESUMEN:');
        $this->table(
            ['Tabla', 'Total', 'Con User', 'Sin User'],
            [
                ['Docentes', Docente::count(), Docente::whereNotNull('user_id')->count(), Docente::whereNull('user_id')->count()],
                ['Padres', Padre::count(), Padre::whereNotNull('user_id')->count(), Padre::whereNull('user_id')->count()],
                ['Estudiantes', Estudiante::count(), Estudiante::whereNotNull('user_id')->count(), Estudiante::whereNull('user_id')->count()],
            ]
        );

        $this->newLine();
        $this->info('✅ Sincronización completada');

        return 0;
    }

    private function generateEmail($nombre, $tipo)
    {
        // Convertir nombre a formato email
        $slug = strtolower(trim($nombre));
        $slug = preg_replace('/[^a-z0-9]+/', '.', $slug);
        $slug = trim($slug, '.');
        
        // Agregar número aleatorio para evitar duplicados
        $random = rand(100, 999);
        
        return "{$slug}.{$random}@{$tipo}.colegio.pe";
    }
}
