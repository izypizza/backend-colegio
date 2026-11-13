<?php

namespace Tests\Feature;

use App\Models\AnioEscolar;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Seccion;
use App\Services\MatriculaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculaGeneracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_una_matricula_por_cada_estudiante_sin_matricula(): void
    {
        $seccion = $this->crearSeccion();
        $primero = $this->crearEstudiante($seccion, '72111223');
        $segundo = $this->crearEstudiante($seccion, '72111224');

        $servicio = app(MatriculaService::class);
        $anioEscolar = $servicio->obtenerOCrearAnioEscolar($this->anioActual());
        $generadas = $servicio->generarMatriculasPendientes($anioEscolar);

        $this->assertSame(2, $generadas);
        $this->assertDatabaseHas('matriculas', [
            'estudiante_id' => $primero->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
            'estado' => 'matriculado',
            'origen' => 'migracion',
        ]);
        $this->assertDatabaseHas('matriculas', [
            'estudiante_id' => $segundo->id,
            'anio_escolar_id' => $anioEscolar->id,
        ]);
    }

    public function test_es_idempotente_y_no_duplica_matriculas(): void
    {
        $seccion = $this->crearSeccion();
        $this->crearEstudiante($seccion, '72222331');

        $servicio = app(MatriculaService::class);
        $anioEscolar = $servicio->obtenerOCrearAnioEscolar($this->anioActual());

        $this->assertSame(1, $servicio->generarMatriculasPendientes($anioEscolar));
        $this->assertSame(0, $servicio->generarMatriculasPendientes($anioEscolar));
        $this->assertSame(1, Matricula::count());
    }

    public function test_un_estudiante_egresado_genera_matricula_culminada(): void
    {
        $seccion = $this->crearSeccion();
        $this->crearEstudiante($seccion, '72333441', 'egresado');

        $servicio = app(MatriculaService::class);
        $anioEscolar = $servicio->obtenerOCrearAnioEscolar($this->anioActual());
        $servicio->generarMatriculasPendientes($anioEscolar);

        $matricula = Matricula::firstOrFail();

        $this->assertSame('culminado', $matricula->estado);
        $this->assertNotNull($matricula->observacion);
    }

    public function test_no_permite_dos_matriculas_del_mismo_estudiante_en_el_mismo_anio(): void
    {
        $seccion = $this->crearSeccion();
        $estudiante = $this->crearEstudiante($seccion, '72444551');

        $anioEscolar = AnioEscolar::create([
            'anio' => $this->anioActual(),
            'estado' => 'abierto',
        ]);

        $datos = [
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
            'estado' => 'matriculado',
            'tipo' => 'regular',
            'fecha_matricula' => now()->toDateString(),
        ];

        Matricula::create($datos);

        $this->expectException(QueryException::class);
        Matricula::create($datos);
    }

    public function test_el_anio_en_curso_queda_abierto_y_uno_futuro_en_planificacion(): void
    {
        $servicio = app(MatriculaService::class);

        $actual = $servicio->obtenerOCrearAnioEscolar($this->anioActual());
        $futuro = $servicio->obtenerOCrearAnioEscolar($this->anioActual() + 1);

        $this->assertSame('abierto', $actual->estado);
        $this->assertSame('planificacion', $futuro->estado);
    }

    public function test_obtener_o_crear_anio_es_idempotente(): void
    {
        $servicio = app(MatriculaService::class);

        $primero = $servicio->obtenerOCrearAnioEscolar($this->anioActual());
        $segundo = $servicio->obtenerOCrearAnioEscolar($this->anioActual());

        $this->assertSame($primero->id, $segundo->id);
        $this->assertSame(1, AnioEscolar::count());
    }

    public function test_el_comando_genera_las_matriculas_del_anio(): void
    {
        $seccion = $this->crearSeccion();
        $this->crearEstudiante($seccion, '72555661');

        $this->artisan('matriculas:generar', ['anio' => $this->anioActual()])
            ->assertExitCode(0);

        $this->assertSame(1, Matricula::count());
    }

    public function test_la_migracion_de_datos_genera_matriculas_para_estudiantes_existentes(): void
    {
        $seccion = $this->crearSeccion();
        $estudiante = $this->crearEstudiante($seccion, '72666771');

        $migracion = require database_path(
            'migrations/2026_11_13_100200_generar_matriculas_del_anio_en_curso.php'
        );
        $migracion->up();

        $this->assertDatabaseHas('matriculas', [
            'estudiante_id' => $estudiante->id,
            'seccion_id' => $seccion->id,
            'origen' => 'migracion',
        ]);
    }

    private function anioActual(): int
    {
        return (int) now()->year;
    }

    private function crearSeccion(): Seccion
    {
        $grado = Grado::firstOrCreate(
            ['nombre' => '1 Primaria', 'nivel' => 'primaria']
        );

        return Seccion::create([
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 40,
            'turno' => 'Mañana',
        ]);
    }

    private function crearEstudiante(Seccion $seccion, string $dni, string $estado = 'activo'): Estudiante
    {
        return Estudiante::create([
            'nombres' => 'Alumno',
            'apellido_paterno' => 'Matricula',
            'apellido_materno' => 'Prueba',
            'dni' => $dni,
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $seccion->id,
            'estado' => $estado,
        ]);
    }
}
