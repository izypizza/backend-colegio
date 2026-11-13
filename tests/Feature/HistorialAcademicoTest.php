<?php

namespace Tests\Feature;

use App\Models\AnioEscolar;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistorialAcademicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_calificacion_nueva_queda_atada_a_su_matricula(): void
    {
        $contexto = $this->crearContexto();

        $calificacion = Calificacion::create([
            'estudiante_id' => $contexto['estudiante']->id,
            'materia_id' => $contexto['materia']->id,
            'periodo_academico_id' => $contexto['periodo']->id,
            'nota' => 16,
        ]);

        $this->assertSame($contexto['matricula']->id, $calificacion->fresh()->matricula_id);
    }

    public function test_una_asistencia_nueva_queda_atada_a_su_matricula(): void
    {
        $contexto = $this->crearContexto();

        $asistencia = Asistencia::create([
            'estudiante_id' => $contexto['estudiante']->id,
            'materia_id' => $contexto['materia']->id,
            'fecha' => now()->toDateString(),
            'estado' => 'presente',
        ]);

        $this->assertSame($contexto['matricula']->id, $asistencia->fresh()->matricula_id);
    }

    public function test_sin_matricula_el_registro_se_guarda_sin_vinculo(): void
    {
        $contexto = $this->crearContexto();
        $contexto['matricula']->delete();

        $calificacion = Calificacion::create([
            'estudiante_id' => $contexto['estudiante']->id,
            'materia_id' => $contexto['materia']->id,
            'periodo_academico_id' => $contexto['periodo']->id,
            'nota' => 12,
        ]);

        $this->assertNotNull($calificacion->fresh());
        $this->assertNull($calificacion->fresh()->matricula_id);
    }

    public function test_el_historial_conserva_la_seccion_aunque_el_estudiante_cambie(): void
    {
        $contexto = $this->crearContexto();

        $calificacion = Calificacion::create([
            'estudiante_id' => $contexto['estudiante']->id,
            'materia_id' => $contexto['materia']->id,
            'periodo_academico_id' => $contexto['periodo']->id,
            'nota' => 18,
        ]);

        $seccionNueva = $this->crearSeccion('B');
        $contexto['estudiante']->update(['seccion_id' => $seccionNueva->id]);

        $this->assertSame(
            $contexto['seccion']->id,
            $calificacion->fresh()->matricula->seccion_id
        );
        $this->assertSame(
            $seccionNueva->id,
            $contexto['estudiante']->fresh()->seccion_id
        );
    }

    public function test_el_relleno_asigna_matricula_a_las_calificaciones_previas(): void
    {
        $contexto = $this->crearContexto();

        DB::table('calificaciones')->insert([
            'estudiante_id' => $contexto['estudiante']->id,
            'materia_id' => $contexto['materia']->id,
            'periodo_academico_id' => $contexto['periodo']->id,
            'nota' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(DB::table('calificaciones')->value('matricula_id'));

        $this->migracionDeMatricula()->rellenarCalificaciones();

        $this->assertSame(
            $contexto['matricula']->id,
            (int) DB::table('calificaciones')->value('matricula_id')
        );
    }

    public function test_el_relleno_asigna_matricula_a_las_asistencias_previas(): void
    {
        $contexto = $this->crearContexto();

        DB::table('asistencias')->insert([
            'estudiante_id' => $contexto['estudiante']->id,
            'materia_id' => $contexto['materia']->id,
            'fecha' => now()->toDateString(),
            'estado' => 'presente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(DB::table('asistencias')->value('matricula_id'));

        $this->migracionDeMatricula()->rellenarAsistencias();

        $this->assertSame(
            $contexto['matricula']->id,
            (int) DB::table('asistencias')->value('matricula_id')
        );
    }

    private function migracionDeMatricula(): object
    {
        return require database_path(
            'migrations/2026_11_13_110000_add_matricula_id_to_calificaciones_and_asistencias.php'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function crearContexto(): array
    {
        $anioEscolar = AnioEscolar::create([
            'anio' => (int) now()->year,
            'estado' => 'abierto',
        ]);

        $periodo = PeriodoAcademico::create([
            'nombre' => 'I Bimestre ' . now()->year,
            'anio' => (int) now()->year,
            'estado' => 'activo',
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
        ]);

        $materia = Materia::create(['nombre' => 'Matemática']);
        $seccion = $this->crearSeccion('A');

        $estudiante = Estudiante::create([
            'nombres' => 'Alumno',
            'apellido_paterno' => 'Historial',
            'apellido_materno' => 'Academico',
            'dni' => '75111222',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $seccion->id,
            'estado' => 'activo',
        ]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
            'estado' => 'matriculado',
            'tipo' => 'regular',
            'origen' => 'sistema',
            'fecha_matricula' => now()->toDateString(),
        ]);

        return compact('anioEscolar', 'periodo', 'materia', 'seccion', 'estudiante', 'matricula');
    }

    private function crearSeccion(string $nombre): Seccion
    {
        $grado = Grado::firstOrCreate(
            ['nombre' => '1 Primaria', 'nivel' => 'primaria']
        );

        return Seccion::create([
            'nombre' => $nombre,
            'grado_id' => $grado->id,
            'capacidad_maxima' => 40,
            'turno' => 'Mañana',
        ]);
    }
}
