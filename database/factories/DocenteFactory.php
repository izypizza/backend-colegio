<?php

namespace Database\Factories;

use Database\Factories\Traits\RemoveAccents;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Docente>
 */
class DocenteFactory extends Factory
{
    use RemoveAccents;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombres = ['Roberto', 'Patricia', 'Fernando', 'Mónica', 'Ricardo', 'Claudia', 'Alberto', 'Silvia', 'Jorge', 'Teresa', 'Luis', 'Carmen', 'Daniel', 'Elena', 'Miguel'];
        $apellidosPaternos = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores'];
        $apellidosMaternos = ['Rojas', 'Castro', 'Silva', 'Vargas', 'Mendoza', 'Gutiérrez', 'Morales', 'Ortiz', 'Ríos', 'Vega'];
        $especialidades = [
            'Matemáticas', 'Comunicación', 'Ciencias Sociales', 'Ciencia y Tecnología',
            'Educación Física', 'Arte y Cultura', 'Inglés', 'Educación Religiosa',
            'Tutoría', 'Educación para el Trabajo', 'Desarrollo Personal y Ciudadanía'
        ];
        $direcciones = [
            'Av. Los Maestros 123, Cusco',
            'Jr. Educación 456, Cusco',
            'Calle Los Profesores 789, Wanchaq',
            'Av. La Cultura 234, Cusco',
            'Jr. Pumacurco 567, Cusco'
        ];
        
        $nombre = fake()->randomElement($nombres);
        $apPaterno = fake()->randomElement($apellidosPaternos);
        $apMaterno = fake()->randomElement($apellidosMaternos);
        
        $nombreEmail = $this->removeAccents($nombre);
        $apellidoEmail = $this->removeAccents($apPaterno);
        $emailBase = strtolower($nombreEmail . '.' . $apellidoEmail);
        
        return [
            'nombres' => $nombre,
            'apellido_paterno' => $apPaterno,
            'apellido_materno' => $apMaterno,
            'dni' => fake()->unique()->numerify('########'),
            'email' => fake()->unique()->numerify($emailBase . '###@colegio.pe'),
            'telefono' => '9' . fake()->numerify('########'),
            'direccion' => fake()->randomElement($direcciones),
            'especialidad' => fake()->randomElement($especialidades),
        ];
    }
}
