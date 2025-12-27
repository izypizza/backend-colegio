<?php

namespace Database\Seeders;

use App\Models\Eleccion;
use App\Models\Candidato;
use App\Models\Estudiante;
use Illuminate\Database\Seeder;

class EleccionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear elecciones (solo con campos de la migración)
        $elecciones = [
            [
                'titulo' => 'Elecciones Municipio Escolar 2025',
                'fecha' => now()->addDays(7)->format('Y-m-d'),
            ],
            [
                'titulo' => 'Elecciones Policía Escolar 2025',
                'fecha' => now()->addDays(12)->format('Y-m-d'),
            ],
        ];

        foreach ($elecciones as $eleccionData) {
            $eleccion = Eleccion::create($eleccionData);

            // Crear candidatos para cada elección
            if ($eleccion->titulo === 'Elecciones Municipio Escolar 2025') {
                $candidatosData = [
                    ['nombre' => 'Lista 1 - Unidos por el Cambio'],
                    ['nombre' => 'Lista 2 - Por Una Escuela Mejor'],
                    ['nombre' => 'Lista 3 - Juntos por Nuestra Escuela'],
                ];

                foreach ($candidatosData as $candidato) {
                    Candidato::create([
                        'eleccion_id' => $eleccion->id,
                        'nombre' => $candidato['nombre'],
                    ]);
                }
            } elseif ($eleccion->titulo === 'Elecciones Policía Escolar 2025') {
                $candidatosData = [
                    ['nombre' => 'Lista A - Disciplina y Respeto'],
                    ['nombre' => 'Lista B - Orden y Seguridad'],
                ];

                foreach ($candidatosData as $candidato) {
                    Candidato::create([
                        'eleccion_id' => $eleccion->id,
                        'nombre' => $candidato['nombre'],
                    ]);
                }
            }
        }
    }
}
