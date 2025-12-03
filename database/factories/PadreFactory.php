<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Padre>
 */
class PadreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombres = ['Juan', 'María', 'Carlos', 'Rosa', 'Luis', 'Ana', 'Pedro', 'Carmen', 'José', 'Elena'];
        $apellidos = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores'];
        
        return [
            'nombre' => fake()->randomElement($nombres) . ' ' . fake()->randomElement($apellidos) . ' ' . fake()->randomElement($apellidos),
            'telefono' => '9' . fake()->numerify('########') // Formato peruano: 9XXXXXXXX
        ];
    }
}
