<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShowDatabaseDiagram extends Command
{
    protected $signature = 'db:diagram {--json : Output as JSON}';

    protected $description = 'Muestra el diagrama de la base de datos con relaciones';

    public function handle()
    {
        $this->info('🗄️  DIAGRAMA DE BASE DE DATOS - Sistema Escolar');
        $this->newLine();

        $tables = $this->getImportantTables();

        if ($this->option('json')) {
            $this->outputJson($tables);

            return 0;
        }

        $this->drawDiagram($tables);
        $this->newLine();
        $this->showRelationships();
        $this->newLine();
        $this->showTableDetails($tables);

        return 0;
    }

    private function getImportantTables()
    {
        return [
            'users' => [
                'icon' => '👤',
                'description' => 'Autenticación y Roles',
                'key_fields' => ['id', 'name', 'email', 'role', 'is_active'],
                'relations' => [
                    'docentes' => '1:1',
                    'padres' => '1:1',
                    'estudiantes' => '1:1',
                ],
            ],
            'docentes' => [
                'icon' => '👨‍🏫',
                'description' => 'Profesores',
                'key_fields' => ['id', 'user_id', 'nombre', 'especialidad', 'dni'],
                'relations' => [
                    'users' => 'belongsTo',
                    'asignacion_docente_materia' => '1:N',
                ],
            ],
            'padres' => [
                'icon' => '👨‍👩‍👧',
                'description' => 'Padres de Familia',
                'key_fields' => ['id', 'user_id', 'nombre', 'dni', 'ocupacion'],
                'relations' => [
                    'users' => 'belongsTo',
                    'estudiantes' => 'N:M (estudiante_padre)',
                ],
            ],
            'estudiantes' => [
                'icon' => '👨‍🎓',
                'description' => 'Alumnos',
                'key_fields' => ['id', 'user_id', 'nombre', 'dni', 'seccion_id'],
                'relations' => [
                    'users' => 'belongsTo',
                    'secciones' => 'belongsTo',
                    'padres' => 'N:M (estudiante_padre)',
                    'asistencias' => '1:N',
                    'calificaciones' => '1:N',
                ],
            ],
            'grados' => [
                'icon' => '📊',
                'description' => 'Grados Académicos',
                'key_fields' => ['id', 'nombre', 'nivel'],
                'relations' => [
                    'secciones' => '1:N',
                ],
            ],
            'secciones' => [
                'icon' => '🏫',
                'description' => 'Secciones por Grado',
                'key_fields' => ['id', 'nombre', 'grado_id', 'nivel'],
                'relations' => [
                    'grados' => 'belongsTo',
                    'estudiantes' => '1:N',
                    'horarios' => '1:N',
                ],
            ],
            'materias' => [
                'icon' => '📚',
                'description' => 'Asignaturas',
                'key_fields' => ['id', 'nombre', 'codigo'],
                'relations' => [
                    'asignacion_docente_materia' => '1:N',
                    'calificaciones' => '1:N',
                ],
            ],
            'asignacion_docente_materia' => [
                'icon' => '🔗',
                'description' => 'Asignación Docente-Materia',
                'key_fields' => ['id', 'docente_id', 'materia_id', 'seccion_id'],
                'relations' => [
                    'docentes' => 'belongsTo',
                    'materias' => 'belongsTo',
                    'secciones' => 'belongsTo',
                ],
            ],
            'periodos_academicos' => [
                'icon' => '📅',
                'description' => 'Bimestres/Trimestres',
                'key_fields' => ['id', 'nombre', 'fecha_inicio', 'fecha_fin', 'activo'],
                'relations' => [
                    'calificaciones' => '1:N',
                    'asistencias' => '1:N',
                ],
            ],
            'asistencias' => [
                'icon' => '✓',
                'description' => 'Control de Asistencia',
                'key_fields' => ['id', 'estudiante_id', 'fecha', 'estado'],
                'relations' => [
                    'estudiantes' => 'belongsTo',
                    'periodos_academicos' => 'belongsTo',
                ],
            ],
            'calificaciones' => [
                'icon' => '📝',
                'description' => 'Notas por Materia',
                'key_fields' => ['id', 'estudiante_id', 'materia_id', 'periodo_id', 'nota'],
                'relations' => [
                    'estudiantes' => 'belongsTo',
                    'materias' => 'belongsTo',
                    'periodos_academicos' => 'belongsTo',
                ],
            ],
            'horarios' => [
                'icon' => '🕐',
                'description' => 'Programación de Clases',
                'key_fields' => ['id', 'seccion_id', 'materia_id', 'dia', 'hora_inicio'],
                'relations' => [
                    'secciones' => 'belongsTo',
                    'materias' => 'belongsTo',
                ],
            ],
        ];
    }

    private function drawDiagram($tables)
    {
        $this->line('┌─────────────────────────────────────────────────────────────────────────┐');
        $this->line('│                    ARQUITECTURA DE BASE DE DATOS                         │');
        $this->line('└─────────────────────────────────────────────────────────────────────────┘');
        $this->newLine();

        // Capa 1: Autenticación
        $this->info('  [CAPA 1: AUTENTICACIÓN]');
        $this->line('  ┌──────────────────┐');
        $this->line('  │  👤 users        │ ◄── Tabla central de autenticación');
        $this->line('  │  ─────────────   │     (admin, docente, padre, estudiante)');
        $this->line('  │  • id            │');
        $this->line('  │  • email         │');
        $this->line('  │  • role          │');
        $this->line('  │  • password      │');
        $this->line('  └────────┬─────────┘');
        $this->line('           │');
        $this->line('           ├──────────────┬──────────────┐');
        $this->newLine();

        // Capa 2: Perfiles
        $this->info('  [CAPA 2: PERFILES DE USUARIOS]');
        $this->line('  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐');
        $this->line('  │ 👨‍🏫 docentes  │  │ 👨‍👩‍👧 padres    │  │ 👨‍🎓 estudiantes│');
        $this->line('  │ ────────────│  │ ────────────│  │ ────────────│');
        $this->line('  │ • user_id   │  │ • user_id   │  │ • user_id   │');
        $this->line('  │ • nombre    │  │ • nombre    │  │ • nombre    │');
        $this->line('  │ • dni       │  │ • dni       │  │ • dni       │');
        $this->line('  │ • especial. │  │ • ocupación │  │ • seccion_id│');
        $this->line('  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘');
        $this->line('         │                 │                 │');
        $this->newLine();

        // Capa 3: Estructura Académica
        $this->info('  [CAPA 3: ESTRUCTURA ACADÉMICA]');
        $this->line('  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐');
        $this->line('  │ 📊 grados    │  │ 🏫 secciones │  │ 📚 materias  │');
        $this->line('  │ ────────────│  │ ────────────│  │ ────────────│');
        $this->line('  │ • nombre    │──┤ • grado_id  │  │ • nombre    │');
        $this->line('  │ • nivel     │  │ • nombre    │  │ • codigo    │');
        $this->line('  └──────────────┘  └──────┬───────┘  └──────┬───────┘');
        $this->line('                           │                 │');
        $this->newLine();

        // Capa 4: Operaciones
        $this->info('  [CAPA 4: OPERACIONES ACADÉMICAS]');
        $this->line('  ┌───────────────────┐  ┌──────────────┐  ┌──────────────┐');
        $this->line('  │ 🔗 asignaciones   │  │ ✓ asistencias│  │ 📝 calificac.│');
        $this->line('  │ ─────────────────│  │ ────────────│  │ ────────────│');
        $this->line('  │ • docente_id     │  │ • estud._id │  │ • estud._id │');
        $this->line('  │ • materia_id     │  │ • fecha     │  │ • materia_id│');
        $this->line('  │ • seccion_id     │  │ • estado    │  │ • nota      │');
        $this->line('  └───────────────────┘  └──────────────┘  └──────────────┘');
        $this->newLine();

        // Resumen
        $this->line('  ┌─────────────────────────────────────────────────────────┐');
        $this->line('  │  📅 periodos_academicos (bimestres/trimestres)          │');
        $this->line('  │  🕐 horarios (programación de clases)                   │');
        $this->line('  └─────────────────────────────────────────────────────────┘');
    }

    private function showRelationships()
    {
        $this->info('🔗 RELACIONES PRINCIPALES:');
        $this->newLine();

        $relationships = [
            ['users', '1:1', 'docentes', 'Un usuario puede ser un docente'],
            ['users', '1:1', 'padres', 'Un usuario puede ser un padre'],
            ['users', '1:1', 'estudiantes', 'Un usuario puede ser un estudiante'],
            ['grados', '1:N', 'secciones', 'Un grado tiene muchas secciones'],
            ['secciones', '1:N', 'estudiantes', 'Una sección tiene muchos estudiantes'],
            ['padres', 'N:M', 'estudiantes', 'Padres e hijos (tabla pivot: estudiante_padre)'],
            ['docentes', 'N:M', 'materias', 'Docentes enseñan materias (asignacion_docente_materia)'],
            ['estudiantes', '1:N', 'asistencias', 'Un estudiante tiene muchas asistencias'],
            ['estudiantes', '1:N', 'calificaciones', 'Un estudiante tiene muchas calificaciones'],
            ['periodos_academicos', '1:N', 'calificaciones', 'Un periodo tiene muchas calificaciones'],
        ];

        $this->table(
            ['Tabla Origen', 'Tipo', 'Tabla Destino', 'Descripción'],
            array_map(fn ($r) => $r, $relationships)
        );
    }

    private function showTableDetails($tables)
    {
        $this->info('📋 DETALLES DE TABLAS:');
        $this->newLine();

        foreach ($tables as $tableName => $info) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $count = DB::table($tableName)->count();
            $this->line("{$info['icon']} <fg=cyan>{$tableName}</> - {$info['description']}");
            $this->line("   Registros: <fg=yellow>{$count}</>");
            $this->line('   Campos clave: '.implode(', ', $info['key_fields']));

            if (! empty($info['relations'])) {
                $this->line('   Relaciones:');
                foreach ($info['relations'] as $table => $type) {
                    $this->line("     • {$table} ({$type})");
                }
            }
            $this->newLine();
        }

        // Estadísticas generales
        $this->info('📊 ESTADÍSTICAS GENERALES:');
        $stats = [
            ['Usuarios totales', DB::table('users')->count()],
            ['Docentes', DB::table('docentes')->count()],
            ['Padres', DB::table('padres')->count()],
            ['Estudiantes', DB::table('estudiantes')->count()],
            ['Grados', DB::table('grados')->count()],
            ['Secciones', DB::table('secciones')->count()],
            ['Materias', DB::table('materias')->count()],
            ['Asistencias registradas', DB::table('asistencias')->count()],
            ['Calificaciones registradas', DB::table('calificaciones')->count()],
        ];

        $this->table(['Concepto', 'Total'], $stats);
    }

    private function outputJson($tables)
    {
        $data = [
            'tables' => [],
            'relationships' => [],
            'statistics' => [],
        ];

        foreach ($tables as $tableName => $info) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = Schema::getColumnListing($tableName);
            $count = DB::table($tableName)->count();

            $data['tables'][] = [
                'name' => $tableName,
                'description' => $info['description'],
                'columns' => $columns,
                'count' => $count,
                'relations' => $info['relations'],
            ];
        }

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
