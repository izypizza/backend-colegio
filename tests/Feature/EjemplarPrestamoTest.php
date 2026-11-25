<?php

namespace Tests\Feature;

use App\Models\EjemplarLibro;
use App\Models\Estudiante;
use App\Models\Libro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EjemplarPrestamoTest extends TestCase
{
    use RefreshDatabase;

    private function datos(): array
    {
        $bibliotecario = User::factory()->create(['role' => 'bibliotecario', 'is_active' => true]);
        $estudiante = User::factory()->create(['role' => 'estudiante', 'is_active' => true]);
        Estudiante::factory()->create(['user_id' => $estudiante->id, 'estado' => 'activo']);

        $categoria = \App\Models\CategoriaLibro::create(['nombre' => 'Literatura']);

        $libro = Libro::create([
            'titulo' => 'Cuentos Andinos',
            'tipo' => 'fisico',
            'autor' => 'Autor',
            'cantidad_total' => 2,
            'categoria_id' => $categoria->id,
            'disponible' => true,
        ]);

        EjemplarLibro::create(['libro_id' => $libro->id, 'codigo' => 'CUENTOS-001', 'estado' => 'disponible']);
        EjemplarLibro::create(['libro_id' => $libro->id, 'codigo' => 'CUENTOS-002', 'estado' => 'disponible']);

        return compact('bibliotecario', 'estudiante', 'libro');
    }

    public function test_un_prestamo_guarda_el_ejemplar_exacto(): void
    {
        ['bibliotecario' => $bibliotecario, 'estudiante' => $estudiante, 'libro' => $libro] = $this->datos();
        Sanctum::actingAs($bibliotecario);

        $ejemplar = EjemplarLibro::where('libro_id', $libro->id)->orderBy('id')->first();

        $respuesta = $this->postJson('/api/prestamos', [
            'libro_id' => $libro->id,
            'ejemplar_id' => $ejemplar->id,
            'user_id' => $estudiante->id,
            'fecha_devolucion' => today()->addDays(7)->toDateString(),
        ]);

        $respuesta->assertCreated();
        $this->assertSame($ejemplar->id, $respuesta->json('prestamo.ejemplar_id'));
        $this->assertSame('prestado', $ejemplar->fresh()->estado);
    }

    public function test_no_presta_dos_prestamos_sobre_el_mismo_ejemplar(): void
    {
        ['bibliotecario' => $bibliotecario, 'estudiante' => $estudiante, 'libro' => $libro] = $this->datos();
        Sanctum::actingAs($bibliotecario);

        $ejemplar = EjemplarLibro::where('libro_id', $libro->id)->orderBy('id')->first();

        $this->postJson('/api/prestamos', [
            'libro_id' => $libro->id,
            'ejemplar_id' => $ejemplar->id,
            'user_id' => $estudiante->id,
            'fecha_devolucion' => today()->addDays(7)->toDateString(),
        ])->assertCreated();

        $otroEstudiante = User::factory()->create(['role' => 'estudiante', 'is_active' => true]);
        Estudiante::factory()->create(['user_id' => $otroEstudiante->id, 'estado' => 'activo']);

        $this->postJson('/api/prestamos', [
            'libro_id' => $libro->id,
            'ejemplar_id' => $ejemplar->id,
            'user_id' => $otroEstudiante->id,
            'fecha_devolucion' => today()->addDays(7)->toDateString(),
        ])->assertStatus(422);
    }

    public function test_devolver_libera_el_ejemplar(): void
    {
        ['bibliotecario' => $bibliotecario, 'estudiante' => $estudiante, 'libro' => $libro] = $this->datos();
        Sanctum::actingAs($bibliotecario);

        $ejemplar = EjemplarLibro::where('libro_id', $libro->id)->orderBy('id')->first();

        $prestamo = $this->postJson('/api/prestamos', [
            'libro_id' => $libro->id,
            'ejemplar_id' => $ejemplar->id,
            'user_id' => $estudiante->id,
            'fecha_devolucion' => today()->addDays(7)->toDateString(),
        ])->assertCreated()->json('prestamo.id');

        $this->postJson("/api/prestamos/{$prestamo}/devolver")->assertOk();

        $this->assertSame('disponible', $ejemplar->fresh()->estado);
    }
}
