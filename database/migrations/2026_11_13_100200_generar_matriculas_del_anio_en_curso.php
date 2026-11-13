<?php

use App\Services\MatriculaService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Genera la matrícula del año en curso para los estudiantes ya registrados.
     * No borra ni altera datos: solo agrega filas en la tabla matriculas.
     */
    public function up(): void
    {
        if (DB::table('estudiantes')->count() === 0) {
            return;
        }

        $servicio = app(MatriculaService::class);
        $anio = $servicio->anioEscolarEnCurso();
        $anioEscolar = $servicio->obtenerOCrearAnioEscolar($anio);

        $servicio->generarMatriculasPendientes($anioEscolar, 'migracion');
    }

    /**
     * Solo elimina las matrículas creadas por esta migración, de modo que
     * las matrículas registradas por usuarios se conservan.
     */
    public function down(): void
    {
        DB::table('matriculas')->where('origen', 'migracion')->delete();
    }
};
