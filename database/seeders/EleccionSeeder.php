<?php

namespace Database\Seeders;

use App\Models\Eleccion;
use App\Models\Candidato;
use Illuminate\Database\Seeder;

class EleccionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Definir elecciones con sus candidatos
        $eleccionesData = [
            [
                'titulo' => 'Elecciones Municipio Escolar 2025',
                'fecha' => now()->addDays(7)->format('Y-m-d'),
                'candidatos' => [
                    'Lista 1 - Unidos por el Cambio',
                    'Lista 2 - Por Una Escuela Mejor',
                    'Lista 3 - Juntos por Nuestra Escuela',
                ],
            ],
            [
                'titulo' => 'Elecciones Policía Escolar 2025',
                'fecha' => now()->addDays(12)->format('Y-m-d'),
                'candidatos' => [
                    'Lista A - Disciplina y Respeto',
                    'Lista B - Orden y Seguridad',
                ],
            ],
        ];

        foreach ($eleccionesData as $eleccionData) {
            $candidatos = $eleccionData['candidatos'];
            unset($eleccionData['candidatos']);
            
            $eleccion = Eleccion::create($eleccionData);

            // Crear candidatos en batch
            $candidatosData = [];
            foreach ($candidatos as $nombreCandidato) {
                $candidatosData[] = [
                    'eleccion_id' => $eleccion->id,
                    'nombre' => $nombreCandidato,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            Candidato::insert($candidatosData);
        }
    }
}
