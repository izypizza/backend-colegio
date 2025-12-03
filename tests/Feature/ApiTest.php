<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Seccion;

class ApiTest extends TestCase
{
    /**
     * Prueba para listar estudiantes.
     */
    public function test_puede_listar_estudiantes(): void
    {
        $response = $this->getJson('/api/estudiantes');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'nombre', 'seccion_id', 'seccion']
                 ]);
    }

    /**
     * Prueba para listar docentes.
     */
    public function test_puede_listar_docentes(): void
    {
        $response = $this->getJson('/api/docentes');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'nombre', 'especialidad']
                 ]);
    }

    /**
     * Prueba para ver un estudiante específico.
     */
    public function test_puede_ver_estudiante_detalle(): void
    {
        $estudiante = Estudiante::first();
        
        $response = $this->getJson("/api/estudiantes/{$estudiante->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $estudiante->id,
                     'nombre' => $estudiante->nombre
                 ]);
    }

    /**
     * Prueba para listar secciones y verificar grados.
     */
    public function test_puede_listar_secciones_con_grados(): void
    {
        $response = $this->getJson('/api/secciones');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'nombre', 'grado' => ['id', 'nombre']]
                 ]);
    }
    
    /**
     * Prueba para verificar calificaciones.
     */
    public function test_puede_listar_calificaciones(): void
    {
        $response = $this->getJson('/api/calificaciones');

        $response->assertStatus(200);
    }
}
