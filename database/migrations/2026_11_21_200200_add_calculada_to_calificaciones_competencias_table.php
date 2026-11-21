<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distingue una nota de competencia calculada desde sus evidencias
     * de una registrada directamente por el docente.
     */
    public function up(): void
    {
        if (Schema::hasColumn('calificaciones_competencias', 'calculada_desde_actividades')) {
            return;
        }

        Schema::table('calificaciones_competencias', function (Blueprint $table) {
            $table->boolean('calculada_desde_actividades')
                ->default(false)
                ->after('conclusion_descriptiva');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('calificaciones_competencias', 'calculada_desde_actividades')) {
            return;
        }

        Schema::table('calificaciones_competencias', function (Blueprint $table) {
            $table->dropColumn('calculada_desde_actividades');
        });
    }
};
