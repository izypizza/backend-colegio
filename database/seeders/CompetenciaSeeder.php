<?php

namespace Database\Seeders;

use App\Models\Competencia;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class CompetenciaSeeder extends Seeder
{
    /**
     * Competencias del Currículo Nacional por área y ciclo.
     * Los ciclos se repiten porque la rúbrica apunta al estándar del ciclo.
     */
    public function run(): void
    {
        $primaria = ['III', 'IV', 'V'];
        $secundaria = ['VI', 'VII'];
        $todas = array_merge($primaria, $secundaria);

        $catalogo = [
            'Matemática' => [
                'ciclos' => $todas,
                'competencias' => [
                    ['MATE-1', 'Resuelve problemas de cantidad'],
                    ['MATE-2', 'Resuelve problemas de regularidad, equivalencia y cambio'],
                    ['MATE-3', 'Resuelve problemas de forma, movimiento y localización'],
                    ['MATE-4', 'Resuelve problemas de gestión de datos e incertidumbre'],
                ],
            ],
            'Comunicación' => [
                'ciclos' => $todas,
                'competencias' => [
                    ['COMU-1', 'Se comunica oralmente en su lengua materna'],
                    ['COMU-2', 'Lee diversos tipos de textos escritos en su lengua materna'],
                    ['COMU-3', 'Escribe diversos tipos de textos en su lengua materna'],
                ],
            ],
            'Ciencias Sociales' => [
                'ciclos' => $todas,
                'competencias' => [
                    ['CSOC-1', 'Construye interpretaciones históricas'],
                    ['CSOC-2', 'Gestiona responsablemente el espacio y el ambiente'],
                    ['CSOC-3', 'Gestiona responsablemente los recursos económicos'],
                ],
            ],
            'Ciencia y Tecnología' => [
                'ciclos' => $todas,
                'competencias' => [
                    ['CYT-1', 'Indaga mediante métodos científicos para construir sus conocimientos'],
                    ['CYT-2', 'Explica el mundo físico basándose en conocimientos sobre los seres vivos, materia y energía, biodiversidad, Tierra y universo'],
                    ['CYT-3', 'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno'],
                ],
            ],
            'Educación Física' => [
                'ciclos' => $todas,
                'competencias' => [
                    ['EFIS-1', 'Se desenvuelve de manera autónoma a través de su motricidad'],
                    ['EFIS-2', 'Asume una vida saludable'],
                    ['EFIS-3', 'Interactúa a través de sus habilidades sociomotrices'],
                ],
            ],
            'Arte y Cultura' => [
                'ciclos' => $todas,
                'competencias' => [
                    ['ARTE-1', 'Aprecia de manera crítica manifestaciones artístico-culturales'],
                    ['ARTE-2', 'Crea proyectos desde los lenguajes artísticos'],
                ],
            ],
            'Inglés' => [
                'ciclos' => $todas,
                'competencias' => [
                    ['ING-1', 'Se comunica oralmente en inglés como lengua extranjera'],
                    ['ING-2', 'Lee diversos tipos de textos escritos en inglés como lengua extranjera'],
                    ['ING-3', 'Escribe diversos tipos de textos en inglés como lengua extranjera'],
                ],
            ],
            'Educación Religiosa' => [
                'ciclos' => $todas,
                'competencias' => [
                    ['EREL-1', 'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente'],
                    ['EREL-2', 'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida'],
                ],
            ],
            'Desarrollo Personal, Ciudadanía y Cívica' => [
                'ciclos' => $secundaria,
                'competencias' => [
                    ['DPCC-1', 'Construye su identidad'],
                    ['DPCC-2', 'Convive y participa democráticamente en la búsqueda del bien común'],
                ],
            ],
            'Educación para el Trabajo' => [
                'ciclos' => $secundaria,
                'competencias' => [
                    ['EPT-1', 'Gestiona proyectos de emprendimiento económico o social'],
                ],
            ],
        ];

        foreach ($catalogo as $nombreMateria => $definicion) {
            $materia = Materia::where('nombre', $nombreMateria)->first();

            if (! $materia) {
                continue;
            }

            foreach ($definicion['ciclos'] as $ciclo) {
                foreach ($definicion['competencias'] as $indice => [$codigo, $nombre]) {
                    Competencia::updateOrCreate(
                        [
                            'materia_id' => $materia->id,
                            'ciclo' => $ciclo,
                            'codigo' => $codigo,
                        ],
                        [
                            'nombre' => $nombre,
                            'orden' => $indice + 1,
                            'activo' => true,
                        ]
                    );
                }
            }
        }
    }
}
