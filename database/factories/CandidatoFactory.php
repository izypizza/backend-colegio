<?php

namespace Database\Factories;

use App\Models\Eleccion;
use App\Models\Partido;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Candidato>
 */
class CandidatoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'eleccion_id' => Eleccion::factory(),
            'nombre' => fake()->name(),
            'partido_id' => Partido::factory(),
        ];
    }
}
