<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Valoración del nivel de logro por competencia y periodo.
     * Admite escala literal (AD, A, B, C) o numérica (0-20), nunca ambas.
     */
    public function up(): void
    {
        if (Schema::hasTable('calificaciones_competencias')) {
            return;
        }

        Schema::create('calificaciones_competencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->foreignId('competencia_id')->constrained('competencias')->cascadeOnDelete();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->string('escala', 10)->default('literal');
            $table->string('nivel', 2)->nullable();
            $table->decimal('nota', 5, 2)->nullable();
            $table->text('conclusion_descriptiva')->nullable();
            $table->unsignedSmallInteger('modificaciones_count')->default(0);
            $table->timestamp('ultima_modificacion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['estudiante_id', 'competencia_id', 'periodo_academico_id'],
                'calificacion_competencia_unica'
            );
            $table->index(['competencia_id', 'periodo_academico_id'], 'calif_comp_por_competencia');
            $table->index(['estudiante_id', 'periodo_academico_id'], 'calif_comp_por_estudiante');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones_competencias');
    }
};
