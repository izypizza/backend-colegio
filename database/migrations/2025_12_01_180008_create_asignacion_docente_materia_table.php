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
        Schema::create('asignacion_docente_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')
                  ->constrained('docentes')
                  ->cascadeOnDelete();
            $table->foreignId('materia_id')
                  ->constrained('materias')
                  ->cascadeOnDelete();
            $table->foreignId('seccion_id')
                  ->constrained('secciones')
                  ->cascadeOnDelete();
            $table->foreignId('periodo_academico_id')
                  ->constrained('periodos_academicos')
                  ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignacion_docente_materia');
    }
};
