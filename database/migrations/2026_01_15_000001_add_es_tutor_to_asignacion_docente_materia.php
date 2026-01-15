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
        Schema::table('asignacion_docente_materia', function (Blueprint $table) {
            $table->boolean('es_tutor')->default(false)->after('periodo_academico_id');
            $table->timestamp('tutor_hasta')->nullable()->after('es_tutor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignacion_docente_materia', function (Blueprint $table) {
            $table->dropColumn(['es_tutor', 'tutor_hasta']);
        });
    }
};
