<?php

namespace Tests\Feature;

use App\Models\AsignacionDocenteMateria;
use App\Models\Calificacion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReglasNegocioTest extends TestCase
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

        return compact('periodo', 'materia', 'propio', 'ajeno', 'usuarioDocente', 'docente');
    }

    public function test_administracion_puede_crear_calificacion_con_los_campos_del_formulario(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/calificaciones', [
            'estudiante_id' => $datos['propio']->id,
            'materia_id' => $datos['materia']->id,
            'periodo_academico_id' => $datos['periodo']->id,
            'nota' => 15,
        ])->assertCreated();

        $this->assertDatabaseHas('calificaciones', [
            'estudiante_id' => $datos['propio']->id,
            'materia_id' => $datos['materia']->id,
            'periodo_academico_id' => $datos['periodo']->id,
            'nota' => 15,
        ]);
    }

    public function test_asistencia_rechaza_un_estado_que_la_base_no_soporta(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/asistencias', [
            'estudiante_id' => $datos['propio']->id,
            'materia_id' => $datos['materia']->id,
            'fecha' => today()->toDateString(),
            'estado' => 'justificado',
        ])->assertStatus(422);
    }

    public function test_asistencia_rechaza_fechas_futuras(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/asistencias', [
            'estudiante_id' => $datos['propio']->id,
            'materia_id' => $datos['materia']->id,
            'fecha' => today()->addDay()->toDateString(),
            'estado' => 'presente',
        ])->assertStatus(422);
    }

    public function test_docente_solo_lista_estudiantes_de_sus_secciones(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['usuarioDocente']);

        $respuesta = $this->getJson('/api/estudiantes?all=true')->assertOk();

        $respuesta->assertJsonCount(1);
        $respuesta->assertJsonFragment(['id' => $datos['propio']->id]);
        $respuesta->assertJsonMissing(['id' => $datos['ajeno']->id]);
    }

    public function test_limite_de_tres_modificaciones_tambien_aplica_en_el_portal_docente(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['usuarioDocente']);

        $cuerpo = [
            'estudiante_id' => $datos['propio']->id,
            'materia_id' => $datos['materia']->id,
            'periodo_academico_id' => $datos['periodo']->id,
            'nota' => 10,
        ];

        $this->postJson('/api/docente/registrar-calificacion', $cuerpo)->assertCreated();

        foreach ([11, 12, 13] as $nota) {
            $this->postJson('/api/docente/registrar-calificacion', array_merge($cuerpo, ['nota' => $nota]))
                ->assertCreated();
        }

        $this->postJson('/api/docente/registrar-calificacion', array_merge($cuerpo, ['nota' => 14]))
            ->assertStatus(422);

        $calificacion = Calificacion::where('estudiante_id', $datos['propio']->id)
            ->where('materia_id', $datos['materia']->id)
            ->where('periodo_academico_id', $datos['periodo']->id)
            ->first();

        $this->assertSame(3, $calificacion->modificaciones_count);
        $this->assertEquals(13, $calificacion->nota);
    }
}
