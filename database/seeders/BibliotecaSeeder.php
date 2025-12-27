<?php

namespace Database\Seeders;

use App\Models\CategoriaLibro;
use App\Models\Libro;
use Illuminate\Database\Seeder;

class BibliotecaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categorías
        $categorias = [
            ['nombre' => 'Literatura Peruana'],
            ['nombre' => 'Literatura Universal'],
            ['nombre' => 'Ciencias Naturales'],
            ['nombre' => 'Matemáticas'],
            ['nombre' => 'Historia del Perú'],
            ['nombre' => 'Geografía'],
            ['nombre' => 'Cuentos Infantiles'],
            ['nombre' => 'Enciclopedias'],
        ];

        foreach ($categorias as $categoria) {
            CategoriaLibro::create($categoria);
        }

        // Crear libros (solo con campos existentes en la migración)
        $libros = [
            // Literatura Peruana
            [
                'titulo' => 'El Mundo es Ancho y Ajeno',
                'autor' => 'Ciro Alegría',
                'categoria_id' => 1,
                'disponible' => true,
            ],
            [
                'titulo' => 'Los Ríos Profundos',
                'autor' => 'José María Arguedas',
                'categoria_id' => 1,
                'disponible' => true,
            ],
            [
                'titulo' => 'Tradiciones Peruanas',
                'autor' => 'Ricardo Palma',
                'categoria_id' => 1,
                'disponible' => true,
            ],
            
            // Literatura Universal
            [
                'titulo' => 'Cien Años de Soledad',
                'autor' => 'Gabriel García Márquez',
                'categoria_id' => 2,
                'disponible' => true,
            ],
            [
                'titulo' => 'Don Quijote de la Mancha',
                'autor' => 'Miguel de Cervantes',
                'categoria_id' => 2,
                'disponible' => true,
            ],
            
            // Ciencias Naturales
            [
                'titulo' => 'Biología General',
                'autor' => 'Curtis & Barnes',
                'categoria_id' => 3,
                'disponible' => true,
            ],
            [
                'titulo' => 'Química Orgánica',
                'autor' => 'John McMurry',
                'categoria_id' => 3,
                'disponible' => true,
            ],
            
            // Matemáticas
            [
                'titulo' => 'Álgebra de Baldor',
                'autor' => 'Aurelio Baldor',
                'categoria_id' => 4,
                'disponible' => true,
            ],
            [
                'titulo' => 'Geometría Plana y del Espacio',
                'autor' => 'A. G. Tsipkin',
                'categoria_id' => 4,
                'disponible' => true,
            ],
            
            // Historia del Perú
            [
                'titulo' => 'Historia del Tahuantinsuyo',
                'autor' => 'María Rostworowski',
                'categoria_id' => 5,
                'disponible' => true,
            ],
            [
                'titulo' => 'Historia de la República del Perú',
                'autor' => 'Jorge Basadre',
                'categoria_id' => 5,
                'disponible' => true,
            ],
            
            // Geografía
            [
                'titulo' => 'Geografía del Perú',
                'autor' => 'Javier Pulgar Vidal',
                'categoria_id' => 6,
                'disponible' => true,
            ],
            
            // Cuentos Infantiles
            [
                'titulo' => 'El Principito',
                'autor' => 'Antoine de Saint-Exupéry',
                'categoria_id' => 7,
                'disponible' => true,
            ],
            [
                'titulo' => 'Cuentos de la Selva',
                'autor' => 'Horacio Quiroga',
                'categoria_id' => 7,
                'disponible' => true,
            ],
            
            // Enciclopedias
            [
                'titulo' => 'Enciclopedia Escolar',
                'autor' => 'Varios Autores',
                'categoria_id' => 8,
                'disponible' => true,
            ],
        ];

        foreach ($libros as $libro) {
            Libro::create($libro);
        }
    }
}
