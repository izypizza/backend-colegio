<?php

use App\Services\HistorialAcademicoService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ata cada calificación y cada asistencia a la matrícula en la que ocurrieron.
     * Así el historial conserva su sección y su año aunque el estudiante cambie.
     */
    public function up(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->foreignId('matricula_id')
                ->nullable()
                ->after('estudiante_id')
                ->constrained('matriculas')
                ->nullOnDelete();
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->foreignId('matricula_id')
                ->nullable()
                ->after('estudiante_id')
                ->constrained('matriculas')
                ->nullOnDelete();
        });

        app(HistorialAcademicoService::class)->vincularPendientes();
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matricula_id');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matricula_id');
        });
    }

    public function rellenarCalificaciones(): void
    {
        app(HistorialAcademicoService::class)->vincularCalificaciones();
    }

    public function rellenarAsistencias(): void
    {
        app(HistorialAcademicoService::class)->vincularAsistencias();
    }
};
