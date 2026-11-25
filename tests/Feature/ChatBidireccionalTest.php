<?php

namespace Tests\Feature;

use App\Models\AsignacionDocenteMateria;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\Padre;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * El chat docente-padre debe funcionar en ambos sentidos: cualquiera de los dos
 * puede iniciar la conversación y ambos pueden escribir y leer.
 */
class ChatBidireccionalTest extends TestCase
{
    use RefreshDatabase;

    private array $datos;

    protected function setUp(): void
    {
        parent::setUp();

        $periodo = PeriodoAcademico::create([
            'nombre' => 'I Bimestre',
            'anio' => 2026,
            'estado' => 'activo',
        ]);

        $grado = Grado::create(['nombre' => '1 Secundaria', 'nivel' => 'secundaria']);

        $seccion = Seccion::create([
            'nombre' => 'A',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 30,
            'turno' => 'Mañana',
        ]);

        $otraSeccion = Seccion::create([
            'nombre' => 'B',
            'grado_id' => $grado->id,
            'capacidad_maxima' => 30,
            'turno' => 'Mañana',
        ]);

        $materia = Materia::factory()->create(['nombre' => 'Matemática']);

        $usuarioDocente = User::factory()->create(['role' => 'docente']);
        $docente = Docente::factory()->create(['user_id' => $usuarioDocente->id]);

        AsignacionDocenteMateria::create([
            'docente_id' => $docente->id,
            'materia_id' => $materia->id,
            'seccion_id' => $seccion->id,
            'periodo_academico_id' => $periodo->id,
        ]);

        $estudiante = Estudiante::factory()->create(['seccion_id' => $seccion->id]);
        $estudianteAjeno = Estudiante::factory()->create(['seccion_id' => $otraSeccion->id]);

        $usuarioPadre = User::factory()->create(['role' => 'padre']);
        $padre = Padre::factory()->create(['user_id' => $usuarioPadre->id]);
        $estudiante->padres()->attach($padre->id);

        $usuarioPadreAjeno = User::factory()->create(['role' => 'padre']);
        $padreAjeno = Padre::factory()->create(['user_id' => $usuarioPadreAjeno->id]);
        $estudianteAjeno->padres()->attach($padreAjeno->id);

        $usuarioOtroDocente = User::factory()->create(['role' => 'docente']);
        Docente::factory()->create(['user_id' => $usuarioOtroDocente->id]);

        $this->datos = compact(
            'usuarioDocente',
            'docente',
            'estudiante',
            'usuarioPadre',
            'padre',
            'padreAjeno',
            'usuarioOtroDocente',
        );
    }

    public function test_el_padre_inicia_la_conversacion_y_el_docente_responde(): void
    {
        ['usuarioPadre' => $usuarioPadre, 'docente' => $docente, 'estudiante' => $estudiante] = $this->datos;

        Sanctum::actingAs($usuarioPadre);

        $conversacion = $this->postJson('/api/chat/conversaciones', [
            'docente_id' => $docente->id,
            'estudiante_id' => $estudiante->id,
        ])->assertCreated()->json();

        $this->postJson("/api/chat/conversaciones/{$conversacion['id']}/mensajes", [
            'mensaje' => 'Buenas tardes, una consulta sobre mi hijo.',
        ])->assertCreated();

        Sanctum::actingAs($this->datos['usuarioDocente']);

        $this->postJson("/api/chat/conversaciones/{$conversacion['id']}/mensajes", [
            'mensaje' => 'Buenas tardes, le escucho.',
        ])->assertCreated();

        $mensajes = $this->getJson("/api/chat/conversaciones/{$conversacion['id']}/mensajes")
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $mensajes);
        $this->assertSame(
            'Buenas tardes, le escucho.',
            $mensajes[1]['mensaje'],
        );
    }

    public function test_el_docente_tambien_puede_iniciar_la_conversacion(): void
    {
        ['usuarioDocente' => $usuarioDocente, 'padre' => $padre] = $this->datos;

        Sanctum::actingAs($usuarioDocente);

        $conversacion = $this->postJson('/api/chat/conversaciones', [
            'padre_id' => $padre->id,
        ])->assertCreated()->json();

        $this->assertSame($padre->id, $conversacion['padre_id']);

        $this->postJson("/api/chat/conversaciones/{$conversacion['id']}/mensajes", [
            'mensaje' => 'Le escribo por el avance de su hijo.',
        ])->assertCreated();

        Sanctum::actingAs($this->datos['usuarioPadre']);

        $this->postJson("/api/chat/conversaciones/{$conversacion['id']}/mensajes", [
            'mensaje' => 'Gracias por avisarme.',
        ])->assertCreated();
    }

    public function test_mis_padres_solo_lista_los_de_mis_secciones(): void
    {
        ['usuarioDocente' => $usuarioDocente, 'padre' => $padre, 'padreAjeno' => $padreAjeno] = $this->datos;

        Sanctum::actingAs($usuarioDocente);

        $padres = $this->getJson('/api/docente/mis-padres')
            ->assertOk()
            ->json('padres');

        $this->assertCount(1, $padres);
        $this->assertSame($padre->id, $padres[0]['id']);
        $this->assertNotContains($padreAjeno->id, array_column($padres, 'id'));
    }

    public function test_un_docente_ajeno_no_puede_leer_ni_escribir_la_conversacion(): void
    {
        ['usuarioPadre' => $usuarioPadre, 'docente' => $docente, 'usuarioOtroDocente' => $ajeno] = $this->datos;

        Sanctum::actingAs($usuarioPadre);

        $conversacion = $this->postJson('/api/chat/conversaciones', [
            'docente_id' => $docente->id,
        ])->assertCreated()->json();

        Sanctum::actingAs($ajeno);

        $this->getJson("/api/chat/conversaciones/{$conversacion['id']}/mensajes")
            ->assertForbidden();

        $this->postJson("/api/chat/conversaciones/{$conversacion['id']}/mensajes", [
            'mensaje' => 'No debería poder escribir.',
        ])->assertForbidden();
    }
}
