<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatriculaVacantesTest extends TestCase
{
    use RefreshDatabase;

    public function test_permite_matricular_hasta_la_capacidad_maxima(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seccion = $this->crearSeccion('A', 2);

        Sanctum::actingAs($admin);

        $this->postJson('/api/estudiantes', $this->datosEstudiante($seccion, '70111222'))
            ->assertCreated();

        $this->postJson('/api/estudiantes', $this->datosEstudiante($seccion, '70111223'))
            ->assertCreated();

        $this->assertSame(2, Estudiante::where('seccion_id', $seccion->id)->count());
    }

    public function test_bloquea_la_matricula_cuando_la_seccion_esta_llena(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seccion = $this->crearSeccion('B', 2);

        $this->crearEstudiante($seccion, '70222331');
        $this->crearEstudiante($seccion, '70222332');

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/estudiantes', $this->datosEstudiante($seccion, '70222333'));

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('seccion_id');
        $this->assertSame(2, Estudiante::where('seccion_id', $seccion->id)->count());
    }

    public function test_un_estudiante_egresado_libera_vacante(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seccion = $this->crearSeccion('C', 1);

        $this->crearEstudiante($seccion, '70333441', 'egresado');

        Sanctum::actingAs($admin);

        $this->postJson('/api/estudiantes', $this->datosEstudiante($seccion, '70333442'))
            ->assertCreated();

        $this->assertSame(2, Estudiante::where('seccion_id', $seccion->id)->count());
    }

    public function test_bloquea_mover_un_estudiante_a_una_seccion_llena(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seccionOrigen = $this->crearSeccion('D', 10);
        $seccionDestino = $this->crearSeccion('E', 1);

        $this->crearEstudiante($seccionDestino, '70444551');

        $estudiante = Estudiante::create([
            'nombres' => 'Rosa',
            'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Huamán',
            'dni' => '70444552',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $seccionOrigen->id,
        ]);

        Sanctum::actingAs($admin);

        $respuesta = $this->putJson("/api/estudiantes/{$estudiante->id}", [
            'seccion_id' => $seccionDestino->id,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('seccion_id');
        $this->assertSame($seccionOrigen->id, $estudiante->fresh()->seccion_id);
    }

    public function test_permite_actualizar_datos_sin_cambiar_de_seccion_aunque_este_llena(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seccion = $this->crearSeccion('F', 1);
        $estudiante = $this->crearEstudiante($seccion, '70555661');

        Sanctum::actingAs($admin);

        $respuesta = $this->putJson("/api/estudiantes/{$estudiante->id}", [
            'telefono' => '984112233',
            'direccion' => 'Av. Garcilaso 120',
        ]);

        $respuesta->assertOk();
        $this->assertSame('984112233', $estudiante->fresh()->telefono);
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

    private function crearEstudiante(Seccion $seccion, string $dni, string $estado = 'activo'): Estudiante
    {
        return Estudiante::create([
            'nombres' => 'Alumno',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => 'Vacante',
            'dni' => $dni,
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $seccion->id,
            'estado' => $estado,
        ]);
    }

    private function datosEstudiante(Seccion $seccion, string $dni): array
    {
        return [
            'nombres' => 'Nuevo',
            'apellido_paterno' => 'Ingresante',
            'apellido_materno' => 'Cusco',
            'dni' => $dni,
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $seccion->id,
        ];
    }
}
