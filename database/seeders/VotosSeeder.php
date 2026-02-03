<?php

namespace Database\Seeders;

use App\Models\Voto;
use App\Models\Eleccion;
use App\Models\Candidato;
use App\Models\Estudiante;
use Illuminate\Database\Seeder;

class VotosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $elecciones = Eleccion::all();
        
        if ($elecciones->isEmpty()) {
            $this->command->warn('No hay elecciones disponibles para crear votos');
            return;
        }

        $totalVotos = 0;

        foreach ($elecciones as $eleccion) {
            $candidatos = Candidato::where('eleccion_id', $eleccion->id)->get();
            
            if ($candidatos->isEmpty()) {
                continue;
            }

            // Seleccionar estudiantes que votarán (30-50% de estudiantes activos)
            $estudiantesVotantes = Estudiante::where('estado', 'activo')
                ->inRandomOrder()
                ->limit(rand(120, 200))
                ->get();

            foreach ($estudiantesVotantes as $estudiante) {
                // Votar por un candidato aleatorio
                $candidato = $candidatos->random();
                
                try {
                    Voto::create([
                        'eleccion_id' => $eleccion->id,
                        'candidato_id' => $candidato->id,
                        'estudiante_id' => $estudiante->id,
                    ]);
                    
                    $totalVotos++;
                } catch (\Exception $e) {
                    // Ignora duplicados (constraint unique evita votos múltiples)
                    continue;
                }
            }
        }

        $this->command->info("Total de votos registrados: {$totalVotos}");
    }
}
