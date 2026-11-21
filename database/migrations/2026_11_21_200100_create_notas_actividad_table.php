<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nota de cada estudiante en una evidencia concreta.
     */
    public function up(): void
    {
        if (Schema::hasTable('notas_actividad')) {
            return;
        }

        Schema::create('notas_actividad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_evaluacion_id')->constrained('actividades_evaluacion')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->decimal('nota', 5, 2)->nullable();
            $table->string('nivel', 2)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['actividad_evaluacion_id', 'estudiante_id'], 'nota_actividad_unica');
            $table->index(['estudiante_id', 'nivel'], 'nota_actividad_por_estudiante');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_actividad');
    }
};
