<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de competencias del Currículo Nacional por área y ciclo.
     * Cada competencia conserva su propia valoración: no se promedian.
     */
    public function up(): void
    {
        if (Schema::hasTable('competencias')) {
            return;
        }

        Schema::create('competencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->string('ciclo', 3);
            $table->string('codigo', 20);
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['materia_id', 'ciclo', 'codigo'], 'competencia_unica_por_ciclo');
            $table->index(['ciclo', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencias');
    }
};
