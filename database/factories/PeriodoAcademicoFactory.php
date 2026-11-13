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
        $anioActual = (int) now()->year;
        $bimestres = ['I Bimestre', 'II Bimestre', 'III Bimestre', 'IV Bimestre'];

        return [
            'nombre' => fake()->randomElement($bimestres) . ' ' . $anioActual,
            'anio' => $anioActual,
            'estado' => 'inactivo',
            'fecha_inicio' => null,
            'fecha_fin' => null,
        ];
    }
}
