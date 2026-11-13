<?php

namespace Tests\Feature;

use App\Models\AnioEscolar;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatriculaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_las_matriculas_del_anio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('abierto');
        $seccion = $this->crearSeccion('A', 40);
        $estudiante = $this->crearEstudiante($seccion, '73111223');
        $this->crearMatricula($estudiante, $seccion, $anioEscolar);

        Sanctum::actingAs($admin);

        $respuesta = $this->getJson("/api/matriculas?anio_escolar_id={$anioEscolar->id}");

        $respuesta->assertOk();
        $respuesta->assertJsonPath('total', 1);
        $respuesta->assertJsonPath('data.0.estudiante.dni', '73111223');
        $respuesta->assertJsonPath('data.0.seccion.nombre', 'A');
    }

    public function test_matricula_un_estudiante_y_sincroniza_la_seccion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('abierto');
        $seccionOrigen = $this->crearSeccion('A', 40);
        $seccionDestino = $this->crearSeccion('B', 40);
        $estudiante = $this->crearEstudiante($seccionOrigen, '73222331');

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/matriculas', [
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccionDestino->id,
            'tipo' => 'regular',
        ]);

        $respuesta->assertCreated();
        $this->assertDatabaseHas('matriculas', [
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccionDestino->id,
            'estado' => 'matriculado',
        ]);
        $this->assertSame($seccionDestino->id, $estudiante->fresh()->seccion_id);
    }

    public function test_no_permite_matricular_en_un_anio_cerrado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('cerrado');
        $seccion = $this->crearSeccion('A', 40);
        $estudiante = $this->crearEstudiante($seccion, '73333441');

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/matriculas', [
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('anio_escolar_id');
        $this->assertSame(0, Matricula::count());
    }

    public function test_no_permite_dos_matriculas_del_mismo_estudiante_en_el_anio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('abierto');
        $seccion = $this->crearSeccion('A', 40);
        $estudiante = $this->crearEstudiante($seccion, '73444551');
        $this->crearMatricula($estudiante, $seccion, $anioEscolar);

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/matriculas', [
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('estudiante_id');
        $this->assertSame(1, Matricula::count());
    }

    public function test_no_permite_matricular_en_una_seccion_sin_vacantes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('abierto');
        $seccion = $this->crearSeccion('A', 1);
        $ocupante = $this->crearEstudiante($seccion, '73555661');
        $this->crearMatricula($ocupante, $seccion, $anioEscolar);

        $postulante = $this->crearEstudiante($seccion, '73555662');

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/matriculas', [
            'estudiante_id' => $postulante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('seccion_id');
        $this->assertSame(1, Matricula::count());
    }

    public function test_traslada_una_matricula_a_otra_seccion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('abierto');
        $origen = $this->crearSeccion('A', 40);
        $destino = $this->crearSeccion('B', 40);
        $estudiante = $this->crearEstudiante($origen, '73666771');
        $matricula = $this->crearMatricula($estudiante, $origen, $anioEscolar);

        Sanctum::actingAs($admin);

        $respuesta = $this->putJson("/api/matriculas/{$matricula->id}", [
            'seccion_id' => $destino->id,
            'observacion' => 'Cambio por reorganización de aulas',
        ]);

        $respuesta->assertOk();
        $this->assertSame($destino->id, $matricula->fresh()->seccion_id);
        $this->assertSame($destino->id, $estudiante->fresh()->seccion_id);
        $this->assertStringContainsString('Traslado interno', $matricula->fresh()->observacion);
    }

    public function test_no_permite_trasladar_a_una_seccion_llena(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('abierto');
        $origen = $this->crearSeccion('A', 40);
        $destino = $this->crearSeccion('B', 1);
        $ocupante = $this->crearEstudiante($destino, '73777881');
        $this->crearMatricula($ocupante, $destino, $anioEscolar);

        $estudiante = $this->crearEstudiante($origen, '73777882');
        $matricula = $this->crearMatricula($estudiante, $origen, $anioEscolar);

        Sanctum::actingAs($admin);

        $respuesta = $this->putJson("/api/matriculas/{$matricula->id}", [
            'seccion_id' => $destino->id,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('seccion_id');
        $this->assertSame($origen->id, $matricula->fresh()->seccion_id);
    }

    public function test_devuelve_las_vacantes_por_seccion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('abierto');
        $seccion = $this->crearSeccion('A', 30);
        $estudiante = $this->crearEstudiante($seccion, '73888991');
        $this->crearMatricula($estudiante, $seccion, $anioEscolar);

        Sanctum::actingAs($admin);

        $respuesta = $this->getJson("/api/matriculas/vacantes?anio_escolar_id={$anioEscolar->id}");

        $respuesta->assertOk();
        $respuesta->assertJsonPath('secciones.0.capacidad', 30);
        $respuesta->assertJsonPath('secciones.0.ocupadas', 1);
        $respuesta->assertJsonPath('secciones.0.disponibles', 29);
        $respuesta->assertJsonPath('totales.disponibles', 29);
    }

    public function test_un_docente_no_puede_gestionar_matriculas(): void
    {
        $docente = User::factory()->create(['role' => 'docente']);
        $anioEscolar = $this->crearAnioEscolar('abierto');

        Sanctum::actingAs($docente);

        $this->getJson("/api/matriculas?anio_escolar_id={$anioEscolar->id}")
            ->assertStatus(403);
    }

    public function test_lista_los_anios_escolares_disponibles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->crearAnioEscolar('abierto');

        Sanctum::actingAs($admin);

        $respuesta = $this->getJson('/api/anios-escolares');

        $respuesta->assertOk();
        $respuesta->assertJsonCount(1);
        $respuesta->assertJsonPath('0.estado', 'abierto');
    }

    public function test_no_permite_trasladar_en_un_anio_cerrado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('cerrado');
        $origen = $this->crearSeccion('A', 40);
        $destino = $this->crearSeccion('B', 40);
        $estudiante = $this->crearEstudiante($origen, '74000111');
        $matricula = $this->crearMatricula($estudiante, $origen, $anioEscolar);

        Sanctum::actingAs($admin);

        $respuesta = $this->putJson("/api/matriculas/{$matricula->id}", [
            'seccion_id' => $destino->id,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('anio_escolar_id');
        $this->assertSame($origen->id, $matricula->fresh()->seccion_id);
    }

    public function test_permite_matricular_en_un_anio_en_planificacion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anioEscolar = $this->crearAnioEscolar('planificacion');
        $seccion = $this->crearSeccion('A', 40);
        $estudiante = $this->crearEstudiante($seccion, '74000222');

        Sanctum::actingAs($admin);

        $this->postJson('/api/matriculas', [
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
        ])->assertCreated();

        $this->assertSame(1, Matricula::count());
    }

    public function test_crea_el_anio_escolar_si_no_existe_y_es_idempotente(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anio = (int) now()->year;

        Sanctum::actingAs($admin);

        $this->postJson('/api/anios-escolares', ['anio' => $anio])
            ->assertCreated();

        $this->postJson('/api/anios-escolares', ['anio' => $anio])
            ->assertOk();

        $this->assertSame(1, AnioEscolar::count());
        $this->assertDatabaseHas('anios_escolares', [
            'anio' => $anio,
            'estado' => 'abierto',
        ]);
    }

    private function crearAnioEscolar(string $estado): AnioEscolar
    {
        return AnioEscolar::create([
            'anio' => (int) now()->year,
            'estado' => $estado,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
        ]);
    }

    private function crearSeccion(string $nombre, int $capacidadMaxima): Seccion
    {
        $grado = Grado::firstOrCreate(
            ['nombre' => '1 Primaria', 'nivel' => 'primaria']
        );

        return Seccion::create([
            'nombre' => $nombre,
            'grado_id' => $grado->id,
            'capacidad_maxima' => $capacidadMaxima,
            'turno' => 'Mañana',
        ]);
    }

    private function crearEstudiante(Seccion $seccion, string $dni): Estudiante
    {
        return Estudiante::create([
            'nombres' => 'Alumno',
            'apellido_paterno' => 'Matricula',
            'apellido_materno' => 'Api',
            'dni' => $dni,
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $seccion->id,
            'estado' => 'activo',
        ]);
    }

    private function crearMatricula(Estudiante $estudiante, Seccion $seccion, AnioEscolar $anioEscolar): Matricula
    {
        return Matricula::create([
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
            'estado' => 'matriculado',
            'tipo' => 'regular',
            'origen' => 'sistema',
            'fecha_matricula' => now()->toDateString(),
        ]);
    }
}
