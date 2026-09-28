<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Libro;
use App\Models\PrestamoLibro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrestamosFlujoTest extends TestCase
{
    use RefreshDatabase;

    private function estudiante(): User
    {
        $user = User::factory()->create(['role' => 'estudiante', 'is_active' => true]);
        Estudiante::factory()->create(['user_id' => $user->id, 'estado' => 'activo']);

        return $user;
    }

    private function libro(): Libro
    {
        return Libro::create(['titulo' => 'Libro de prueba', 'autor' => 'Autor de prueba', 'cantidad_total' => 1, 'disponible' => true,
            'categoria_id' => \App\Models\CategoriaLibro::firstOrCreate(['nombre' => 'Pruebas'])->id]);
    }

    public function test_catalogo_completo_no_se_trunca_en_cincuenta_libros(): void
    {
        for ($i = 0; $i < 55; $i++) {
            $this->libro();
        }
        Sanctum::actingAs($this->estudiante());
        $this->getJson('/api/libros-disponibles?all=true')->assertOk()->assertJsonCount(55);
    }

    public function test_personal_registra_para_estudiante_y_respeta_fecha(): void
    {
        foreach (['admin', 'bibliotecario'] as $rol) {
            $estudiante = $this->estudiante();
            $libro = $this->libro();
            Sanctum::actingAs(User::factory()->create(['role' => $rol]));
            $fecha = today()->addDays(4)->toDateString();
            $id = $this->postJson('/api/prestamos', ['libro_id' => $libro->id,
                'user_id' => $estudiante->id, 'fecha_devolucion' => $fecha])->assertCreated()
                ->assertJsonPath('prestamo.user_id', $estudiante->id)
                ->assertJsonPath('prestamo.estado', 'aprobado')->json('prestamo.id');
            $this->assertSame($fecha, PrestamoLibro::findOrFail($id)->fecha_devolucion->toDateString());
            $this->assertSame(0, $libro->fresh()->cantidad_disponible);
            $this->postJson("/api/prestamos/$id/devolver")->assertOk();
            $this->assertSame(1, $libro->fresh()->cantidad_disponible);
            $this->postJson("/api/prestamos/$id/devolver")->assertStatus(422);
        }
    }

    public function test_solicitud_aprobacion_y_control_de_stock(): void
    {
        $libro = $this->libro();
        $ids = [];
        foreach ([$this->estudiante(), $this->estudiante()] as $estudiante) {
            Sanctum::actingAs($estudiante);
            $ids[] = $this->postJson('/api/prestamos', ['libro_id' => $libro->id])->assertCreated()
                ->assertJsonPath('prestamo.estado', 'pendiente')->json('prestamo.id');
            $this->postJson('/api/prestamos', ['libro_id' => $libro->id])->assertStatus(422);
        }
        $this->assertSame(1, $libro->fresh()->cantidad_disponible);
        Sanctum::actingAs(User::factory()->create(['role' => 'bibliotecario']));
        $this->postJson("/api/prestamos/{$ids[0]}/devolver")->assertStatus(422);
        $this->postJson("/api/prestamos/{$ids[0]}/aprobar")->assertOk();
        $this->postJson("/api/prestamos/{$ids[0]}/aprobar")->assertStatus(422);
        $this->postJson("/api/prestamos/{$ids[1]}/aprobar")->assertStatus(422);
        $this->getJson('/api/biblioteca/reportes')->assertOk()->assertJsonPath('prestamos_activos', 1);
        $this->postJson("/api/prestamos/{$ids[0]}/devolver")->assertOk();
        $this->postJson("/api/prestamos/{$ids[1]}/aprobar")->assertOk();
        $this->assertSame(0, $libro->fresh()->cantidad_disponible);
    }

    public function test_estudiante_no_suplanta_y_rechazo_permite_nueva_solicitud(): void
    {
        $estudiante = $this->estudiante();
        $otro = $this->estudiante();
        $libro = $this->libro();
        Sanctum::actingAs($estudiante);
        $this->postJson('/api/prestamos', ['libro_id' => $libro->id, 'user_id' => $otro->id])->assertStatus(422);
        $id = $this->postJson('/api/prestamos', ['libro_id' => $libro->id])->assertCreated()->json('prestamo.id');
        $this->postJson("/api/prestamos/$id/aprobar")->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['role' => 'bibliotecario']));
        $this->postJson("/api/prestamos/$id/rechazar", ['motivo' => 'Prueba de rechazo'])->assertOk();
        $this->postJson("/api/prestamos/$id/aprobar")->assertStatus(422);
        Sanctum::actingAs($estudiante);
        $this->postJson('/api/prestamos', ['libro_id' => $libro->id])->assertCreated();
        $this->getJson('/api/mis-prestamos')->assertOk()->assertJsonCount(2);
    }

    public function test_no_supera_tres_prestamos_ni_admite_usuario_incorrecto(): void
    {
        $estudiante = $this->estudiante();
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/prestamos', ['libro_id' => $this->libro()->id,
                'user_id' => $estudiante->id, 'fecha_devolucion' => today()->addDay()->toDateString()])
                ->assertStatus($i < 3 ? 201 : 422);
        }
        $this->postJson('/api/prestamos', ['libro_id' => $this->libro()->id,
            'user_id' => $admin->id, 'fecha_devolucion' => today()->toDateString()])->assertStatus(422);
        $this->assertDatabaseCount('prestamos_libros', 3);
    }
}
