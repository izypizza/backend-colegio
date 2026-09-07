<?php

namespace Database\Seeders;

use App\Models\Eleccion;
use App\Models\Candidato;
use App\Models\Partido;
use Illuminate\Database\Seeder;

class EleccionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Paleta de colores para los partidos (uso en la UI)
        $colores = ['#3B82F6', '#F22727', '#04ADBF', '#F59E0B', '#10B981', '#8B5CF6', '#EC4899', '#F97316'];

        // Definir elecciones con sus partidos y candidatos
        $eleccionesData = [
            [
                'titulo' => 'Elecciones Municipio Escolar 2025',
                'fecha' => now()->addDays(7)->format('Y-m-d'),
                'fecha_inicio' => now()->addDays(5)->format('Y-m-d H:i:s'),
                'fecha_cierre' => now()->addDays(9)->format('Y-m-d H:i:s'),
                'partidos' => [
                    [
                        'nombre' => 'Unidos por el Cambio',
                        'siglas' => 'UC',
                        'candidatos' => [
                            'Lista 1 - Unidos por el Cambio',
                        ],
                    ],
                    [
                        'nombre' => 'Por una Escuela Mejor',
                        'siglas' => 'PEM',
                        'candidatos' => [
                            'Lista 2 - Por Una Escuela Mejor',
                        ],
                    ],
                    [
                        'nombre' => 'Juntos por Nuestra Escuela',
                        'siglas' => 'JNE',
                        'candidatos' => [
                            'Lista 3 - Juntos por Nuestra Escuela',
                        ],
                    ],
                ],
            ],
            [
                'titulo' => 'Elecciones Policía Escolar 2025',
                'fecha' => now()->addDays(12)->format('Y-m-d'),
                'fecha_inicio' => now()->addDays(10)->format('Y-m-d H:i:s'),
                'fecha_cierre' => now()->addDays(14)->format('Y-m-d H:i:s'),
                'partidos' => [
                    [
                        'nombre' => 'Disciplina y Respeto',
                        'siglas' => 'DR',
                        'candidatos' => [
                            'Lista A - Disciplina y Respeto',
                        ],
                    ],
                    [
                        'nombre' => 'Orden y Seguridad',
                        'siglas' => 'OS',
                        'candidatos' => [
                            'Lista B - Orden y Seguridad',
                        ],
                    ],
                ],
            ],
        ];

        $colorIndex = 0;

        foreach ($eleccionesData as $eleccionData) {
            $partidos = $eleccionData['partidos'];
            unset($eleccionData['partidos']);

            // Por defecto la elección queda pendiente (el admin la activa cuando corresponda)
            $eleccionData['estado'] = 'pendiente';
            $eleccionData['resultados_publicados'] = false;

            $eleccion = Eleccion::create($eleccionData);

            foreach ($partidos as $partidoData) {
                $candidatos = $partidoData['candidatos'];
                unset($partidoData['candidatos']);

                $partido = Partido::create(array_merge($partidoData, [
                    'eleccion_id' => $eleccion->id,
                    'descripcion' => $partidoData['nombre'],
                    'color' => $colores[$colorIndex % count($colores)],
                ]));

                $colorIndex++;

                // Crear candidatos del partido en batch
                $candidatosData = [];
                foreach ($candidatos as $nombreCandidato) {
                    $candidatosData[] = [
                        'eleccion_id' => $eleccion->id,
                        'partido_id' => $partido->id,
                        'nombre' => $nombreCandidato,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                Candidato::insert($candidatosData);
            }
        }
    }
}
