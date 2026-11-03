<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JustificacionAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $materia = Materia::factory()->create();
        $estudiante = Estudiante::factory()->create([
            'user_id' => User::factory()->create(['role' => 'estudiante'])->id,
        ]);

        $ausente = Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'fecha' => today()->toDateString(),
            'estado' => Asistencia::ESTADO_AUSENTE,
        ]);

        $presente = Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'materia_id' => $materia->id,
            'fecha' => today()->subDay()->toDateString(),
            'estado' => Asistencia::ESTADO_PRESENTE,
        ]);

        $auxiliar = User::factory()->create(['role' => 'auxiliar']);
        $docente = User::factory()->create(['role' => 'docente']);

        return compact('ausente', 'presente', 'auxiliar', 'docente');
    }

    public function test_auxiliar_justifica_una_falta_con_motivo(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['auxiliar']);

        $this->putJson("/api/asistencias/{$datos['ausente']->id}/justificar", [
            'justificada' => true,
            'motivo' => 'Cita médica en el Hospital Regional de Cusco',
        ])->assertOk();

        $asistencia = $datos['ausente']->fresh();

        $this->assertTrue($asistencia->justificada);
        $this->assertSame('Cita médica en el Hospital Regional de Cusco', $asistencia->motivo_justificacion);
        $this->assertSame($datos['auxiliar']->id, $asistencia->justificado_por);
        $this->assertNotNull($asistencia->justificado_at);
    }

    public function test_no_se_justifica_sin_motivo(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['auxiliar']);

        $this->putJson("/api/asistencias/{$datos['ausente']->id}/justificar", [
            'justificada' => true,
        ])->assertStatus(422);

        $this->assertFalse($datos['ausente']->fresh()->justificada);
    }

    public function test_docente_no_puede_justificar(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['docente']);

        $this->putJson("/api/asistencias/{$datos['ausente']->id}/justificar", [
            'justificada' => true,
            'motivo' => 'Intento del docente',
        ])->assertForbidden();

        $this->assertFalse($datos['ausente']->fresh()->justificada);
    }

    public function test_no_se_justifica_una_asistencia_presente(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['auxiliar']);

        $this->putJson("/api/asistencias/{$datos['presente']->id}/justificar", [
            'justificada' => true,
            'motivo' => 'No corresponde',
        ])->assertStatus(422);
    }

    public function test_se_puede_retirar_la_justificacion(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['auxiliar']);

        $this->putJson("/api/asistencias/{$datos['ausente']->id}/justificar", [
            'justificada' => true,
            'motivo' => 'Permiso',
        ])->assertOk();

        $this->putJson("/api/asistencias/{$datos['ausente']->id}/justificar", [
            'justificada' => false,
        ])->assertOk();

        $asistencia = $datos['ausente']->fresh();

        $this->assertFalse($asistencia->justificada);
        $this->assertNull($asistencia->motivo_justificacion);
        $this->assertNull($asistencia->justificado_por);
        $this->assertNull($asistencia->justificado_at);
    }
}
