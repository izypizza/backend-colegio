<?php

namespace Database\Factories;

use App\Models\Eleccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partido>
 */
class PartidoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $partidos = [
            ['nombre' => 'Partido Juventud Estudiantil', 'siglas' => 'PJE'],
            ['nombre' => 'Unidos por una Escuela Mejor', 'siglas' => 'UEM'],
            ['nombre' => 'Alianza Estudiantil Túpac Amaru', 'siglas' => 'AETA'],
            ['nombre' => 'Frente de Líderes del Mañana', 'siglas' => 'FLM'],
            ['nombre' => 'Movimiento Estudiantil del Cambio', 'siglas' => 'MEC'],
            ['nombre' => 'Partido de la Nueva Generación', 'siglas' => 'PNG'],
        ];

        $partido = fake()->randomElement($partidos);

        return [
            'eleccion_id' => Eleccion::factory(),
            'nombre' => $partido['nombre'],
            'siglas' => $partido['siglas'],
            'descripcion' => fake()->sentence(8),
            'logo' => null,
            'color' => fake()->hexColor(),
        ];
    }
}
