<?php

namespace Database\Factories;

use App\Models\Candidato;
use App\Models\Eleccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voto>
 */
class VotoFactory extends Factory
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
            'candidato_id' => Candidato::factory(),
            'user_id' => User::factory(),
        ];
    }
}
