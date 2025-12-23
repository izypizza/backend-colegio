<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabase extends Command
{
    protected $signature = 'db:check';

    protected $description = 'Verifica la estructura completa de la base de datos';

    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE BASE DE DATOS ===');
        $this->newLine();

        // 1. Verificar tabla users
        $this->info('📋 TABLA USERS:');
        $userColumns = Schema::getColumnListing('users');
        $this->line('Columnas: '.implode(', ', $userColumns));

        // Verificar si tiene role
        if (in_array('role', $userColumns)) {
            $this->info('✓ Campo "role" existe');
        } else {
            $this->error('✗ Campo "role" NO existe');
        }

        $this->newLine();

        // 2. Verificar foreign keys hacia users
        $this->info('🔗 FOREIGN KEYS HACIA USERS:');

        $foreignKeys = DB::select("
            SELECT 
                TABLE_NAME, 
                COLUMN_NAME, 
                CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME = 'users'
        ");

        if (count($foreignKeys) > 0) {
            foreach ($foreignKeys as $fk) {
                $this->line("  • {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} → users.{$fk->REFERENCED_COLUMN_NAME}");
            }
        } else {
            $this->warn('⚠ No hay foreign keys configuradas hacia la tabla users');
        }

        $this->newLine();

        // 3. Verificar user_id en docentes, padres, estudiantes
        $this->info('👥 RELACIONES CON USERS:');

        $tables = ['docentes', 'padres', 'estudiantes'];
        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            if (in_array('user_id', $columns)) {
                $this->info("  ✓ {$table} tiene user_id");

                // Contar registros
                $total = DB::table($table)->count();
                $withUser = DB::table($table)->whereNotNull('user_id')->count();
                $this->line("    Total: {$total}, Con user_id: {$withUser}");
            } else {
                $this->error("  ✗ {$table} NO tiene user_id");
            }
        }

        $this->newLine();

        // 4. Verificar datos en users
        $this->info('👤 USUARIOS EN LA BASE DE DATOS:');
        $users = DB::table('users')->select('id', 'name', 'email', 'role', 'is_active')->get();

        if ($users->isEmpty()) {
            $this->warn('⚠ No hay usuarios en la base de datos');
        } else {
            $this->table(
                ['ID', 'Nombre', 'Email', 'Rol', 'Activo'],
                $users->map(fn ($u) => [$u->id, $u->name, $u->email, $u->role, $u->is_active ? 'Sí' : 'No'])
            );
        }

        $this->newLine();

        // 5. Verificar relaciones
        $this->info('🔍 VERIFICACIÓN DE RELACIONES:');

        foreach ($tables as $table) {
            $tableName = ucfirst($table);
            $singular = rtrim($table, 's');

            $total = DB::table($table)->count();
            $related = DB::table($table)
                ->join('users', "{$table}.user_id", '=', 'users.id')
                ->count();

            $this->line("  {$tableName}: {$related}/{$total} relacionados con users");
        }

        $this->newLine();

        // 6. Problemas detectados
        $this->info('⚠ PROBLEMAS DETECTADOS:');
        $problems = [];

        if (count($foreignKeys) === 0) {
            $problems[] = 'No hay foreign keys configuradas hacia users (opcional pero recomendado)';
        }

        foreach ($tables as $table) {
            $orphans = DB::table($table)->whereNull('user_id')->count();
            if ($orphans > 0) {
                $problems[] = "{$orphans} registros en {$table} sin user_id asignado";
            }
        }

        if (empty($problems)) {
            $this->info('  ✓ No se detectaron problemas');
        } else {
            foreach ($problems as $problem) {
                $this->warn("  • {$problem}");
            }
        }

        $this->newLine();
        $this->info('✅ Verificación completada');

        return 0;
    }
}