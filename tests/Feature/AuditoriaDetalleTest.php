<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Grado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditoriaDetalleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_el_estado_anterior_al_actualizar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $grado = Grado::create(['nombre' => '1 Primaria', 'nivel' => 'primaria']);

        Sanctum::actingAs($admin);

        $this->putJson("/api/grados/{$grado->id}", [
            'nombre' => '1ro Primaria',
        ])->assertOk();

        $registro = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('actualizar', $registro->accion);
        $this->assertSame('grados', $registro->entidad);
        $this->assertSame($grado->id, $registro->entidad_id);
        $this->assertSame('1 Primaria', $registro->cambios_antes['nombre']);
        $this->assertSame('1ro Primaria', $registro->cambios_despues['nombre']);
    }

    public function test_no_registra_operaciones_rechazadas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $grado = Grado::create(['nombre' => '2 Primaria', 'nivel' => 'primaria']);

        Sanctum::actingAs($admin);

        $this->putJson("/api/grados/{$grado->id}", [
            'nombre' => '',
        ])->assertStatus(422);

        $this->assertSame(0, AuditLog::count());
    }

    public function test_filtra_por_entidad_y_accion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AuditLog::create([
            'user_id' => $admin->id,
            'accion' => 'crear',
            'entidad' => 'grados',
            'descripcion' => 'Crear grado #1',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'accion' => 'actualizar',
            'entidad' => 'secciones',
            'descripcion' => 'Actualizar seccion #2',
        ]);

        Sanctum::actingAs($admin);

        $respuesta = $this->getJson('/api/auditoria?entidad=grados&accion=crear');

        $respuesta->assertOk();
        $respuesta->assertJsonPath('total', 1);
        $respuesta->assertJsonPath('data.0.entidad', 'grados');
    }

    public function test_busca_por_texto_de_la_descripcion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AuditLog::create([
            'user_id' => $admin->id,
            'accion' => 'eliminar',
            'entidad' => 'materias',
            'descripcion' => 'Eliminar materia #9',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/auditoria?buscar=materia')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_devuelve_entidades_y_usuarios_para_los_filtros(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AuditLog::create([
            'user_id' => $admin->id,
            'accion' => 'crear',
            'entidad' => 'estudiantes',
            'descripcion' => 'Crear estudiante #1',
        ]);

        Sanctum::actingAs($admin);

        $respuesta = $this->getJson('/api/auditoria/filtros');

        $respuesta->assertOk();
        $respuesta->assertJsonPath('entidades.0', 'estudiantes');
        $respuesta->assertJsonPath('usuarios.0.id', $admin->id);
    }
}
