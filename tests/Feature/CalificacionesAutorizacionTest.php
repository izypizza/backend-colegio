<?php

namespace Tests\Feature;

use App\Models\AsignacionDocenteMateria;
use App\Models\Calificacion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Padre;
use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CalificacionesAutorizacionTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $periodo = PeriodoAcademico::create(['nombre' => 'Actual', 'anio' => 2026, 'estado' => 'activo']);
        $materia = Materia::factory()->create();
        $propio = Estudiante::factory()->create(['user_id' => User::factory()->create(['role' => 'estudiante'])->id]);
        $ajeno = Estudiante::factory()->create(['user_id' => User::factory()->create(['role' => 'estudiante'])->id]);
        $notas = [];
        foreach ([$propio, $ajeno] as $estudiante) {
            $notas[] = Calificacion::create(['estudiante_id' => $estudiante->id, 'materia_id' => $materia->id,
                'periodo_academico_id' => $periodo->id, 'nota' => 15]);
        }

        return [$propio, $ajeno, $notas, $periodo, $materia];
    }

    private function comprobarLecturas(User $usuario, Calificacion $propia, Calificacion $ajena): void
    {
        Sanctum::actingAs($usuario);
        $this->getJson('/api/calificaciones?todos=1')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $propia->id);
        $this->getJson('/api/calificaciones?estudiante_id='.$ajena->estudiante_id)->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/calificaciones/'.$ajena->id)->assertNotFound();
        $this->getJson('/api/calificaciones/'.$propia->id)->assertOk();
        $this->getJson('/api/calificaciones/boletin/'.$ajena->estudiante_id.'/'.$ajena->periodo_academico_id)
            ->assertOk()->assertJsonCount(0, 'calificaciones');
        $this->getJson('/api/calificaciones/reporte/materia/'.$propia->materia_id)
            ->assertOk()->assertJsonCount(1, 'calificaciones');
        $this->getJson('/api/calificaciones/estadisticas-avanzadas')->assertOk()->assertJsonPath('general.total', 1);
    }

    public function test_estudiante_solo_lee_sus_calificaciones(): void
    {
        [$propio, , $notas] = $this->escenario();
        $this->comprobarLecturas($propio->user, $notas[0], $notas[1]);
    }

    public function test_padre_solo_lee_notas_de_sus_hijos(): void
    {
        [$propio, , $notas] = $this->escenario();
        $usuario = User::factory()->create(['role' => 'padre']);
        $padre = Padre::factory()->create(['user_id' => $usuario->id]);
        $padre->estudiantes()->attach($propio->id);
        $this->comprobarLecturas($usuario, $notas[0], $notas[1]);
    }

    public function test_usuario_sin_relacion_no_recibe_calificaciones(): void
    {
        $this->escenario();
        foreach (['estudiante', 'padre', 'docente'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['role' => $rol]));
            $this->getJson('/api/calificaciones?todos=1')->assertOk()->assertJsonPath('total', 0);
        }
    }

    public function test_docente_respeta_materia_seccion_y_periodo_y_no_puede_mover_nota_ajena(): void
    {
        [$propio, $ajeno, $notas, $periodo, $materia] = $this->escenario();
        $usuario = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuario->id]);
        AsignacionDocenteMateria::create(['docente_id' => $docente->id, 'seccion_id' => $propio->seccion_id,
            'materia_id' => $materia->id, 'periodo_academico_id' => $periodo->id]);
        $otraMateria = Materia::factory()->create();
        Calificacion::create(['estudiante_id' => $propio->id, 'materia_id' => $otraMateria->id,
            'periodo_academico_id' => $periodo->id, 'nota' => 12]);
        $otroPeriodo = PeriodoAcademico::create(['nombre' => 'Futuro', 'anio' => 2027, 'estado' => 'inactivo']);
        Calificacion::create(['estudiante_id' => $propio->id, 'materia_id' => $materia->id,
            'periodo_academico_id' => $otroPeriodo->id, 'nota' => 12]);
        $this->comprobarLecturas($usuario, $notas[0], $notas[1]);
        $this->putJson('/api/calificaciones/'.$notas[1]->id, ['estudiante_id' => $propio->id, 'nota' => 18])
            ->assertNotFound();
        $this->assertDatabaseHas('calificaciones', ['id' => $notas[1]->id, 'estudiante_id' => $ajeno->id, 'nota' => 15]);
    }

    public function test_docente_no_puede_escribir_en_periodo_no_asignado(): void
    {
        [$propio, , $notas, $periodo, $materia] = $this->escenario();
        $usuario = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuario->id]);
        AsignacionDocenteMateria::create(['docente_id' => $docente->id, 'seccion_id' => $propio->seccion_id,
            'materia_id' => $materia->id, 'periodo_academico_id' => $periodo->id]);
        $otro = PeriodoAcademico::create(['nombre' => 'Otro', 'anio' => 2027, 'estado' => 'inactivo']);
        Sanctum::actingAs($usuario);
        $this->putJson('/api/calificaciones/'.$notas[0]->id, ['periodo_academico_id' => $otro->id])->assertForbidden();
        $this->postJson('/api/calificaciones', ['estudiante_id' => $propio->id, 'materia_id' => $materia->id,
            'periodo_academico_id' => $otro->id, 'nota' => 16, 'tipo_evaluacion' => 'final', 'fecha' => today()->toDateString()])
            ->assertForbidden();
        $this->putJson('/api/calificaciones/'.$notas[0]->id, ['nota' => 16])->assertOk();
        $this->assertDatabaseHas('calificaciones', ['id' => $notas[0]->id, 'nota' => 16, 'periodo_academico_id' => $periodo->id]);
    }

    public function test_tutor_lee_otras_materias_solo_mientras_su_tutoria_esta_vigente(): void
    {
        [$propio, , $notas, $periodo] = $this->escenario();
        $usuario = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuario->id]);
        $asignacion = AsignacionDocenteMateria::create(['docente_id' => $docente->id, 'seccion_id' => $propio->seccion_id,
            'materia_id' => Materia::factory()->create()->id, 'periodo_academico_id' => $periodo->id,
            'es_tutor' => true, 'tutor_hasta' => now()->addDay()]);
        Sanctum::actingAs($usuario);
        $this->getJson('/api/calificaciones?todos=1')->assertOk()->assertJsonPath('total', 1);
        $this->putJson('/api/calificaciones/'.$notas[0]->id, ['nota' => 18])->assertForbidden();
        $asignacion->update(['tutor_hasta' => now()->subMinute()]);
        $this->getJson('/api/calificaciones?todos=1')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_administracion_conserva_su_acceso(): void
    {
        $this->escenario();
        foreach (['admin', 'auxiliar'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['role' => $rol]));
            $this->getJson('/api/calificaciones?todos=1')->assertOk()->assertJsonPath('total', 2);
        }
    }
}
