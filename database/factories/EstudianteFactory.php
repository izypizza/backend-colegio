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
        $nombres = [
            'Diego', 'Sofía', 'Mateo', 'Valentina', 'Santiago', 'Isabella', 
            'Sebastián', 'Camila', 'Nicolás', 'Luciana', 'Andrés', 'Martina', 
            'Lucas', 'Emma', 'Gabriel', 'Valeria', 'Daniel', 'Mía', 'Alejandro',
            'Victoria', 'Joaquín', 'Renata', 'Leonardo', 'Amanda', 'Rafael',
            'Daniela', 'Emilio', 'Paula', 'Tomás', 'Carolina', 'Ángel', 'Fernanda'
        ];
        $apellidosPaternos = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Vega', 'Díaz', 'Herrera', 'Medina', 'Castillo'];
        $apellidosMaternos = ['Rojas', 'Castro', 'Silva', 'Vargas', 'Mendoza', 'Gutiérrez', 'Morales', 'Ortiz', 'Ríos', 'Vega', 'Ramos', 'Cruz', 'Reyes', 'Salazar', 'Aguilar'];
        $direcciones = [
            'Av. Los Álamos 123, Lima',
            'Jr. Las Flores 456, San Isidro',
            'Calle Los Rosales 789, Miraflores',
            'Av. Principal 234, Surco',
            'Jr. Central 567, Pueblo Libre',
            'Calle Las Palmeras 890, La Molina'
        ];
        
        return [
            'nombres' => fake()->randomElement($nombres),
            'apellido_paterno' => fake()->randomElement($apellidosPaternos),
            'apellido_materno' => fake()->randomElement($apellidosMaternos),
            'dni' => fake()->unique()->numerify('########'),
            'fecha_nacimiento' => fake()->dateTimeBetween('-17 years', '-6 years')->format('Y-m-d'),
            'direccion' => fake()->randomElement($direcciones),
            'telefono' => '9' . fake()->numerify('########'),
            'seccion_id' => \App\Models\Seccion::factory()
        ];
    }
}
