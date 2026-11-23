<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConfiguracionSistemaTest extends TestCase
{
    use RefreshDatabase;

    private function crearConfiguracion(string $clave, string $tipo, string $valor, string $categoria): Configuracion
    {
        return Configuracion::updateOrCreate(
            ['clave' => $clave],
            [
                'valor' => $valor,
                'tipo' => $tipo,
                'descripcion' => $clave,
                'categoria' => $categoria,
            ]
        );
    }

    public function test_se_puede_desactivar_un_modulo_con_valor_falso(): void
    {
        $this->crearConfiguracion('modulos_biblioteca', 'boolean', 'true', 'modulos');

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/configuraciones', [
            'configuraciones' => [
                ['clave' => 'modulos_biblioteca', 'valor' => false],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('configuraciones', [
            'clave' => 'modulos_biblioteca',
            'valor' => 'false',
        ]);
    }

    public function test_no_se_aceptan_claves_inexistentes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/configuraciones', [
            'configuraciones' => [
                ['clave' => 'clave_inventada', 'valor' => 'x'],
            ],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('configuraciones', ['clave' => 'clave_inventada']);
    }

    public function test_el_modo_mantenimiento_no_bloquea_al_administrador(): void
    {
        $this->crearConfiguracion('sistema_modo_mantenimiento', 'boolean', 'true', 'sistema');

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/estudiantes')->assertOk();
        $this->getJson('/api/auth/me')->assertOk();
    }

    public function test_el_modo_mantenimiento_bloquea_a_los_demas_roles(): void
    {
        $this->crearConfiguracion('sistema_modo_mantenimiento', 'boolean', 'true', 'sistema');

        Sanctum::actingAs(User::factory()->create(['role' => 'docente']));

        $this->getJson('/api/estudiantes')
            ->assertStatus(503)
            ->assertJsonPath('maintenance_mode', true);
    }
}
