<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Docente>
 */
class DocenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombres = ['Roberto', 'Patricia', 'Fernando', 'Mónica', 'Ricardo', 'Claudia', 'Alberto', 'Silvia', 'Jorge', 'Teresa'];
        $apellidos = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores'];
        $especialidades = [
            'Matemáticas', 'Comunicación', 'Ciencias Sociales', 'Ciencia y Tecnología',
            'Educación Física', 'Arte y Cultura', 'Inglés', 'Educación Religiosa',
            'Tutoría', 'Educación para el Trabajo'
        ];
        
        return [
            'nombre' => 'Prof. ' . fake()->randomElement($nombres) . ' ' . fake()->randomElement($apellidos) . ' ' . fake()->randomElement($apellidos),
            'especialidad' => fake()->randomElement($especialidades)
        ];
    }
}
