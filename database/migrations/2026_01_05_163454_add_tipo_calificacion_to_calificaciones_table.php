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
        Schema::table('calificaciones', function (Blueprint $table) {
            // Agregar tipo de calificación: 'numerica' (0-20) o 'literal' (AD, A, B, C)
            $table->enum('tipo_calificacion', ['numerica', 'literal'])->default('literal')->after('periodo_academico_id');
            // Valor literal para calificaciones por letras
            $table->string('calificacion_literal', 2)->nullable()->after('tipo_calificacion');
            // Hacer nota nullable ya que ahora puede ser numérica o literal
            $table->decimal('nota', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropColumn(['tipo_calificacion', 'calificacion_literal']);
        });
    }
};
