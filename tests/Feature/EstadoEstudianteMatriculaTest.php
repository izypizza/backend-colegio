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

class EstadoEstudianteMatriculaTest extends TestCase
{
    use RefreshDatabase;

    public function test_egresar_un_estudiante_culmina_su_matricula(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto('74111222');

        Sanctum::actingAs($admin);

        $this->putJson("/api/estudiantes/{$contexto['estudiante']->id}/estado", [
            'estado' => 'egresado',
        ])->assertOk();

        $this->assertSame('culminado', $contexto['matricula']->fresh()->estado);
    }

    public function test_reactivar_un_estudiante_devuelve_la_matricula_a_vigente(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto('74222333');
        $contexto['matricula']->update(['estado' => 'culminado']);
        $contexto['estudiante']->update(['estado' => 'egresado']);

        Sanctum::actingAs($admin);

        $this->putJson("/api/estudiantes/{$contexto['estudiante']->id}/estado", [
            'estado' => 'activo',
        ])->assertOk();

        $this->assertSame('matriculado', $contexto['matricula']->fresh()->estado);
    }

    public function test_suspender_un_estudiante_conserva_su_matricula_vigente(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto('74333444');

        Sanctum::actingAs($admin);

        $this->putJson("/api/estudiantes/{$contexto['estudiante']->id}/estado", [
            'estado' => 'suspendido',
        ])->assertOk();

        $this->assertSame('matriculado', $contexto['matricula']->fresh()->estado);
    }

    /**
     * @return array{estudiante: Estudiante, matricula: Matricula}
     */
    private function crearContexto(string $dni): array
    {
        $anioEscolar = AnioEscolar::create([
            'anio' => (int) now()->year,
            'estado' => 'abierto',
        ]);

        $grado = Grado::firstOrCreate(
            ['nombre' => '1 Primaria', 'nivel' => 'primaria']
        );

        $seccion = Seccion::create([
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 40,
            'turno' => 'Mañana',
        ]);

        $estudiante = Estudiante::create([
            'nombres' => 'Alumno',
            'apellido_paterno' => 'Estado',
            'apellido_materno' => 'Matricula',
            'dni' => $dni,
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

        return ['estudiante' => $estudiante, 'matricula' => $matricula];
    }
}
