<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PeriodoAcademico>
 */
class PeriodoAcademicoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement([
                'I Bimestre', 'II Bimestre', 'III Bimestre', 'IV Bimestre',
                'I Trimestre', 'II Trimestre', 'III Trimestre'
            ]),
            'anio' => fake()->numberBetween(2023, 2025)
        ];
    }
}
