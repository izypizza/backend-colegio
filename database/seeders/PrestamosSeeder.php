<?php

namespace Database\Seeders;

use App\Models\PrestamoLibro;
use App\Models\Estudiante;
use App\Models\Libro;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrestamosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estudiantes = Estudiante::whereNotNull('user_id')->limit(20)->get();
        $libros = Libro::all();
        
        if ($libros->isEmpty() || $estudiantes->isEmpty()) {
            $this->command->warn('No hay suficientes libros o estudiantes para crear préstamos');
            return;
        }

        $prestamosActivos = 0;
        $prestamosDevueltos = 0;

        foreach ($estudiantes as $estudiante) {
            // 50% de probabilidad de tener un préstamo
            if (rand(0, 1) === 1) {
                $libro = $libros->random();
                $fechaPrestamo = now()->subDays(rand(1, 30));
                
                // 70% devuelto, 30% activo
                $devuelto = rand(1, 10) <= 7;
                
                $prestamo = PrestamoLibro::create([
                    'libro_id' => $libro->id,
                    'estudiante_id' => $estudiante->id,
                    'user_id' => $estudiante->user_id,
                    'fecha_prestamo' => $fechaPrestamo,
                    'fecha_devolucion' => $devuelto ? $fechaPrestamo->copy()->addDays(rand(1, 14)) : null,
                    'devuelto' => $devuelto,
                    'estado' => $devuelto ? 'devuelto' : 'aprobado',
                ]);

                if ($devuelto) {
                    $prestamosDevueltos++;
                } else {
                    $prestamosActivos++;
                }
            }
        }

        $this->command->info("Préstamos creados: {$prestamosActivos} activos, {$prestamosDevueltos} devueltos");
    }
}
