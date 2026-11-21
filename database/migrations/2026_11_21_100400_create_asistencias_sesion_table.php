<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asistencia de cada estudiante a una sesión concreta.
     * Un estudiante tiene como máximo un registro por sesión.
     */
    public function up(): void
    {
        if (Schema::hasTable('asistencias_sesion')) {
            return;
        }

        Schema::create('asistencias_sesion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_clase_id')->constrained('sesiones_clase')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->string('estado', 15)->default('presente');
            $table->unsignedSmallInteger('minutos_tardanza')->nullable();
            $table->text('justificacion')->nullable();
            $table->timestamps();

            $table->unique(['sesion_clase_id', 'estudiante_id'], 'asistencia_sesion_unica');
            $table->index(['estudiante_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_sesion');
    }
};
