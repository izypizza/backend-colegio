<?php

namespace App\Console\Commands;

use App\Services\MatriculaService;
use Illuminate\Console\Command;

class GenerarMatriculasDelAnio extends Command
{
    protected $signature = 'matriculas:generar
                            {anio? : Año escolar a generar (por defecto el del periodo activo)}
                            {--origen=sistema : Origen a registrar en las matrículas creadas}';

    protected $description = 'Genera la matrícula de los estudiantes que aún no la tienen en el año indicado';

    public function handle(MatriculaService $servicio): int
    {
        $anio = $this->argument('anio')
            ? (int) $this->argument('anio')
            : $servicio->anioEscolarEnCurso();

        $origen = (string) $this->option('origen');

        if (!in_array($origen, ['migracion', 'sistema'], true)) {
            $this->error("Origen no válido: {$origen}. Use migracion o sistema.");

            return Command::FAILURE;
        }

        $anioEscolar = $servicio->obtenerOCrearAnioEscolar($anio);
        $generadas = $servicio->generarMatriculasPendientes($anioEscolar, $origen);

        $this->info("Año escolar {$anioEscolar->anio} ({$anioEscolar->estado}): {$generadas} matrícula(s) generada(s).");

        if ($generadas === 0) {
            $this->comment('Todos los estudiantes ya tenían matrícula en ese año.');
        }

        return Command::SUCCESS;
    }
}
