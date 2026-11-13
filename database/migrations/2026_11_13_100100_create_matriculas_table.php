<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La matrícula es el vínculo entre una persona, un año lectivo y una sección.
     * Un estudiante puede tener una matrícula por año, nunca dos.
     */
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')
                ->constrained('estudiantes')
                ->cascadeOnDelete();
            $table->foreignId('anio_escolar_id')
                ->constrained('anios_escolares')
                ->restrictOnDelete();
            $table->foreignId('seccion_id')
                ->constrained('secciones')
                ->cascadeOnDelete();
            $table->enum('estado', ['matriculado', 'retirado', 'trasladado', 'culminado'])
                ->default('matriculado');
            $table->enum('tipo', ['regular', 'repite', 'traslado_externo', 'reincorporacion'])
                ->default('regular');
            $table->enum('origen', ['migracion', 'sistema'])
                ->default('sistema');
            $table->date('fecha_matricula');
            $table->text('observacion')->nullable();
            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['estudiante_id', 'anio_escolar_id'],
                'matriculas_estudiante_anio_unique'
            );
            $table->index(
                ['anio_escolar_id', 'seccion_id', 'estado'],
                'matriculas_anio_seccion_estado_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
