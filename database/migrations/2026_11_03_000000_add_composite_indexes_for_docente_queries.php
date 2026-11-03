<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices compuestos para las consultas del portal docente y del dashboard.
     * Complementan a los índices simples creados en 2026_01_08_000000.
     */
    public function up(): void
    {
        Schema::table('asignacion_docente_materia', function (Blueprint $table) {
            if (! $this->indexExists('asignacion_docente_materia', 'idx_asignacion_docente_periodo_materia')) {
                $table->index(
                    ['docente_id', 'periodo_academico_id', 'materia_id', 'seccion_id'],
                    'idx_asignacion_docente_periodo_materia'
                );
            }
        });

        Schema::table('calificaciones', function (Blueprint $table) {
            if (! $this->indexExists('calificaciones', 'idx_calificaciones_materia_periodo')) {
                $table->index(['materia_id', 'periodo_academico_id'], 'idx_calificaciones_materia_periodo');
            }
        });

        Schema::table('asistencias', function (Blueprint $table) {
            if (! $this->indexExists('asistencias', 'idx_asistencias_materia_fecha')) {
                $table->index(['materia_id', 'fecha'], 'idx_asistencias_materia_fecha');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asignacion_docente_materia', function (Blueprint $table) {
            $table->dropIndex('idx_asignacion_docente_periodo_materia');
        });

        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_calificaciones_materia_periodo');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('idx_asistencias_materia_fecha');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return Schema::hasIndex($table, $indexName);
    }
};
