<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Estudiante>
 */
class EstudianteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombres = ['Diego', 'Sofía', 'Mateo', 'Valentina', 'Santiago', 'Isabella', 'Sebastián', 'Camila', 'Nicolás', 'Luciana'];
        $apellidos = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores'];
        
        return [
            'nombre' => fake()->randomElement($nombres) . ' ' . fake()->randomElement($apellidos) . ' ' . fake()->randomElement($apellidos),
            'fecha_nacimiento' => fake()->dateTimeBetween('-17 years', '-6 years')->format('Y-m-d'), // Edad escolar: 6-17 años
            'seccion_id' => \App\Models\Seccion::factory()
        ];
    }
}
