<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evidencias de aprendizaje de una competencia en un periodo.
     * Cada una tiene un peso; la nota de la competencia es su promedio ponderado.
     */
    public function up(): void
    {
        if (Schema::hasTable('actividades_evaluacion')) {
            return;
        }

        Schema::create('actividades_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competencia_id')->constrained('competencias')->cascadeOnDelete();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('tipo', 20)->default('otro');
            $table->decimal('peso', 5, 2)->default(1);
            $table->date('fecha')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['competencia_id', 'periodo_academico_id', 'nombre'], 'actividad_evaluacion_unica');
            $table->index(['periodo_academico_id', 'competencia_id'], 'actividad_por_periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades_evaluacion');
    }
};
