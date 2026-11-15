<?php

namespace Tests\Feature;

use App\Models\AnioEscolar;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Matricula;
use App\Models\MatriculaApoderado;
use App\Models\Padre;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApoderadoPorMatriculaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_registro_asocia_el_apoderado_a_la_matricula(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        Sanctum::actingAs($admin);

        $this->postJson('/api/matriculas/registro-completo', [
            'dni' => '78111222',
            'nombres' => 'Ana',
            'apellido_paterno' => 'Torres',
            'apellido_materno' => 'Vega',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
            'apoderado' => [
                'dni' => '40333444',
                'nombres' => 'Raúl',
                'apellido_paterno' => 'Torres',
                'apellido_materno' => 'Loayza',
                'tipo' => 'padre',
                'es_contacto_principal' => true,
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('matricula_apoderado', [
            'padre_id' => Padre::where('dni', '40333444')->value('id'),
            'tipo' => 'padre',
            'es_contacto_principal' => true,
        ]);
    }

    public function test_un_apoderado_de_otro_anio_no_borra_el_anterior(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contexto = $this->crearContexto();

        $estudiante = Estudiante::create([
            'nombres' => 'Luis',
            'apellido_paterno' => 'Puma',
            'apellido_materno' => 'Chávez',
            'dni' => '78222333',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $contexto['seccion']->id,
            'estado' => 'activo',
        ]);

        $primera = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
            'estado' => 'matriculado',
            'tipo' => 'regular',
            'origen' => 'sistema',
            'fecha_matricula' => now()->toDateString(),
        ]);

        $apoderadoAnterior = Padre::create([
            'nombres' => 'Marta',
            'apellido_paterno' => 'Chávez',
            'apellido_materno' => 'Ríos',
            'dni' => '40444555',
        ]);

        MatriculaApoderado::create([
            'matricula_id' => $primera->id,
            'padre_id' => $apoderadoAnterior->id,
            'tipo' => 'madre',
        ]);

        $anioSiguiente = AnioEscolar::create([
            'anio' => $contexto['anioEscolar']->anio + 1,
            'estado' => 'abierto',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/matriculas/registro-completo', [
            'dni' => '78222333',
            'anio_escolar_id' => $anioSiguiente->id,
            'seccion_id' => $contexto['seccion']->id,
            'apoderado' => [
                'dni' => '40555666',
                'nombres' => 'Jorge',
                'apellido_paterno' => 'Puma',
                'apellido_materno' => 'Salas',
                'tipo' => 'padre',
            ],
        ])->assertCreated();

        $nuevaMatricula = Matricula::where('anio_escolar_id', $anioSiguiente->id)->firstOrFail();

        $this->assertDatabaseHas('matricula_apoderado', [
            'matricula_id' => $primera->id,
            'padre_id' => $apoderadoAnterior->id,
        ]);
        $this->assertDatabaseHas('matricula_apoderado', [
            'matricula_id' => $nuevaMatricula->id,
            'padre_id' => Padre::where('dni', '40555666')->value('id'),
        ]);
        $this->assertSame(2, MatriculaApoderado::count());
    }

    public function test_el_relleno_copia_los_apoderados_existentes_a_la_matricula(): void
    {
        $contexto = $this->crearContexto();

        $estudiante = Estudiante::create([
            'nombres' => 'Rosa',
            'apellido_paterno' => 'Ñahui',
            'apellido_materno' => 'Soto',
            'dni' => '78333444',
            'fecha_nacimiento' => now()->subYears(6)->format('Y-m-d'),
            'seccion_id' => $contexto['seccion']->id,
            'estado' => 'activo',
        ]);

        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $contexto['anioEscolar']->id,
            'seccion_id' => $contexto['seccion']->id,
            'estado' => 'matriculado',
            'tipo' => 'regular',
            'origen' => 'sistema',
            'fecha_matricula' => now()->toDateString(),
        ]);

        $padre = Padre::create([
            'nombres' => 'Pedro',
            'apellido_paterno' => 'Ñahui',
            'apellido_materno' => 'Ramos',
            'dni' => '40666777',
        ]);

        DB::table('estudiante_padre')->insert([
            'estudiante_id' => $estudiante->id,
            'padre_id' => $padre->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migracion = require database_path(
            'migrations/2026_11_15_120000_create_matricula_apoderado_table.php'
        );
        $migracion->trasladarApoderadosExistentes();

        $this->assertDatabaseHas('matricula_apoderado', [
            'matricula_id' => $matricula->id,
            'padre_id' => $padre->id,
        ]);
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
