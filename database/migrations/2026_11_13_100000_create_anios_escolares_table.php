<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El año lectivo. Es la unidad que agrupa bimestres, matrículas,
     * asignaciones docentes y apoderados. No confundir con el bimestre.
     */
    public function up(): void
    {
        Schema::create('anios_escolares', function (Blueprint $table) {
            $table->id();
            $table->year('anio')->unique();
            $table->enum('estado', ['planificacion', 'abierto', 'cerrado'])->default('planificacion');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anios_escolares');
    }
};
