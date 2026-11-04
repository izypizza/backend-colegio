<?php

namespace Tests\Feature;

use App\Models\Grado;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeccionCapacidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_al_crear_una_seccion_se_guarda_la_capacidad(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $grado = Grado::create(['nombre' => '1 Primaria', 'nivel' => 'primaria']);

        Sanctum::actingAs($admin);

        $this->postJson('/api/secciones', [
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 35,
            'turno' => 'Mañana',
        ])->assertCreated();

        $this->assertDatabaseHas('secciones', [
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 35,
        ]);
    }

    public function test_al_actualizar_una_seccion_cambia_la_capacidad(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $grado = Grado::create(['nombre' => '2 Primaria', 'nivel' => 'primaria']);

        $seccion = Seccion::create([
            'nombre' => 'B',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 40,
            'turno' => 'Mañana',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/secciones/{$seccion->id}", [
            'capacidad_maxima' => 45,
        ])->assertOk();

        $this->assertSame(45, $seccion->fresh()->capacidad_maxima);
    }

    public function test_la_capacidad_no_acepta_valores_invalidos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $grado = Grado::create(['nombre' => '3 Primaria', 'nivel' => 'primaria']);

        Sanctum::actingAs($admin);

        $this->postJson('/api/secciones', [
            'nombre' => 'C',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 0,
            'turno' => 'Mañana',
        ])->assertStatus(422);
    }
}
