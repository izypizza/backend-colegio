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
        $nombres = ['Roberto', 'Patricia', 'Fernando', 'Mónica', 'Ricardo', 'Claudia', 'Alberto', 'Silvia', 'Jorge', 'Teresa', 'Luis', 'Carmen', 'Daniel', 'Elena', 'Miguel'];
        $apellidosPaternos = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores'];
        $apellidosMaternos = ['Rojas', 'Castro', 'Silva', 'Vargas', 'Mendoza', 'Gutiérrez', 'Morales', 'Ortiz', 'Ríos', 'Vega'];
        $especialidades = [
            'Matemáticas', 'Comunicación', 'Ciencias Sociales', 'Ciencia y Tecnología',
            'Educación Física', 'Arte y Cultura', 'Inglés', 'Educación Religiosa',
            'Tutoría', 'Educación para el Trabajo', 'Desarrollo Personal y Ciudadanía'
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
            'email' => strtolower($nombreEmail . '.' . $apellidoEmail) . '@colegio.pe',
            'telefono' => '9' . fake()->numerify('########'),
            'direccion' => fake()->address(),
            'especialidad' => fake()->randomElement($especialidades),
        ];
    }
    
    private function removeAccents($string)
    {
        $unwanted = ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n',
                     'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U', 'Ñ'=>'N'];
        return strtr($string, $unwanted);
    }
}
