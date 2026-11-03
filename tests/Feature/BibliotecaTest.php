<?php

namespace Tests\Feature;

use App\Models\CategoriaLibro;
use App\Models\Estudiante;
use App\Models\Libro;
use App\Models\PrestamoLibro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BibliotecaTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $categoria = CategoriaLibro::create(['nombre' => 'Literatura Peruana']);

        $libro = Libro::create([
            'titulo' => 'Los Ríos Profundos',
            'tipo' => 'fisico',
            'autor' => 'José María Arguedas',
            'cantidad_total' => 2,
            'categoria_id' => $categoria->id,
            'disponible' => true,
        ]);

        $usuarioEstudiante = User::factory()->create(['role' => 'estudiante']);
        $estudiante = Estudiante::factory()->create(['user_id' => $usuarioEstudiante->id]);

        $prestamo = PrestamoLibro::create([
            'libro_id' => $libro->id,
            'user_id' => $usuarioEstudiante->id,
            'estudiante_id' => $estudiante->id,
            'fecha_prestamo' => today(),
            'fecha_devolucion' => today()->addDays(15),
            'devuelto' => false,
            'estado' => 'aprobado',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        return compact('categoria', 'libro', 'prestamo', 'admin');
    }

    public function test_devolver_marca_el_prestamo_como_devuelto(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['admin']);

        $this->postJson("/api/prestamos/{$datos['prestamo']->id}/devolver")->assertOk();

        $prestamo = $datos['prestamo']->fresh();

        $this->assertTrue($prestamo->devuelto);
        $this->assertSame('devuelto', $prestamo->estado);
    }

    public function test_el_libro_recupera_su_disponibilidad_al_devolverse(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['admin']);

        $this->assertSame(1, $datos['libro']->fresh()->cantidad_disponible);

        $this->postJson("/api/prestamos/{$datos['prestamo']->id}/devolver")->assertOk();

        $this->assertSame(2, $datos['libro']->fresh()->cantidad_disponible);
    }

    public function test_se_puede_crear_un_libro_digital_con_enlace(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['admin']);

        $this->postJson('/api/libros', [
            'titulo' => 'Compendio Digital',
            'tipo' => 'digital',
            'cantidad_total' => 999,
            'categoria_id' => $datos['categoria']->id,
            'url_digital' => 'https://repositorio.minedu.gob.pe/',
            'formato_digital' => 'PDF',
        ])->assertCreated();

        $this->assertDatabaseHas('libros', [
            'titulo' => 'Compendio Digital',
            'tipo' => 'digital',
            'url_digital' => 'https://repositorio.minedu.gob.pe/',
            'formato_digital' => 'PDF',
        ]);
    }

    public function test_un_libro_digital_exige_el_enlace(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['admin']);

        $this->postJson('/api/libros', [
            'titulo' => 'Sin enlace',
            'tipo' => 'digital',
            'cantidad_total' => 1,
            'categoria_id' => $datos['categoria']->id,
        ])->assertStatus(422);
    }

    public function test_no_se_puede_devolver_dos_veces(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['admin']);

        $this->postJson("/api/prestamos/{$datos['prestamo']->id}/devolver")->assertOk();
        $this->postJson("/api/prestamos/{$datos['prestamo']->id}/devolver")->assertStatus(422);
    }
}
