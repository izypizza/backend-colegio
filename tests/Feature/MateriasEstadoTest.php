<?php

namespace Tests\Feature;

use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MateriasEstadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_administracion_desactiva_una_materia_sin_borrarla(): void
    {
        $materia = Materia::factory()->create(['nombre' => 'Tutoría']);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->patchJson('/api/materias/'.$materia->id.'/estado', ['activo' => false])
            ->assertOk()
            ->assertJsonPath('materia.activo', false);

        $this->assertDatabaseHas('materias', ['id' => $materia->id, 'activo' => 0]);

        $this->patchJson('/api/materias/'.$materia->id.'/estado', ['activo' => true])
            ->assertOk()
            ->assertJsonPath('materia.activo', true);
    }

    public function test_el_listado_se_puede_filtrar_por_estado(): void
    {
        $activa = Materia::factory()->create(['nombre' => 'Matemática', 'activo' => true]);
        $inactiva = Materia::factory()->create(['nombre' => 'Música', 'activo' => false]);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/materias?activo=1')
            ->assertOk()
            ->assertJsonFragment(['id' => $activa->id])
            ->assertJsonMissing(['id' => $inactiva->id]);

        $this->getJson('/api/materias?activo=0')
            ->assertOk()
            ->assertJsonFragment(['id' => $inactiva->id])
            ->assertJsonMissing(['id' => $activa->id]);
    }

    public function test_un_docente_no_puede_cambiar_el_estado_de_una_materia(): void
    {
        $materia = Materia::factory()->create();

        Sanctum::actingAs(User::factory()->create(['role' => 'docente']));

        $this->patchJson('/api/materias/'.$materia->id.'/estado', ['activo' => false])
            ->assertForbidden();

        $this->assertDatabaseHas('materias', ['id' => $materia->id, 'activo' => 1]);
    }
}
