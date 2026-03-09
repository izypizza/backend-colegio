<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar constraint único en estudiante_padre para evitar duplicados
        Schema::table('estudiante_padre', function (Blueprint $table) {
            $table->unique(['estudiante_id', 'padre_id'], 'unique_estudiante_padre');
        });

        // Agregar constraint único en votos para evitar que un estudiante vote múltiples veces en la misma elección
        Schema::table('votos', function (Blueprint $table) {
            // Ahora los votos se relacionan al usuario autenticado
            $table->unique(['eleccion_id', 'user_id'], 'unique_voto_usuario_eleccion');
        });

        // Agregar constraint único en asignacion_docente_materia para evitar asignaciones duplicadas
        Schema::table('asignacion_docente_materia', function (Blueprint $table) {
            $table->unique(
                ['docente_id', 'materia_id', 'seccion_id', 'periodo_academico_id'], 
                'unique_asignacion_docente'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiante_padre', function (Blueprint $table) {
            $table->dropUnique('unique_estudiante_padre');
        });

        Schema::table('votos', function (Blueprint $table) {
            $table->dropUnique('unique_voto_usuario_eleccion');
        });

        Schema::table('asignacion_docente_materia', function (Blueprint $table) {
            $table->dropUnique('unique_asignacion_docente');
        });
    }
};
