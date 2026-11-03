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

        $datosComplementarios = [
            'El Mundo es Ancho y Ajeno' => ['isbn' => '9786124000010', 'editorial' => 'Los Andes', 'anio_publicacion' => 1941, 'cantidad_total' => 12],
            'Los Ríos Profundos' => ['isbn' => '9786124000027', 'editorial' => 'Horizonte', 'anio_publicacion' => 1958, 'cantidad_total' => 10],
            'Tradiciones Peruanas' => ['isbn' => '9786124000034', 'editorial' => 'Universo', 'anio_publicacion' => 1872, 'cantidad_total' => 8],
            'Cien Años de Soledad' => ['isbn' => '9786124000041', 'editorial' => 'Sudamericana', 'anio_publicacion' => 1967, 'cantidad_total' => 10],
            'Don Quijote de la Mancha' => ['isbn' => '9786124000058', 'editorial' => 'Espasa', 'anio_publicacion' => 1605, 'cantidad_total' => 8],
            'Biología General' => ['isbn' => '9786124000065', 'editorial' => 'Médica Panamericana', 'anio_publicacion' => 2015, 'cantidad_total' => 12],
            'Química Orgánica' => ['isbn' => '9786124000072', 'editorial' => 'Cengage Learning', 'anio_publicacion' => 2012, 'cantidad_total' => 9],
            'Álgebra de Baldor' => ['isbn' => '9786124000089', 'editorial' => 'Patria', 'anio_publicacion' => 1941, 'cantidad_total' => 15],
            'Geometría Plana y del Espacio' => ['isbn' => '9786124000096', 'editorial' => 'Mir', 'anio_publicacion' => 1980, 'cantidad_total' => 8],
            'Historia del Tahuantinsuyo' => ['isbn' => '9786124000102', 'editorial' => 'Instituto de Estudios Peruanos', 'anio_publicacion' => 1988, 'cantidad_total' => 7],
            'Historia de la República del Perú' => ['isbn' => '9786124000119', 'editorial' => 'El Comercio', 'anio_publicacion' => 1968, 'cantidad_total' => 6],
            'Geografía del Perú' => ['isbn' => '9786124000126', 'editorial' => 'Universo', 'anio_publicacion' => 1987, 'cantidad_total' => 8],
            'El Principito' => ['isbn' => '9786124000133', 'editorial' => 'Salamandra', 'anio_publicacion' => 1943, 'cantidad_total' => 14],
            'Cuentos de la Selva' => ['isbn' => '9786124000140', 'editorial' => 'Losada', 'anio_publicacion' => 1918, 'cantidad_total' => 10],
            'Enciclopedia Escolar' => ['isbn' => '9786124000157', 'editorial' => 'Lexus', 'anio_publicacion' => 2019, 'cantidad_total' => 6],
        ];

        foreach ($librosData as $indice => $libro) {
            $complemento = $datosComplementarios[$libro['titulo']] ?? null;

            if ($complemento !== null) {
                $librosData[$indice] = array_merge($libro, $complemento);
            }
        }

        Libro::insert($librosData);
    }
}
