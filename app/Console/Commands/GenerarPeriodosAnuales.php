<?php

namespace App\Console\Commands;

use App\Models\PeriodoAcademico;
use Illuminate\Console\Command;

class GenerarPeriodosAnuales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'periodo:generar-anio {anio? : Año para el cual generar periodos (opcional, por defecto año actual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera los 4 bimestres para un año académico específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $anio = $this->argument('anio') ?? (int) date('Y');
        
        // Verificar si ya existen periodos para este año
        $periodosExistentes = PeriodoAcademico::where('anio', $anio)->count();
        
        if ($periodosExistentes > 0) {
            if (!$this->confirm("Ya existen {$periodosExistentes} periodos para el año {$anio}. ¿Desea continuar de todos modos?")) {
                $this->info('Operación cancelada');
                return Command::SUCCESS;
            }
        }
        
        // Crear los 4 bimestres
        $bimestres = [
            ['nombre' => "I Bimestre {$anio}", 'anio' => $anio, 'estado' => 'inactivo'],
            ['nombre' => "II Bimestre {$anio}", 'anio' => $anio, 'estado' => 'inactivo'],
            ['nombre' => "III Bimestre {$anio}", 'anio' => $anio, 'estado' => 'inactivo'],
            ['nombre' => "IV Bimestre {$anio}", 'anio' => $anio, 'estado' => 'inactivo'],
        ];
        
        $this->info("Generando periodos para el año {$anio}...");
        
        foreach ($bimestres as $bimestre) {
            // Verificar si ya existe este periodo específico
            $existe = PeriodoAcademico::where('nombre', $bimestre['nombre'])->first();
            
            if (!$existe) {
                PeriodoAcademico::create($bimestre);
                $this->info("Creado: {$bimestre['nombre']}");
            } else {
                $this->warn("  Ya existe: {$bimestre['nombre']}");
            }
        }
        
        $this->info("\nPeriodos generados correctamente!");
        $this->info("Usa 'php artisan periodo:activar <id>' para activar un periodo específico");
        
        return Command::SUCCESS;
    }
}
