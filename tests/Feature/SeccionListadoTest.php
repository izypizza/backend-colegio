<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeccionListadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_listado_de_secciones_entrega_el_conteo_de_estudiantes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $grado = Grado::create(['nombre' => '3 Primaria', 'nivel' => 'primaria']);

        $seccionConAlumnos = Seccion::create([
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 30,
        ]);

        $seccionVacia = Seccion::create([
            'nombre' => 'B',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 30,
        ]);

        Estudiante::factory()->count(2)->create([
            'seccion_id' => $seccionConAlumnos->id,
        ]);

        Sanctum::actingAs($admin);

        $respuesta = $this->getJson('/api/secciones?all=true')
            ->assertOk()
            ->json();

        $porId = collect($respuesta)->keyBy('id');

        $this->assertSame(2, $porId[$seccionConAlumnos->id]['estudiantes_count']);
        $this->assertSame(0, $porId[$seccionVacia->id]['estudiantes_count']);
    }

    public function test_el_listado_de_secciones_no_hidrata_a_los_estudiantes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $grado = Grado::create(['nombre' => '4 Primaria', 'nivel' => 'primaria']);

        $seccion = Seccion::create([
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 30,
        ]);

        Estudiante::factory()->create(['seccion_id' => $seccion->id]);

        Sanctum::actingAs($admin);

        $respuesta = $this->getJson('/api/secciones?all=true')
            ->assertOk()
            ->json();

        $primera = collect($respuesta)->firstWhere('id', $seccion->id);

        $this->assertArrayNotHasKey('estudiantes', $primera);
        $this->assertArrayHasKey('estudiantes_count', $primera);
    }
}
