<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Eleccion>
 */
class EleccionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('now', '+15 days');
        $cierre = (clone $inicio)->modify('+2 days');

        return [
            'titulo' => fake()->randomElement([
                'Elecciones Municipio Escolar',
                'Elecciones Policía Escolar',
                'Elecciones del Consejo Estudiantil',
                'Elecciones del Delegado de Aula',
            ]) . ' ' . now()->year,
            'fecha' => $inicio->format('Y-m-d'),
            'fecha_inicio' => $inicio->format('Y-m-d H:i:s'),
            'fecha_cierre' => $cierre->format('Y-m-d H:i:s'),
            'estado' => 'pendiente',
            'resultados_publicados' => false,
        ];
    }

    /**
     * Estado activa.
     */
    public function activa(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activa',
        ]);
    }

    /**
     * Estado cerrada con resultados publicados.
     */
    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cerrada',
            'resultados_publicados' => true,
        ]);
    }
}
