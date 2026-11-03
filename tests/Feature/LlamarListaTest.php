<?php

namespace Tests\Feature;

use App\Models\AsignacionDocenteMateria;
use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LlamarListaTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $periodo = PeriodoAcademico::create(['nombre' => 'Actual', 'anio' => 2026, 'estado' => 'activo']);
        $materia = Materia::factory()->create();

        $primero = Estudiante::factory()->create([
            'user_id' => User::factory()->create(['role' => 'estudiante'])->id,
        ]);
        $segundo = Estudiante::factory()->create([
            'user_id' => User::factory()->create(['role' => 'estudiante'])->id,
            'seccion_id' => $primero->seccion_id,
        ]);
        $ajeno = Estudiante::factory()->create([
            'user_id' => User::factory()->create(['role' => 'estudiante'])->id,
        ]);

        $usuarioDocente = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuarioDocente->id]);

        AsignacionDocenteMateria::create([
            'docente_id' => $docente->id,
            'seccion_id' => $primero->seccion_id,
            'materia_id' => $materia->id,
            'periodo_academico_id' => $periodo->id,
        ]);

        return compact('periodo', 'materia', 'primero', 'segundo', 'ajeno', 'usuarioDocente', 'docente');
    }

    public function test_docente_registra_la_lista_de_su_seccion(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['usuarioDocente']);

        $cuerpo = [
            'materia_id' => $datos['materia']->id,
            'seccion_id' => $datos['primero']->seccion_id,
            'fecha' => today()->toDateString(),
            'registros' => [
                ['estudiante_id' => $datos['primero']->id, 'estado' => 'presente'],
                ['estudiante_id' => $datos['segundo']->id, 'estado' => 'ausente', 'observaciones' => 'Sin justificar'],
            ],
        ];

        $this->postJson('/api/docente/llamar-lista', $cuerpo)
            ->assertOk()
            ->assertJson(['creados' => 2, 'actualizados' => 0, 'total' => 2]);

        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $datos['segundo']->id,
            'materia_id' => $datos['materia']->id,
            'estado' => 'ausente',
        ]);

        $cuerpo['registros'][1]['estado'] = 'tarde';

        $this->postJson('/api/docente/llamar-lista', $cuerpo)
            ->assertOk()
            ->assertJson(['creados' => 0, 'actualizados' => 2]);

        $this->assertSame(2, Asistencia::count());
        $this->assertDatabaseHas('asistencias', [
            'estudiante_id' => $datos['segundo']->id,
            'estado' => 'tarde',
        ]);
    }

    public function test_docente_no_registra_en_una_seccion_ajena(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['usuarioDocente']);

        $this->postJson('/api/docente/llamar-lista', [
            'materia_id' => $datos['materia']->id,
            'seccion_id' => $datos['ajeno']->seccion_id,
            'fecha' => today()->toDateString(),
            'registros' => [
                ['estudiante_id' => $datos['ajeno']->id, 'estado' => 'presente'],
            ],
        ])->assertForbidden();

        $this->assertSame(0, Asistencia::count());
    }

    public function test_no_permite_fechas_futuras(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['usuarioDocente']);

        $this->postJson('/api/docente/llamar-lista', [
            'materia_id' => $datos['materia']->id,
            'seccion_id' => $datos['primero']->seccion_id,
            'fecha' => today()->addDay()->toDateString(),
            'registros' => [
                ['estudiante_id' => $datos['primero']->id, 'estado' => 'presente'],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, Asistencia::count());
    }

    public function test_estudiantes_no_pueden_llamar_lista(): void
    {
        $datos = $this->escenario();
        Sanctum::actingAs($datos['primero']->user);

        $this->postJson('/api/docente/llamar-lista', [
            'materia_id' => $datos['materia']->id,
            'seccion_id' => $datos['primero']->seccion_id,
            'fecha' => today()->toDateString(),
            'registros' => [
                ['estudiante_id' => $datos['primero']->id, 'estado' => 'presente'],
            ],
        ])->assertForbidden();
    }
}
