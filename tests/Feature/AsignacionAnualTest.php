<?php

namespace Tests\Feature;

use App\Models\AnioEscolar;
use App\Models\AsignacionDocenteMateria;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AsignacionAnualTest extends TestCase
{
    use RefreshDatabase;

    public function test_asigna_un_curso_en_todos_los_bimestres_del_anio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/asignaciones/anual', [
            'docente_id' => $contexto['docente']->id,
            'materia_id' => $contexto['materia']->id,
            'seccion_id' => $contexto['seccion']->id,
            'anio' => $contexto['anio'],
        ]);

        $respuesta->assertCreated();
        $respuesta->assertJsonPath('creados', 4);
        $this->assertSame(4, AsignacionDocenteMateria::count());
    }

    public function test_no_duplica_al_asignar_dos_veces(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $datos = [
            'docente_id' => $contexto['docente']->id,
            'materia_id' => $contexto['materia']->id,
            'seccion_id' => $contexto['seccion']->id,
            'anio' => $contexto['anio'],
        ];

        $this->postJson('/api/asignaciones/anual', $datos)->assertCreated();
        $this->postJson('/api/asignaciones/anual', $datos)
            ->assertOk()
            ->assertJsonPath('creados', 0);

        $this->assertSame(4, AsignacionDocenteMateria::count());
    }

    public function test_rechaza_un_anio_sin_bimestres(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/asignaciones/anual', [
            'docente_id' => $contexto['docente']->id,
            'materia_id' => $contexto['materia']->id,
            'seccion_id' => $contexto['seccion']->id,
            'anio' => $contexto['anio'] + 5,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('anio');
        $this->assertSame(0, AsignacionDocenteMateria::count());
    }

    public function test_lista_los_cursos_con_sus_bimestres_cubiertos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $this->postJson('/api/asignaciones/anual', [
            'docente_id' => $contexto['docente']->id,
            'materia_id' => $contexto['materia']->id,
            'seccion_id' => $contexto['seccion']->id,
            'anio' => $contexto['anio'],
            'es_tutor' => true,
        ])->assertCreated();

        $respuesta = $this->getJson("/api/asignaciones/anual?anio={$contexto['anio']}");

        $respuesta->assertOk();
        $respuesta->assertJsonPath('bimestres_del_anio', 4);
        $respuesta->assertJsonPath('cursos.0.bimestres', 4);
        $respuesta->assertJsonPath('cursos.0.materia', 'Matemática');
        $respuesta->assertJsonPath('cursos.0.es_tutor', true);
    }

    public function test_quita_el_curso_de_todo_el_anio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $datos = [
            'docente_id' => $contexto['docente']->id,
            'materia_id' => $contexto['materia']->id,
            'seccion_id' => $contexto['seccion']->id,
            'anio' => $contexto['anio'],
        ];

        $this->postJson('/api/asignaciones/anual', $datos)->assertCreated();

        $this->deleteJson('/api/asignaciones/anual', $datos)
            ->assertOk()
            ->assertJsonPath('eliminadas', 4);

        $this->assertSame(0, AsignacionDocenteMateria::count());
    }

    public function test_un_auxiliar_no_puede_asignar_cursos(): void
    {
        $auxiliar = User::factory()->create(['role' => 'auxiliar']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($auxiliar);

        $this->postJson('/api/asignaciones/anual', [
            'docente_id' => $contexto['docente']->id,
            'materia_id' => $contexto['materia']->id,
            'seccion_id' => $contexto['seccion']->id,
            'anio' => $contexto['anio'],
        ])->assertStatus(403);
    }

    public function test_incluye_aforo_y_ocupacion_real_de_la_seccion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        $anioEscolar = AnioEscolar::create([
            'anio' => $contexto['anio'],
            'estado' => 'abierto',
        ]);

        $estudiante = Estudiante::create([
            'nombres' => 'Alumno',
            'apellido_paterno' => 'Carga',
            'apellido_materno' => 'Academica',
            'dni' => '77111222',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $contexto['seccion']->id,
            'estado' => 'activo',
        ]);

        Matricula::create([
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $contexto['seccion']->id,
            'estado' => 'matriculado',
            'tipo' => 'regular',
            'origen' => 'sistema',
            'fecha_matricula' => now()->toDateString(),
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/asignaciones/anual', [
            'docente_id' => $contexto['docente']->id,
            'materia_id' => $contexto['materia']->id,
            'seccion_id' => $contexto['seccion']->id,
            'anio' => $contexto['anio'],
        ])->assertCreated();

        $respuesta = $this->getJson("/api/asignaciones/anual?anio={$contexto['anio']}");

        $respuesta->assertOk();
        $respuesta->assertJsonPath('cursos.0.capacidad', 40);
        $respuesta->assertJsonPath('cursos.0.ocupadas', 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function crearContexto(): array
    {
        $anio = (int) now()->year;

        $grado = Grado::create(['nombre' => '1 Primaria', 'nivel' => 'primaria']);

        $seccion = Seccion::create([
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 40,
            'turno' => 'Mañana',
        ]);

        $materia = Materia::create(['nombre' => 'Matemática']);

        $docente = Docente::create([
            'nombres' => 'Ana',
            'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Rojas',
            'dni' => '40111222',
            'email' => 'ana.quispe@colegio.pe',
            'especialidad' => 'Matemática',
        ]);

        foreach ([1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'] as $numero => $romano) {
            PeriodoAcademico::create([
                'nombre' => "{$romano} Bimestre {$anio}",
                'anio' => $anio,
                'estado' => $numero === 1 ? 'activo' : 'inactivo',
                'fecha_inicio' => "{$anio}-03-01",
                'fecha_fin' => "{$anio}-12-20",
            ]);
        }

        return compact('anio', 'grado', 'seccion', 'materia', 'docente');
    }
}
