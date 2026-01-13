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
        // Crear categorías en batch
        $categoriasData = [
            ['nombre' => 'Literatura Peruana', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Literatura Universal', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ciencias Naturales', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Matemáticas', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Historia del Perú', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Geografía', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Cuentos Infantiles', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Enciclopedias', 'created_at' => now(), 'updated_at' => now()],
        ];

        CategoriaLibro::insert($categoriasData);
        $categorias = CategoriaLibro::all()->keyBy('nombre');

        CategoriaLibro::insert($categoriasData);
        $categorias = CategoriaLibro::all()->keyBy('nombre');

        // Crear libros en batch
        $librosData = [
            // Literatura Peruana
            [
                'titulo' => 'El Mundo es Ancho y Ajeno',
                'autor' => 'Ciro Alegría',
                'categoria_id' => $categorias['Literatura Peruana']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'titulo' => 'Los Ríos Profundos',
                'autor' => 'José María Arguedas',
                'categoria_id' => $categorias['Literatura Peruana']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'titulo' => 'Tradiciones Peruanas',
                'autor' => 'Ricardo Palma',
                'categoria_id' => $categorias['Literatura Peruana']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Literatura Universal
            [
                'titulo' => 'Cien Años de Soledad',
                'autor' => 'Gabriel García Márquez',
                'categoria_id' => $categorias['Literatura Universal']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'titulo' => 'Don Quijote de la Mancha',
                'autor' => 'Miguel de Cervantes',
                'categoria_id' => $categorias['Literatura Universal']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Ciencias Naturales
            [
                'titulo' => 'Biología General',
                'autor' => 'Curtis & Barnes',
                'categoria_id' => $categorias['Ciencias Naturales']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'titulo' => 'Química Orgánica',
                'autor' => 'John McMurry',
                'categoria_id' => $categorias['Ciencias Naturales']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Matemáticas
            [
                'titulo' => 'Álgebra de Baldor',
                'autor' => 'Aurelio Baldor',
                'categoria_id' => $categorias['Matemáticas']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'titulo' => 'Geometría Plana y del Espacio',
                'autor' => 'A. G. Tsipkin',
                'categoria_id' => $categorias['Matemáticas']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Historia del Perú
            [
                'titulo' => 'Historia del Tahuantinsuyo',
                'autor' => 'María Rostworowski',
                'categoria_id' => $categorias['Historia del Perú']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'titulo' => 'Historia de la República del Perú',
                'autor' => 'Jorge Basadre',
                'categoria_id' => $categorias['Historia del Perú']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Geografía
            [
                'titulo' => 'Geografía del Perú',
                'autor' => 'Javier Pulgar Vidal',
                'categoria_id' => $categorias['Geografía']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Cuentos Infantiles
            [
                'titulo' => 'El Principito',
                'autor' => 'Antoine de Saint-Exupéry',
                'categoria_id' => $categorias['Cuentos Infantiles']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'titulo' => 'Cuentos de la Selva',
                'autor' => 'Horacio Quiroga',
                'categoria_id' => $categorias['Cuentos Infantiles']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Enciclopedias
            [
                'titulo' => 'Enciclopedia Escolar',
                'autor' => 'Varios Autores',
                'categoria_id' => $categorias['Enciclopedias']->id,
                'disponible' => true,
                'tipo' => 'fisico',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Libro::insert($librosData);
    }
}
