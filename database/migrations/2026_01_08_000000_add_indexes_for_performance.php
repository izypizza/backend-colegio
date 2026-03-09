<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Añade índices para optimizar las consultas más frecuentes
     */
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            if (! $this->indexExists('estudiantes', 'idx_estudiantes_seccion')) {
                $table->index('seccion_id', 'idx_estudiantes_seccion');
            }
            if (! $this->indexExists('estudiantes', 'idx_estudiantes_user')) {
                $table->index('user_id', 'idx_estudiantes_user');
            }
            if (! $this->indexExists('estudiantes', 'idx_estudiantes_seccion_estado')) {
                $table->index(['seccion_id', 'estado'], 'idx_estudiantes_seccion_estado');
            }
        });

        Schema::table('docentes', function (Blueprint $table) {
            if (! $this->indexExists('docentes', 'idx_docentes_user')) {
                $table->index('user_id', 'idx_docentes_user');
            }
            // especialidad es TEXT, no se puede indexar
        });

        Schema::table('padres', function (Blueprint $table) {
            if (! $this->indexExists('padres', 'idx_padres_user')) {
                $table->index('user_id', 'idx_padres_user');
            }
        });

        Schema::table('calificaciones', function (Blueprint $table) {
            if (! $this->indexExists('calificaciones', 'idx_calificaciones_estudiante')) {
                $table->index('estudiante_id', 'idx_calificaciones_estudiante');
            }
            if (! $this->indexExists('calificaciones', 'idx_calificaciones_materia')) {
                $table->index('materia_id', 'idx_calificaciones_materia');
            }
            if (! $this->indexExists('calificaciones', 'idx_calificaciones_periodo')) {
                $table->index('periodo_academico_id', 'idx_calificaciones_periodo');
            }
            if (! $this->indexExists('calificaciones', 'idx_calificaciones_estudiante_periodo')) {
                $table->index(['estudiante_id', 'periodo_academico_id'], 'idx_calificaciones_estudiante_periodo');
            }
        });

        Schema::table('asistencias', function (Blueprint $table) {
            if (! $this->indexExists('asistencias', 'idx_asistencias_estudiante')) {
                $table->index('estudiante_id', 'idx_asistencias_estudiante');
            }
            if (! $this->indexExists('asistencias', 'idx_asistencias_materia')) {
                $table->index('materia_id', 'idx_asistencias_materia');
            }
            if (! $this->indexExists('asistencias', 'idx_asistencias_fecha')) {
                $table->index('fecha', 'idx_asistencias_fecha');
            }
            if (! $this->indexExists('asistencias', 'idx_asistencias_estudiante_fecha')) {
                $table->index(['estudiante_id', 'fecha'], 'idx_asistencias_estudiante_fecha');
            }
        });

        Schema::table('horarios', function (Blueprint $table) {
            if (! $this->indexExists('horarios', 'idx_horarios_seccion')) {
                $table->index('seccion_id', 'idx_horarios_seccion');
            }
            if (! $this->indexExists('horarios', 'idx_horarios_materia')) {
                $table->index('materia_id', 'idx_horarios_materia');
            }
            if (! $this->indexExists('horarios', 'idx_horarios_dia')) {
                $table->index('dia', 'idx_horarios_dia');
            }
        });

        Schema::table('asignacion_docente_materia', function (Blueprint $table) {
            if (! $this->indexExists('asignacion_docente_materia', 'idx_asignacion_docente')) {
                $table->index('docente_id', 'idx_asignacion_docente');
            }
            if (! $this->indexExists('asignacion_docente_materia', 'idx_asignacion_seccion')) {
                $table->index('seccion_id', 'idx_asignacion_seccion');
            }
            if (! $this->indexExists('asignacion_docente_materia', 'idx_asignacion_docente_seccion')) {
                $table->index(['docente_id', 'seccion_id'], 'idx_asignacion_docente_seccion');
            }
        });

        Schema::table('prestamos_libros', function (Blueprint $table) {
            if (! $this->indexExists('prestamos_libros', 'idx_prestamos_estudiante')) {
                $table->index('estudiante_id', 'idx_prestamos_estudiante');
            }
            if (! $this->indexExists('prestamos_libros', 'idx_prestamos_libro')) {
                $table->index('libro_id', 'idx_prestamos_libro');
            }
            if (! $this->indexExists('prestamos_libros', 'idx_prestamos_estado')) {
                $table->index('estado', 'idx_prestamos_estado');
            }
            if (! $this->indexExists('prestamos_libros', 'idx_prestamos_devuelto')) {
                $table->index('devuelto', 'idx_prestamos_devuelto');
            }
            if (! $this->indexExists('prestamos_libros', 'idx_prestamos_estudiante_estado')) {
                $table->index(['estudiante_id', 'estado'], 'idx_prestamos_estudiante_estado');
            }
        });

        Schema::table('votos', function (Blueprint $table) {
            if (! $this->indexExists('votos', 'idx_votos_eleccion')) {
                $table->index('eleccion_id', 'idx_votos_eleccion');
            }
            // Tras el cambio de esquema, los votos se relacionan al usuario autenticado
            if (! $this->indexExists('votos', 'idx_votos_user')) {
                $table->index('user_id', 'idx_votos_user');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        return ! empty($indexes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropIndex('idx_estudiantes_seccion');
            $table->dropIndex('idx_estudiantes_user');
            $table->dropIndex('idx_estudiantes_seccion_estado');
        });

        Schema::table('docentes', function (Blueprint $table) {
            $table->dropIndex('idx_docentes_user');
        });

        Schema::table('padres', function (Blueprint $table) {
            $table->dropIndex('idx_padres_user');
        });

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_calificaciones_estudiante');
            $table->dropIndex('idx_calificaciones_materia');
            $table->dropIndex('idx_calificaciones_periodo');
            $table->dropIndex('idx_calificaciones_estudiante_periodo');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('idx_asistencias_estudiante');
            $table->dropIndex('idx_asistencias_materia');
            $table->dropIndex('idx_asistencias_fecha');
            $table->dropIndex('idx_asistencias_estudiante_fecha');
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropIndex('idx_horarios_seccion');
            $table->dropIndex('idx_horarios_materia');
            $table->dropIndex('idx_horarios_dia');
        });

        Schema::table('asignacion_docente_materia', function (Blueprint $table) {
            $table->dropIndex('idx_asignacion_docente');
            $table->dropIndex('idx_asignacion_seccion');
            $table->dropIndex('idx_asignacion_docente_seccion');
        });

        Schema::table('prestamos_libros', function (Blueprint $table) {
            $table->dropIndex('idx_prestamos_estudiante');
            $table->dropIndex('idx_prestamos_libro');
            $table->dropIndex('idx_prestamos_estado');
            $table->dropIndex('idx_prestamos_devuelto');
            $table->dropIndex('idx_prestamos_estudiante_estado');
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->dropIndex('idx_votos_eleccion');
            $table->dropIndex('idx_votos_user');
        });
    }
};
