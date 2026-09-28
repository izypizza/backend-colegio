<?php

namespace Tests\Feature;

use App\Models\Candidato;
use App\Models\Eleccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VotacionVigenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_admite_un_voto_dentro_del_intervalo(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 28)->setTime(12, 0));
        Sanctum::actingAs(User::factory()->create(['role' => 'estudiante']));
        $eleccion = Eleccion::factory()->create(['estado' => 'activa',
            'fecha_inicio' => now()->addMinute(), 'fecha_cierre' => now()->addMinutes(2)]);
        $candidato = Candidato::create(['nombre' => 'Lista de prueba', 'eleccion_id' => $eleccion->id]);
        $datos = ['eleccion_id' => $eleccion->id, 'candidato_id' => $candidato->id];

        $this->postJson('/api/votos', $datos)->assertStatus(400);
        $this->assertDatabaseCount('votos', 0);
        $this->travel(1)->minutes();
        $this->postJson('/api/votos', $datos)->assertCreated();
        $this->postJson('/api/votos', $datos)->assertStatus(400);
        $this->assertDatabaseCount('votos', 1);

        Sanctum::actingAs(User::factory()->create(['role' => 'estudiante']));
        $this->travel(1)->minutes();
        $this->postJson('/api/votos', $datos)->assertStatus(400);
        $this->assertDatabaseCount('votos', 1);
        $this->assertFalse($eleccion->fresh()->votacion_abierta);
    }

    public function test_sin_fechas_no_se_puede_votar(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'estudiante']));
        $eleccion = Eleccion::factory()->create(['estado' => 'activa', 'fecha_inicio' => null, 'fecha_cierre' => null]);
        $candidato = Candidato::create(['nombre' => 'Lista de prueba', 'eleccion_id' => $eleccion->id]);
        $this->postJson('/api/votos', ['eleccion_id' => $eleccion->id, 'candidato_id' => $candidato->id])
            ->assertStatus(400);
        $this->assertDatabaseCount('votos', 0);
    }
}
