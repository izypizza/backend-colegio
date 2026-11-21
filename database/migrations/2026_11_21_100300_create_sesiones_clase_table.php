<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada clase dictada es una sesión. Sobre ella se registra la asistencia,
     * de modo que un curso puede tener varias sesiones en un mismo día.
     */
    public function up(): void
    {
        if (Schema::hasTable('sesiones_clase')) {
            return;
        }

        Schema::create('sesiones_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_docente_materia_id')
                ->constrained('asignacion_docente_materia')
                ->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio')->default('00:00:00');
            $table->time('hora_fin')->nullable();
            $table->string('tema', 255)->nullable();
            $table->string('estado', 20)->default('dictada');
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['asignacion_docente_materia_id', 'fecha', 'hora_inicio'],
                'sesion_clase_unica'
            );
            $table->index(['fecha', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_clase');
    }
};
