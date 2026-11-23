<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReporteSeccionesTest extends TestCase
{
    use RefreshDatabase;

    private function crearSeccion(string $grado, string $nivel, string $nombre, int $capacidad, int $estudiantes): Seccion
    {
        $grados = Grado::factory()->create(['nombre' => $grado, 'nivel' => $nivel]);

        $seccion = Seccion::factory()->create([
            'grado_id' => $grados->id,
            'nombre' => $nombre,
            'capacidad_maxima' => $capacidad,
        ]);

        Estudiante::factory()->count($estudiantes)->create(['seccion_id' => $seccion->id]);

        return $seccion;
    }

    public function test_el_consolidado_calcula_vacantes_y_ocupacion(): void
    {
        $this->crearSeccion('1°', 'primaria', 'A', 30, 27);
        $this->crearSeccion('1°', 'primaria', 'B', 30, 30);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $respuesta = $this->getJson('/api/reportes/secciones')->assertOk();

        $respuesta->assertJsonPath('totales.secciones', 2);
        $respuesta->assertJsonPath('totales.capacidad', 60);
        $respuesta->assertJsonPath('totales.matriculados', 57);
        $respuesta->assertJsonPath('totales.vacantes', 3);

        $primera = collect($respuesta->json('secciones'))->firstWhere('nombre', 'A');

        $this->assertSame(27, $primera['matriculados']);
        $this->assertSame(3, $primera['vacantes']);
        $this->assertSame(90.0, (float) $primera['ocupacion']);
    }

    public function test_el_consolidado_se_filtra_por_nivel(): void
    {
        $this->crearSeccion('1°', 'primaria', 'A', 30, 10);
        $this->crearSeccion('1°', 'secundaria', 'A', 30, 20);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $respuesta = $this->getJson('/api/reportes/secciones?nivel=secundaria')->assertOk();

        $respuesta->assertJsonPath('totales.secciones', 1);
        $respuesta->assertJsonPath('totales.matriculados', 20);

        $niveles = collect($respuesta->json('niveles'))->pluck('nivel')->all();
        $this->assertSame(['secundaria'], $niveles);
    }

    public function test_la_nomina_lista_solo_los_estudiantes_de_la_seccion(): void
    {
        $seccion = $this->crearSeccion('2°', 'primaria', 'A', 30, 3);
        $this->crearSeccion('2°', 'primaria', 'B', 30, 4);

        Sanctum::actingAs(User::factory()->create(['role' => 'auxiliar']));

        $respuesta = $this->getJson("/api/reportes/secciones/{$seccion->id}/nomina")->assertOk();

        $respuesta->assertJsonPath('total', 3);
        $respuesta->assertJsonPath('seccion.grado', '2°');

        $numeros = collect($respuesta->json('estudiantes'))->pluck('numero')->all();
        $this->assertSame([1, 2, 3], $numeros);
    }

    public function test_un_docente_no_accede_al_reporte_de_secciones(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'docente']));

        $this->getJson('/api/reportes/secciones')->assertForbidden();
    }

    public function test_el_consolidado_se_genera_en_pdf(): void
    {
        $this->crearSeccion('1°', 'primaria', 'A', 30, 5);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $respuesta = $this->get('/api/reportes/secciones/pdf');

        $respuesta->assertOk();
        $this->assertStringContainsString(
            'pdf',
            (string) $respuesta->headers->get('content-type')
        );
    }

    public function test_la_nomina_se_genera_en_pdf(): void
    {
        $seccion = $this->crearSeccion('3°', 'secundaria', 'A', 30, 4);

        Sanctum::actingAs(User::factory()->create(['role' => 'auxiliar']));

        $respuesta = $this->get("/api/reportes/secciones/{$seccion->id}/nomina/pdf");

        $respuesta->assertOk();
        $this->assertStringContainsString(
            'pdf',
            (string) $respuesta->headers->get('content-type')
        );
    }

    public function test_el_consolidado_se_exporta_a_excel(): void
    {
        $this->crearSeccion('4°', 'secundaria', 'B', 25, 20);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $respuesta = $this->get('/api/reportes/secciones/excel');

        $respuesta->assertOk();
    }
}
