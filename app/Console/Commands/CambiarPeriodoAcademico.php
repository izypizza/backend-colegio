<?php

namespace App\Console\Commands;

use App\Models\PeriodoAcademico;
use Illuminate\Console\Command;

class CambiarPeriodoAcademico extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'periodo:activar {periodo_id : ID del periodo a activar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activa un periodo académico y desactiva los demás';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $periodoId = $this->argument('periodo_id');
        
        $periodo = PeriodoAcademico::find($periodoId);
        
        if (!$periodo) {
            $this->error("No se encontró el periodo con ID: {$periodoId}");
            return Command::FAILURE;
        }
        
        // Desactivar todos los periodos
        PeriodoAcademico::query()->update(['estado' => 'inactivo']);
        
        // Activar el periodo seleccionado
        $periodo->estado = 'activo';
        $periodo->save();
        
        $this->info("Periodo '{$periodo->nombre}' activado correctamente");
        $this->info("  Todos los demás periodos han sido desactivados");
        
        return Command::SUCCESS;
    }
}
