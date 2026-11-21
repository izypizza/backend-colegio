<?php

namespace Tests\Feature;

use App\Models\AsignacionDocenteMateria;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Padre;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortalPadreTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $periodo = PeriodoAcademico::create(['nombre' => 'I Bimestre', 'anio' => 2026, 'estado' => 'activo']);
        $grado = Grado::create(['nombre' => '4° Secundaria', 'nivel' => 'secundaria']);
        $seccion = Seccion::create([
            'nombre' => 'A', 'grado_id' => $grado->id, 'capacidad_maxima' => 30, 'turno' => 'Mañana',
        ]);
        $materia = Materia::factory()->create(['nombre' => 'Matemática']);
        $docente = Docente::factory()->create([
            'nombres' => 'Roberto', 'apellido_paterno' => 'García', 'apellido_materno' => 'López',
        ]);
        AsignacionDocenteMateria::create([
            'docente_id' => $docente->id, 'materia_id' => $materia->id,
            'seccion_id' => $seccion->id, 'periodo_academico_id' => $periodo->id,
        ]);

        Horario::create([
            'seccion_id' => $seccion->id, 'materia_id' => $materia->id,
            'dia' => 'Miércoles', 'hora_inicio' => '09:45:00', 'hora_fin' => '11:15:00',
        ]);
        Horario::create([
            'seccion_id' => $seccion->id, 'materia_id' => $materia->id,
            'dia' => 'Lunes', 'hora_inicio' => '08:00:00', 'hora_fin' => '09:30:00',
        ]);

        $usuarioPadre = User::factory()->create(['role' => 'padre']);
        $padre = Padre::factory()->create(['user_id' => $usuarioPadre->id]);

        $hijo = Estudiante::factory()->create([
            'seccion_id' => $seccion->id,
            'nombres' => 'Ana Lucía',
            'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Huamán',
        ]);
        $padre->estudiantes()->attach($hijo->id);

        return compact('periodo', 'seccion', 'materia', 'usuarioPadre', 'padre', 'hijo', 'docente');
    }

    public function test_el_padre_ve_el_horario_de_su_hijo_con_su_nombre(): void
    {
        ['usuarioPadre' => $usuario, 'hijo' => $hijo] = $this->escenario();
        Sanctum::actingAs($usuario);

        $respuesta = $this->getJson('/api/padre/horario-hijo/'.$hijo->id)
            ->assertOk()
            ->assertJsonPath('hijo.nombre_completo', $hijo->nombre_completo)
            ->assertJsonPath('hijo.ciclo', 'VII')
            ->assertJsonCount(2, 'bloques')
            ->assertJsonPath('bloques.0.dia', 'Lunes')
            ->assertJsonPath('bloques.0.materia', 'Matemática')
            ->assertJsonPath('bloques.0.docente', 'García López, Roberto')
            ->json();

        $this->assertNotEmpty($respuesta['hijo']['seccion']);
    }

    public function test_el_padre_no_ve_el_horario_de_un_estudiante_ajeno(): void
    {
        ['usuarioPadre' => $usuario, 'seccion' => $seccion] = $this->escenario();
        $ajeno = Estudiante::factory()->create(['seccion_id' => $seccion->id]);
        Sanctum::actingAs($usuario);

        $this->getJson('/api/padre/horario-hijo/'.$ajeno->id)->assertNotFound();
    }

    public function test_un_rol_distinto_no_accede_al_portal_de_padres(): void
    {
        ['hijo' => $hijo] = $this->escenario();
        Sanctum::actingAs(User::factory()->create(['role' => 'docente']));

        $this->getJson('/api/padre/horario-hijo/'.$hijo->id)->assertForbidden();
    }
}
