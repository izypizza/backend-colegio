<?php

namespace Tests\Feature;

use App\Models\AnioEscolar;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\Padre;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistroCompletoMatriculaTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_persona_y_matricula_en_un_solo_paso(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/matriculas/registro-completo', [
            'dni' => '76111222',
            'nombres' => 'Lucía',
            'apellido_paterno' => 'Huamán',
            'apellido_materno' => 'Ccoa',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
        ]);

        $respuesta->assertCreated();
        $respuesta->assertJsonPath('persona_creada', true);
        $this->assertSame(1, Estudiante::count());
        $this->assertSame(1, Matricula::count());
        $this->assertDatabaseHas('estudiantes', ['dni' => '76111222']);
    }

    public function test_si_la_persona_ya_existe_solo_crea_la_matricula(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        $existente = Estudiante::create([
            'nombres' => 'Marco',
            'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Rojas',
            'dni' => '76222333',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $contexto['seccion']->id,
            'estado' => 'activo',
        ]);

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/matriculas/registro-completo', [
            'dni' => '76222333',
            'nombres' => 'Nombre que no debe sobrescribir',
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
        ]);

        $respuesta->assertCreated();
        $respuesta->assertJsonPath('persona_creada', false);
        $this->assertSame(1, Estudiante::count());
        $this->assertSame('Marco', $existente->fresh()->nombres);
        $this->assertSame(1, Matricula::count());
    }

    public function test_no_duplica_la_matricula_si_ya_esta_matriculado_ese_anio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $datos = [
            'dni' => '76333444',
            'nombres' => 'Ana',
            'apellido_paterno' => 'Flores',
            'apellido_materno' => 'Vega',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
        ];

        $this->postJson('/api/matriculas/registro-completo', $datos)->assertCreated();

        $respuesta = $this->postJson('/api/matriculas/registro-completo', $datos);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('estudiante_id');
        $this->assertSame(1, Matricula::count());
    }

    public function test_rechaza_una_edad_incoherente_con_el_grado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/matriculas/registro-completo', [
            'dni' => '76444555',
            'nombres' => 'Pedro',
            'apellido_paterno' => 'Salas',
            'apellido_materno' => 'Nina',
            'fecha_nacimiento' => now()->subYears(16)->format('Y-m-d'),
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
        ]);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonValidationErrors('fecha_nacimiento');
        $this->assertSame(0, Estudiante::count());
    }

    public function test_asocia_un_apoderado_nuevo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $respuesta = $this->postJson('/api/matriculas/registro-completo', [
            'dni' => '76555666',
            'nombres' => 'Rosa',
            'apellido_paterno' => 'Mamani',
            'apellido_materno' => 'Choque',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
            'apoderado' => [
                'dni' => '40111223',
                'nombres' => 'Julio',
                'apellido_paterno' => 'Mamani',
                'apellido_materno' => 'Huanca',
            ],
        ]);

        $respuesta->assertCreated();

        $padre = Padre::where('dni', '40111223')->first();
        $this->assertNotNull($padre);
        $this->assertDatabaseHas('estudiante_padre', ['padre_id' => $padre->id]);
    }

    public function test_reutiliza_un_apoderado_existente_sin_duplicarlo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        $padre = Padre::create([
            'nombres' => 'Julio',
            'apellido_paterno' => 'Mamani',
            'apellido_materno' => 'Huanca',
            'dni' => '40222334',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/matriculas/registro-completo', [
            'dni' => '76666777',
            'nombres' => 'Luis',
            'apellido_paterno' => 'Mamani',
            'apellido_materno' => 'Cruz',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
            'apoderado' => ['dni' => '40222334'],
        ])->assertCreated();

        $this->assertSame(1, Padre::count());
        $this->assertDatabaseHas('estudiante_padre', ['padre_id' => $padre->id]);
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

        $grado = Grado::create(['nombre' => '1 Primaria', 'nivel' => 'primaria']);

        $seccion = Seccion::create([
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 40,
            'turno' => 'Mañana',
        ]);

        return compact('anioEscolar', 'grado', 'seccion');
    }
}
