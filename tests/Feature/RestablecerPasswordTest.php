<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestablecerPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_restablece_la_password_con_una_clave_temporal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $docente = User::factory()->create([
            'role' => 'docente',
            'password' => Hash::make('clave-original'),
        ]);
        $docente->createToken('sesion-abierta');

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson("/api/users/{$docente->id}/restablecer-password")
            ->assertOk()
            ->assertJsonStructure(['message', 'password_temporal', 'usuario' => ['id', 'name', 'email']]);

        $passwordTemporal = $respuesta->json('password_temporal');

        $this->assertMatchesRegularExpression('/^Tupac-\d{4}[a-z]{2}$/', $passwordTemporal);
        $this->assertTrue(Hash::check($passwordTemporal, $docente->fresh()->password));
        $this->assertSame(0, $docente->tokens()->count());
    }

    public function test_no_puede_restablecer_su_propia_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson("/api/users/{$admin->id}/restablecer-password")
            ->assertStatus(422);
    }

    public function test_un_docente_no_puede_restablecer_passwords(): void
    {
        $docente = User::factory()->create(['role' => 'docente']);
        $otro = User::factory()->create(['role' => 'padre']);

        Sanctum::actingAs($docente);

        $this->postJson("/api/users/{$otro->id}/restablecer-password")
            ->assertForbidden();
    }

    public function test_usuario_inexistente_devuelve_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/users/999999/restablecer-password')
            ->assertNotFound();
    }

    public function test_queda_registrado_en_auditoria(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $padre = User::factory()->create(['role' => 'padre']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/users/{$padre->id}/restablecer-password")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'accion' => 'restablecer_password',
            'entidad' => 'users',
            'entidad_id' => $padre->id,
        ]);
    }
}
