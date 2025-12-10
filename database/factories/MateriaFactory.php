<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Materia>
 */
class MateriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Las materias serán creadas por el seeder con el currículo nacional peruano
        return [
            'nombre' => fake()->randomElement([
                'Matemática', 'Comunicación', 'Ciencias Sociales', 'Ciencia y Tecnología',
                'Educación Física', 'Arte y Cultura', 'Inglés', 'Educación Religiosa',
                'Tutoría', 'Educación para el Trabajo'
            ])
        ];
    }
}
