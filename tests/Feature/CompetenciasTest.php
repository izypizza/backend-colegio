<?php

namespace Tests\Feature;

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

class CompetenciasTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $periodo = PeriodoAcademico::create(['nombre' => 'I Bimestre', 'anio' => 2026, 'estado' => 'activo']);
        $grado = Grado::create(['nombre' => '3° Secundaria', 'nivel' => 'secundaria']);
        $seccion = Seccion::create([
            'nombre' => 'A', 'grado_id' => $grado->id, 'capacidad_maxima' => 30, 'turno' => 'Mañana',
        ]);
        $materia = Materia::factory()->create(['nombre' => 'Matemática']);

        $competencia1 = Competencia::create([
            'materia_id' => $materia->id, 'ciclo' => 'VII', 'codigo' => 'MATE-1',
            'nombre' => 'Resuelve problemas de cantidad', 'orden' => 1,
        ]);
        $competencia2 = Competencia::create([
            'materia_id' => $materia->id, 'ciclo' => 'VII', 'codigo' => 'MATE-2',
            'nombre' => 'Resuelve problemas de forma, movimiento y localización', 'orden' => 2,
        ]);

        $usuarioDocente = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuarioDocente->id]);
        AsignacionDocenteMateria::create([
            'docente_id' => $docente->id, 'materia_id' => $materia->id,
            'seccion_id' => $seccion->id, 'periodo_academico_id' => $periodo->id,
        ]);

        $estudiante = Estudiante::factory()->create([
            'seccion_id' => $seccion->id,
            'user_id' => User::factory()->create(['role' => 'estudiante'])->id,
        ]);

        return compact('periodo', 'grado', 'seccion', 'materia', 'competencia1', 'competencia2', 'usuarioDocente', 'estudiante');
    }

    public function test_el_grado_deriva_su_ciclo_del_curriculo(): void
    {
        $primero = Grado::create(['nombre' => '1° Primaria', 'nivel' => 'primaria']);
        $cuarto = Grado::create(['nombre' => '4° Primaria', 'nivel' => 'primaria']);
        $sexto = Grado::create(['nombre' => '6° Primaria', 'nivel' => 'primaria']);
        $segundoSecundaria = Grado::create(['nombre' => '2° Secundaria', 'nivel' => 'secundaria']);
        $quintoSecundaria = Grado::create(['nombre' => '5° Secundaria', 'nivel' => 'secundaria']);

        $this->assertSame('III', $primero->ciclo);
        $this->assertSame('IV', $cuarto->ciclo);
        $this->assertSame('V', $sexto->ciclo);
        $this->assertSame('VI', $segundoSecundaria->ciclo);
        $this->assertSame('VII', $quintoSecundaria->ciclo);
    }

    public function test_docente_registra_nivel_literal_en_su_competencia(): void
    {
        ['periodo' => $periodo, 'competencia1' => $competencia, 'usuarioDocente' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'literal',
            'nivel' => 'ad',
        ])->assertCreated()
            ->assertJsonPath('calificacion.nivel', 'AD')
            ->assertJsonPath('calificacion.nota', null);

        $this->assertDatabaseHas('calificaciones_competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'nivel' => 'AD',
        ]);
    }

    public function test_los_niveles_b_y_c_exigen_conclusion_descriptiva(): void
    {
        ['periodo' => $periodo, 'competencia1' => $competencia, 'usuarioDocente' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'literal',
            'nivel' => 'B',
        ])->assertStatus(422)->assertJsonValidationErrors('conclusion_descriptiva');

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'literal',
            'nivel' => 'B',
            'conclusion_descriptiva' => 'Resuelve operaciones básicas pero aún confunde el orden de las cifras.',
        ])->assertCreated();

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'literal',
            'nivel' => 'AD',
        ])->assertCreated()->assertJsonPath('calificacion.conclusion_descriptiva', null);
    }

    public function test_la_escala_numerica_convierte_la_nota_en_nivel(): void
    {
        ['periodo' => $periodo, 'competencia1' => $competencia, 'usuarioDocente' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'numerica',
            'nota' => 16,
        ])->assertCreated()
            ->assertJsonPath('calificacion.nivel', 'A')
            ->assertJsonPath('calificacion.nota', '16.00');

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'numerica',
            'nota' => 25,
        ])->assertStatus(422)->assertJsonValidationErrors('nota');
    }

    public function test_docente_ajeno_no_puede_calificar_la_seccion(): void
    {
        ['periodo' => $periodo, 'competencia1' => $competencia, 'estudiante' => $estudiante] = $this->escenario();
        $otroUsuario = User::factory()->create(['role' => 'docente']);
        Docente::factory()->create(['user_id' => $otroUsuario->id]);
        Sanctum::actingAs($otroUsuario);

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'literal',
            'nivel' => 'A',
        ])->assertForbidden();
    }

    public function test_el_consolidado_muestra_una_letra_por_competencia_sin_promediar(): void
    {
        ['periodo' => $periodo, 'competencia1' => $competencia1, 'competencia2' => $competencia2, 'usuarioDocente' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        Sanctum::actingAs($usuario);

        CalificacionCompetencia::create([
            'estudiante_id' => $estudiante->id, 'competencia_id' => $competencia1->id,
            'periodo_academico_id' => $periodo->id, 'escala' => 'literal', 'nivel' => 'AD',
        ]);
        CalificacionCompetencia::create([
            'estudiante_id' => $estudiante->id, 'competencia_id' => $competencia2->id,
            'periodo_academico_id' => $periodo->id, 'escala' => 'literal', 'nivel' => 'B',
            'conclusion_descriptiva' => 'Requiere apoyo en ubicación espacial.',
        ]);

        $respuesta = $this->getJson('/api/calificaciones-competencias/consolidado/'.$estudiante->id.'/'.$periodo->id)
            ->assertOk()
            ->assertJsonPath('areas.0.competencias.0.nivel', 'AD')
            ->assertJsonPath('areas.0.competencias.1.nivel', 'B')
            ->assertJsonPath('areas.0.requiere_conclusion', true)
            ->json();

        $this->assertArrayNotHasKey('promedio', $respuesta['areas'][0]);
        $this->assertArrayNotHasKey('nota_area', $respuesta['areas'][0]);
    }

    public function test_un_estudiante_no_puede_leer_el_consolidado_de_un_companero(): void
    {
        ['periodo' => $periodo, 'estudiante' => $estudiante] = $this->escenario();
        $otro = Estudiante::factory()->create([
            'seccion_id' => $estudiante->seccion_id,
            'user_id' => User::factory()->create(['role' => 'estudiante'])->id,
        ]);
        Sanctum::actingAs($otro->user);

        $this->getJson('/api/calificaciones-competencias/consolidado/'.$estudiante->id.'/'.$periodo->id)
            ->assertForbidden();
    }

    public function test_no_se_califica_competencias_de_un_anio_cerrado(): void
    {
        ['periodo' => $periodo, 'competencia1' => $competencia, 'usuarioDocente' => $usuario, 'estudiante' => $estudiante] = $this->escenario();
        AnioEscolar::create(['anio' => $periodo->anio, 'estado' => 'cerrado']);
        Sanctum::actingAs($usuario);

        $this->postJson('/api/calificaciones-competencias', [
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'literal',
            'nivel' => 'A',
        ])->assertStatus(422)->assertJsonValidationErrors('periodo_academico_id');
    }

    public function test_solo_administracion_gestiona_el_catalogo(): void
    {
        ['materia' => $materia] = $this->escenario();

        Sanctum::actingAs(User::factory()->create(['role' => 'docente']));
        $this->postJson('/api/competencias', [
            'materia_id' => $materia->id, 'ciclo' => 'VII', 'codigo' => 'X-1', 'nombre' => 'Competencia docente',
        ])->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/competencias', [
            'materia_id' => $materia->id, 'ciclo' => 'VII', 'codigo' => 'MATE-3',
            'nombre' => 'Resuelve problemas de gestión de datos e incertidumbre',
        ])->assertCreated();

        $this->postJson('/api/competencias', [
            'materia_id' => $materia->id, 'ciclo' => 'VII', 'codigo' => 'MATE-3', 'nombre' => 'Duplicada',
        ])->assertStatus(422)->assertJsonValidationErrors('codigo');

        $this->getJson('/api/competencias?all=1')->assertOk()->assertJsonCount(3);
    }

    public function test_el_libro_de_curso_agrupa_estudiantes_y_competencias(): void
    {
        [
            'periodo' => $periodo, 'seccion' => $seccion, 'materia' => $materia,
            'competencia1' => $competencia1, 'usuarioDocente' => $usuario, 'estudiante' => $estudiante,
        ] = $this->escenario();

        CalificacionCompetencia::create([
            'estudiante_id' => $estudiante->id,
            'competencia_id' => $competencia1->id,
            'periodo_academico_id' => $periodo->id,
            'escala' => 'literal',
            'nivel' => 'AD',
        ]);

        Sanctum::actingAs($usuario);

        $this->getJson('/api/calificaciones-competencias/libro?seccion_id='.$seccion->id
            .'&materia_id='.$materia->id.'&periodo_academico_id='.$periodo->id)
            ->assertOk()
            ->assertJsonCount(2, 'competencias')
            ->assertJsonCount(1, 'estudiantes')
            ->assertJsonPath('seccion.ciclo', 'VII')
            ->assertJsonPath('estudiantes.0.niveles.0.nivel', 'AD')
            ->assertJsonPath('estudiantes.0.niveles.1.nivel', null)
            ->assertJsonPath('estudiantes.0.registradas', 1);
    }

    public function test_el_libro_de_curso_esta_cerrado_para_docentes_ajenos(): void
    {
        ['periodo' => $periodo, 'seccion' => $seccion, 'materia' => $materia] = $this->escenario();

        $otro = User::factory()->create(['role' => 'docente']);
        Docente::factory()->create(['user_id' => $otro->id]);
        Sanctum::actingAs($otro);

        $this->getJson('/api/calificaciones-competencias/libro?seccion_id='.$seccion->id
            .'&materia_id='.$materia->id.'&periodo_academico_id='.$periodo->id)
            ->assertForbidden();
    }
}
