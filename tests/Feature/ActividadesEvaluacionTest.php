<?php

namespace Tests\Feature;

use App\Models\ActividadEvaluacion;
use App\Models\AnioEscolar;
use App\Models\AsignacionDocenteMateria;
use App\Models\CalificacionCompetencia;
use App\Models\Competencia;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActividadesEvaluacionTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $periodo = PeriodoAcademico::create(['nombre' => 'I Bimestre', 'anio' => 2026, 'estado' => 'activo']);
        $grado = Grado::create(['nombre' => '2° Secundaria', 'nivel' => 'secundaria']);
        $seccion = Seccion::create([
            'nombre' => 'A', 'grado_id' => $grado->id, 'capacidad_maxima' => 30, 'turno' => 'Mañana',
        ]);
        $materia = Materia::factory()->create(['nombre' => 'Matemática']);
        $competencia = Competencia::create([
            'materia_id' => $materia->id, 'ciclo' => 'VI', 'codigo' => 'MATE-1',
            'nombre' => 'Resuelve problemas de cantidad', 'orden' => 1,
        ]);

        $usuario = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuario->id]);
        AsignacionDocenteMateria::create([
            'docente_id' => $docente->id, 'materia_id' => $materia->id,
            'seccion_id' => $seccion->id, 'periodo_academico_id' => $periodo->id,
        ]);

        $estudiante = Estudiante::factory()->create(['seccion_id' => $seccion->id]);

        return compact('periodo', 'grado', 'seccion', 'materia', 'competencia', 'usuario', 'estudiante');
    }

    private function actividad(int $competenciaId, int $periodoId, string $nombre, float $peso, int $orden = 1): ActividadEvaluacion
    {
        return ActividadEvaluacion::create([
            'competencia_id' => $competenciaId,
            'periodo_academico_id' => $periodoId,
            'nombre' => $nombre,
            'tipo' => 'examen',
            'peso' => $peso,
            'orden' => $orden,
        ]);
    }

    public function test_calcula_el_promedio_ponderado_de_las_evidencias(): void
    {
        ['periodo' => $periodo, 'competencia' => $competencia, 'usuario' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        $examen = $this->actividad($competencia->id, $periodo->id, 'Examen 1', 30, 1);
        $tarea = $this->actividad($competencia->id, $periodo->id, 'Tarea 1', 20, 2);
        Sanctum::actingAs($usuario);

        $this->postJson('/api/actividades-evaluacion/'.$examen->id.'/notas', [
            'registros' => [['estudiante_id' => $estudiante->id, 'nota' => 15]],
        ])->assertCreated();

        $this->postJson('/api/actividades-evaluacion/'.$tarea->id.'/notas', [
            'registros' => [['estudiante_id' => $estudiante->id, 'nota' => 13]],
        ])->assertCreated();

        // (15*30 + 13*20) / 50 = 14.2
        $this->assertDatabaseHas('calificaciones_competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'nota' => 14.20,
            'nivel' => 'A',
            'calculada_desde_actividades' => 1,
        ]);
    }

    public function test_normaliza_el_peso_cuando_no_suma_cien(): void
    {
        ['periodo' => $periodo, 'competencia' => $competencia, 'usuario' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        $primera = $this->actividad($competencia->id, $periodo->id, 'Practica 1', 1, 1);
        $segunda = $this->actividad($competencia->id, $periodo->id, 'Practica 2', 3, 2);
        Sanctum::actingAs($usuario);

        $this->postJson('/api/actividades-evaluacion/'.$primera->id.'/notas', [
            'registros' => [['estudiante_id' => $estudiante->id, 'nota' => 20]],
        ])->assertCreated();
        $this->postJson('/api/actividades-evaluacion/'.$segunda->id.'/notas', [
            'registros' => [['estudiante_id' => $estudiante->id, 'nota' => 10]],
        ])->assertCreated();

        // (20*1 + 10*3) / 4 = 12.5
        $this->assertDatabaseHas('calificaciones_competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'nota' => 12.50,
            'nivel' => 'B',
        ]);
    }

    public function test_una_nota_manual_no_se_marca_como_calculada(): void
    {
        ['periodo' => $periodo, 'competencia' => $competencia, 'usuario' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'literal',
            'nivel' => 'AD',
        ])->assertCreated();

        $this->assertDatabaseHas('calificaciones_competencias', [
            'estudiante_id' => $estudiante->id,
            'nivel' => 'AD',
            'calculada_desde_actividades' => 0,
        ]);
    }

    public function test_el_resumen_muestra_el_detalle_del_calculo(): void
    {
        ['periodo' => $periodo, 'competencia' => $competencia, 'usuario' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        $examen = $this->actividad($competencia->id, $periodo->id, 'Examen 1', 50, 1);
        $tarea = $this->actividad($competencia->id, $periodo->id, 'Tarea 1', 50, 2);
        Sanctum::actingAs($usuario);

        $this->postJson('/api/actividades-evaluacion/'.$examen->id.'/notas', [
            'registros' => [['estudiante_id' => $estudiante->id, 'nota' => 18]],
        ])->assertCreated();
        $this->postJson('/api/actividades-evaluacion/'.$tarea->id.'/notas', [
            'registros' => [['estudiante_id' => $estudiante->id, 'nota' => 16]],
        ])->assertCreated();

        $this->getJson('/api/actividades-evaluacion/resumen?competencia_id='.$competencia->id
            .'&periodo_academico_id='.$periodo->id.'&estudiante_id='.$estudiante->id)
            ->assertOk()
            ->assertJsonPath('resultado.promedio', 17)
            ->assertJsonPath('resultado.nivel', 'A')
            ->assertJsonPath('resultado.peso_total', 100)
            ->assertJsonCount(2, 'resultado.actividades');
    }

    public function test_rechaza_pesos_invalidos_y_nombres_duplicados(): void
    {
        ['periodo' => $periodo, 'competencia' => $competencia, 'usuario' => $usuario] = $this->escenario();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/actividades-evaluacion', [
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'nombre' => 'Examen 1',
            'tipo' => 'examen',
            'peso' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors('peso');

        $this->postJson('/api/actividades-evaluacion', [
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'nombre' => 'Examen 1',
            'tipo' => 'examen',
            'peso' => 25,
        ])->assertCreated();

        $this->postJson('/api/actividades-evaluacion', [
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'nombre' => 'Examen 1',
            'tipo' => 'examen',
            'peso' => 30,
        ])->assertStatus(422)->assertJsonValidationErrors('nombre');
    }

    public function test_no_se_registran_notas_en_un_anio_cerrado(): void
    {
        ['periodo' => $periodo, 'competencia' => $competencia, 'usuario' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        $examen = $this->actividad($competencia->id, $periodo->id, 'Examen 1', 100);
        AnioEscolar::create(['anio' => $periodo->anio, 'estado' => 'cerrado']);
        Sanctum::actingAs($usuario);

        $this->postJson('/api/actividades-evaluacion/'.$examen->id.'/notas', [
            'registros' => [['estudiante_id' => $estudiante->id, 'nota' => 15]],
        ])->assertStatus(422)->assertJsonValidationErrors('periodo_academico_id');

        $this->assertDatabaseCount('notas_actividad', 0);
    }

    public function test_un_docente_ajeno_no_gestiona_la_competencia(): void
    {
        ['periodo' => $periodo, 'competencia' => $competencia, 'estudiante' => $estudiante] = $this->escenario();
        $otro = User::factory()->create(['role' => 'docente']);
        Docente::factory()->create(['user_id' => $otro->id]);
        Sanctum::actingAs($otro);

        $this->postJson('/api/actividades-evaluacion', [
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'nombre' => 'Examen 1',
            'tipo' => 'examen',
            'peso' => 100,
        ])->assertForbidden();

        $this->assertDatabaseCount('actividades_evaluacion', 0);
    }
}
