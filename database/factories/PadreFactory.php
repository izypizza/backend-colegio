<?php

namespace Database\Factories;

use Database\Factories\Traits\RemoveAccents;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Padre>
 */
class PadreFactory extends Factory
{
    use RemoveAccents;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombres = ['Juan', 'María', 'Carlos', 'Rosa', 'Luis', 'Ana', 'Pedro', 'Carmen', 'José', 'Elena', 'Francisco', 'Teresa', 'Antonio', 'Isabel', 'Manuel'];
        $apellidosPaternos = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores'];
        $apellidosMaternos = ['Rojas', 'Castro', 'Silva', 'Vargas', 'Mendoza', 'Gutiérrez', 'Morales', 'Ortiz', 'Ríos', 'Vega'];
        $ocupaciones = ['Ingeniero', 'Doctor', 'Abogado', 'Comerciante', 'Empresario', 'Contador', 'Profesor', 'Independiente'];
        $direcciones = [
            'Av. Los Álamos 123, Cusco',
            'Jr. Las Flores 456, Wanchaq',
            'Calle Los Rosales 789, San Sebastián',
            'Av. Principal 234, Santiago',
            'Jr. Central 567, San Jerónimo',
            'Calle Las Palmeras 890, Cusco'
        ];
        
        $nombre = fake()->randomElement($nombres);
        $apPaterno = fake()->randomElement($apellidosPaternos);
        $apMaterno = fake()->randomElement($apellidosMaternos);
        
        $nombreEmail = $this->removeAccents($nombre);
        $apellidoEmail = $this->removeAccents($apPaterno);
        
        return [
            'nombres' => $nombre,
            'apellido_paterno' => $apPaterno,
            'apellido_materno' => $apMaterno,
            'dni' => fake()->unique()->numerify('########'),
            'email' => strtolower($nombreEmail . '.' . $apellidoEmail . fake()->numerify('##')) . '@gmail.com',
            'telefono' => '9' . fake()->numerify('########'),
            'direccion' => fake()->randomElement($direcciones),
            'ocupacion' => fake()->randomElement($ocupaciones),
        ];
    }
}
