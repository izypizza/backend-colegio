<?php

namespace App\Console\Commands;

use App\Services\HistorialAcademicoService;
use Illuminate\Console\Command;

class VincularHistorialAcademico extends Command
{
    protected $signature = 'historial:vincular';

    protected $description = 'Vincula con su matrícula las calificaciones y asistencias que quedaron sin vínculo';

    public function handle(HistorialAcademicoService $historial): int
    {
        $resultado = $historial->vincularPendientes();

        $this->info("Calificaciones vinculadas: {$resultado['calificaciones']}");
        $this->info("Asistencias vinculadas: {$resultado['asistencias']}");

        return Command::SUCCESS;
    }
}
