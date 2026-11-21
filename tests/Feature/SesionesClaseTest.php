<?php

namespace Tests\Feature;

use App\Models\AsignacionDocenteMateria;
use App\Models\Asistencia;
use App\Models\AsistenciaSesion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\SesionClase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SesionesClaseTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $periodo = PeriodoAcademico::create(['nombre' => 'I Bimestre', 'anio' => 2026, 'estado' => 'activo']);
        $grado = Grado::create(['nombre' => '2° Secundaria', 'nivel' => 'secundaria']);
        $seccion = Seccion::create([
            'nombre' => 'B', 'grado_id' => $grado->id, 'capacidad_maxima' => 30, 'turno' => 'Mañana',
        ]);
        $materia = Materia::factory()->create(['nombre' => 'Comunicación']);

        $usuarioDocente = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuarioDocente->id]);
        $asignacion = AsignacionDocenteMateria::create([
            'docente_id' => $docente->id, 'materia_id' => $materia->id,
            'seccion_id' => $seccion->id, 'periodo_academico_id' => $periodo->id,
        ]);

        $primero = Estudiante::factory()->create(['seccion_id' => $seccion->id]);
        $segundo = Estudiante::factory()->create(['seccion_id' => $seccion->id]);

        return compact('periodo', 'grado', 'seccion', 'materia', 'usuarioDocente', 'docente', 'asignacion', 'primero', 'segundo');
    }

    public function test_docente_abre_una_sesion_y_llama_lista(): void
    {
        ['asignacion' => $asignacion, 'usuarioDocente' => $usuario, 'primero' => $primero, 'segundo' => $segundo] = $this->escenario();
        Sanctum::actingAs($usuario);

        $sesion = $this->postJson('/api/sesiones-clase', [
            'asignacion_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '08:00',
            'tema' => 'Comprensión lectora',
        ])->assertCreated()->json('sesion');

        $this->postJson('/api/sesiones-clase/'.$sesion['id'].'/asistencias', [
            'registros' => [
                ['estudiante_id' => $primero->id, 'estado' => 'presente'],
                ['estudiante_id' => $segundo->id, 'estado' => 'tarde', 'minutos_tardanza' => 10],
            ],
        ])->assertCreated()
            ->assertJsonPath('resultado.creados', 2)
            ->assertJsonPath('resumen.presentes', 2)
            ->assertJsonPath('resumen.ausentes', 0);

        $this->assertDatabaseHas('asistencias_sesion', ['estudiante_id' => $segundo->id, 'estado' => 'tarde']);
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $segundo->id, 'materia_id' => $asignacion->materia_id, 'estado' => 'tarde',
        ]);
    }

    public function test_llamar_lista_dos_veces_actualiza_sin_duplicar(): void
    {
        ['asignacion' => $asignacion, 'usuarioDocente' => $usuario, 'primero' => $primero] = $this->escenario();
        Sanctum::actingAs($usuario);

        $sesion = SesionClase::create([
            'asignacion_docente_materia_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '10:00:00',
        ]);

        $this->postJson('/api/sesiones-clase/'.$sesion->id.'/asistencias', [
            'registros' => [['estudiante_id' => $primero->id, 'estado' => 'ausente']],
        ])->assertCreated();

        $this->postJson('/api/sesiones-clase/'.$sesion->id.'/asistencias', [
            'registros' => [['estudiante_id' => $primero->id, 'estado' => 'justificado', 'justificacion' => 'Cita médica']],
        ])->assertCreated()->assertJsonPath('resultado.actualizados', 1);

        $this->assertDatabaseCount('asistencias_sesion', 1);
        $this->assertDatabaseHas('asistencias_sesion', ['estudiante_id' => $primero->id, 'estado' => 'justificado']);
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $primero->id, 'estado' => 'ausente', 'justificada' => 1,
        ]);
    }

    public function test_dos_sesiones_el_mismo_dia_no_se_mezclan(): void
    {
        ['asignacion' => $asignacion, 'usuarioDocente' => $usuario, 'primero' => $primero] = $this->escenario();
        Sanctum::actingAs($usuario);

        $primera = SesionClase::create([
            'asignacion_docente_materia_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '08:00:00',
        ]);
        $segunda = SesionClase::create([
            'asignacion_docente_materia_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '11:00:00',
        ]);

        $this->assertNotSame($primera->id, $segunda->id);

        $this->postJson('/api/sesiones-clase/'.$primera->id.'/asistencias', [
            'registros' => [['estudiante_id' => $primero->id, 'estado' => 'presente']],
        ])->assertCreated();
        $this->postJson('/api/sesiones-clase/'.$segunda->id.'/asistencias', [
            'registros' => [['estudiante_id' => $primero->id, 'estado' => 'ausente']],
        ])->assertCreated();

        $this->assertDatabaseCount('asistencias_sesion', 2);
    }

    public function test_no_se_aceptan_estudiantes_de_otra_seccion(): void
    {
        ['asignacion' => $asignacion, 'usuarioDocente' => $usuario] = $this->escenario();
        $otraSeccion = Seccion::create([
            'nombre' => 'C', 'grado_id' => $asignacion->seccion->grado_id, 'capacidad_maxima' => 30, 'turno' => 'Tarde',
        ]);
        $ajeno = Estudiante::factory()->create(['seccion_id' => $otraSeccion->id]);
        Sanctum::actingAs($usuario);

        $sesion = SesionClase::create([
            'asignacion_docente_materia_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '09:00:00',
        ]);

        $this->postJson('/api/sesiones-clase/'.$sesion->id.'/asistencias', [
            'registros' => [['estudiante_id' => $ajeno->id, 'estado' => 'presente']],
        ])->assertStatus(422);

        $this->assertDatabaseCount('asistencias_sesion', 0);
    }

    public function test_un_docente_ajeno_no_puede_registrar_en_la_sesion(): void
    {
        ['asignacion' => $asignacion, 'primero' => $primero] = $this->escenario();
        $intruso = User::factory()->create(['role' => 'docente']);
        Docente::factory()->create(['user_id' => $intruso->id]);
        Sanctum::actingAs($intruso);

        $sesion = SesionClase::create([
            'asignacion_docente_materia_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '07:00:00',
        ]);

        $this->postJson('/api/sesiones-clase/'.$sesion->id.'/asistencias', [
            'registros' => [['estudiante_id' => $primero->id, 'estado' => 'presente']],
        ])->assertForbidden();

        $this->assertDatabaseCount('asistencias_sesion', 0);
    }

    public function test_una_sesion_anulada_no_admite_registros(): void
    {
        ['asignacion' => $asignacion, 'usuarioDocente' => $usuario, 'primero' => $primero] = $this->escenario();
        Sanctum::actingAs($usuario);

        $sesion = SesionClase::create([
            'asignacion_docente_materia_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '12:00:00',
        ]);

        $this->putJson('/api/sesiones-clase/'.$sesion->id.'/anular')->assertOk()
            ->assertJsonPath('sesion.estado', 'anulada');

        $this->postJson('/api/sesiones-clase/'.$sesion->id.'/asistencias', [
            'registros' => [['estudiante_id' => $primero->id, 'estado' => 'presente']],
        ])->assertStatus(422);
    }

    public function test_la_hoja_de_asistencia_lista_la_seccion_con_su_estado(): void
    {
        ['asignacion' => $asignacion, 'usuarioDocente' => $usuario, 'primero' => $primero, 'segundo' => $segundo] = $this->escenario();
        Sanctum::actingAs($usuario);

        $sesion = SesionClase::create([
            'asignacion_docente_materia_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '13:00:00',
        ]);

        AsistenciaSesion::create([
            'sesion_clase_id' => $sesion->id, 'estudiante_id' => $primero->id, 'estado' => 'presente',
        ]);

        $this->getJson('/api/sesiones-clase/'.$sesion->id.'/hoja')
            ->assertOk()
            ->assertJsonCount(2, 'estudiantes');
    }

    public function test_el_docente_solo_ve_sus_propias_sesiones(): void
    {
        ['asignacion' => $asignacion, 'usuarioDocente' => $usuario] = $this->escenario();
        SesionClase::create([
            'asignacion_docente_materia_id' => $asignacion->id,
            'fecha' => today()->toDateString(),
            'hora_inicio' => '15:00:00',
        ]);
        Sanctum::actingAs($usuario);

        $this->getJson('/api/sesiones-clase')->assertOk()->assertJsonPath('total', 1);

        $otro = User::factory()->create(['role' => 'docente']);
        Docente::factory()->create(['user_id' => $otro->id]);
        Sanctum::actingAs($otro);

        $this->getJson('/api/sesiones-clase')->assertOk()->assertJsonPath('total', 0);
    }
}
