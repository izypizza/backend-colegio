<?php

namespace Tests\Feature;

use App\Models\AsignacionDocenteMateria;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AsistenciasAutorizacionTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $periodo = PeriodoAcademico::create(['nombre' => 'Actual', 'anio' => 2026, 'estado' => 'activo']);
        $materia = Materia::factory()->create();

        $propio = Estudiante::factory()->create([
            'user_id' => User::factory()->create(['role' => 'estudiante'])->id,
        ]);
        $ajeno = Estudiante::factory()->create([
            'user_id' => User::factory()->create(['role' => 'estudiante'])->id,
        ]);

        $usuarioDocente = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuarioDocente->id]);

        AsignacionDocenteMateria::create([
            'docente_id' => $docente->id,
            'seccion_id' => $propio->seccion_id,
            'materia_id' => $materia->id,
            'periodo_academico_id' => $periodo->id,
        ]);

        $fecha = today()->subDay()->toDateString();

        $asistenciaPropia = Asistencia::create([
            'estudiante_id' => $propio->id,
            'materia_id' => $materia->id,
            'fecha' => $fecha,
            'estado' => Asistencia::ESTADO_PRESENTE,
        ]);
        $asistenciaAjena = Asistencia::create([
            'estudiante_id' => $ajeno->id,
            'materia_id' => $materia->id,
            'fecha' => $fecha,
            'estado' => Asistencia::ESTADO_AUSENTE,
        ]);

        return compact(
            'periodo',
            'materia',
            'propio',
            'ajeno',
            'usuarioDocente',
            'docente',
            'fecha',
            'asistenciaPropia',
            'asistenciaAjena'
        );
    }

    public function test_docente_solo_lee_asistencias_de_sus_asignaciones(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['usuarioDocente']);

        $this->getJson('/api/asistencias?per_page=50')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $datos['asistenciaPropia']->id);

        $this->getJson('/api/asistencias?seccion_id='.$datos['ajeno']->seccion_id)
            ->assertOk()
            ->assertJsonPath('total', 0);

        $this->getJson('/api/asistencias/'.$datos['asistenciaPropia']->id)->assertOk();
        $this->getJson('/api/asistencias/'.$datos['asistenciaAjena']->id)->assertNotFound();

        $this->getJson('/api/asistencias/reporte/estudiante/'.$datos['ajeno']->id)
            ->assertOk()
            ->assertJsonCount(0, 'asistencias')
            ->assertJsonPath('estadisticas.total', 0);

        $this->getJson('/api/asistencias/reporte/seccion/'.$datos['ajeno']->seccion_id.'?fecha='.$datos['fecha'])
            ->assertOk()
            ->assertJsonCount(0, 'asistencias');

        $this->getJson('/api/asistencias/reporte/seccion/'.$datos['propio']->seccion_id.'?fecha='.$datos['fecha'])
            ->assertOk()
            ->assertJsonCount(1, 'asistencias');
    }

    public function test_docente_no_puede_modificar_asistencia_ajena(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['usuarioDocente']);

        $this->putJson('/api/asistencias/'.$datos['asistenciaAjena']->id, ['estado' => Asistencia::ESTADO_PRESENTE])
            ->assertNotFound();

        $this->assertDatabaseHas('asistencias', [
            'id' => $datos['asistenciaAjena']->id,
            'estado' => Asistencia::ESTADO_AUSENTE,
        ]);

        $this->putJson('/api/asistencias/'.$datos['asistenciaPropia']->id, ['estado' => Asistencia::ESTADO_TARDE])
            ->assertOk();

        $this->assertDatabaseHas('asistencias', [
            'id' => $datos['asistenciaPropia']->id,
            'estado' => Asistencia::ESTADO_TARDE,
        ]);
    }

    public function test_docente_no_puede_registrar_calificacion_de_estudiante_ajeno(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['usuarioDocente']);

        $this->postJson('/api/docente/registrar-calificacion', [
            'estudiante_id' => $datos['ajeno']->id,
            'materia_id' => $datos['materia']->id,
            'periodo_academico_id' => $datos['periodo']->id,
            'nota' => 19,
        ])->assertForbidden();

        $this->assertDatabaseMissing('calificaciones', [
            'estudiante_id' => $datos['ajeno']->id,
            'materia_id' => $datos['materia']->id,
            'periodo_academico_id' => $datos['periodo']->id,
        ]);

        $this->postJson('/api/docente/registrar-calificacion', [
            'estudiante_id' => $datos['propio']->id,
            'materia_id' => $datos['materia']->id,
            'periodo_academico_id' => $datos['periodo']->id,
            'nota' => 17,
        ])->assertCreated();
    }

    public function test_docente_no_puede_registrar_calificacion_en_periodo_no_asignado(): void
    {
        $datos = $this->escenario();
        $otroPeriodo = PeriodoAcademico::create(['nombre' => 'Otro', 'anio' => 2027, 'estado' => 'inactivo']);
        Sanctum::actingAs($datos['usuarioDocente']);

        $this->postJson('/api/docente/registrar-calificacion', [
            'estudiante_id' => $datos['propio']->id,
            'materia_id' => $datos['materia']->id,
            'periodo_academico_id' => $otroPeriodo->id,
            'nota' => 20,
        ])->assertForbidden();
    }

    public function test_portal_docente_no_mezcla_materias_y_secciones(): void
    {
        $datos = $this->escenario();
        $otraMateria = Materia::factory()->create();

        AsignacionDocenteMateria::create([
            'docente_id' => $datos['docente']->id,
            'seccion_id' => $datos['ajeno']->seccion_id,
            'materia_id' => $otraMateria->id,
            'periodo_academico_id' => $datos['periodo']->id,
        ]);

        foreach ([
            [$datos['propio']->id, $datos['materia']->id],
            [$datos['ajeno']->id, $otraMateria->id],
            [$datos['ajeno']->id, $datos['materia']->id],
            [$datos['propio']->id, $otraMateria->id],
        ] as [$estudianteId, $materiaId]) {
            Calificacion::create([
                'estudiante_id' => $estudianteId,
                'materia_id' => $materiaId,
                'periodo_academico_id' => $datos['periodo']->id,
                'nota' => 15,
            ]);
        }

        Asistencia::create([
            'estudiante_id' => $datos['ajeno']->id,
            'materia_id' => $otraMateria->id,
            'fecha' => $datos['fecha'],
            'estado' => Asistencia::ESTADO_PRESENTE,
        ]);

        Sanctum::actingAs($datos['usuarioDocente']);

        $this->getJson('/api/docente/mis-calificaciones')
            ->assertOk()
            ->assertJsonCount(2, 'calificaciones');

        $this->getJson('/api/docente/mis-asistencias')
            ->assertOk()
            ->assertJsonCount(2, 'asistencias');
    }

    public function test_roles_sin_acceso_no_leen_asistencias(): void
    {
        $this->escenario();

        foreach (['estudiante', 'padre'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['role' => $rol]));
            $this->getJson('/api/asistencias')->assertForbidden();
        }
    }

    public function test_administracion_conserva_acceso_completo(): void
    {
        $datos = $this->escenario();

        foreach (['admin', 'auxiliar'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['role' => $rol]));
            $this->getJson('/api/asistencias?per_page=50')->assertOk()->assertJsonPath('total', 2);
            $this->getJson('/api/asistencias/'.$datos['asistenciaAjena']->id)->assertOk();
            $this->getJson('/api/asistencias/reporte/estudiante/'.$datos['ajeno']->id)
                ->assertOk()
                ->assertJsonCount(1, 'asistencias');
        }
    }
}
