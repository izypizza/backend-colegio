<?php

namespace Tests\Feature;

use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Padre;
use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeriodosDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_fechas_se_persisten_y_se_validan_incluso_al_actualizar_parcialmente(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $id = $this->postJson('/api/periodos', ['nombre' => 'Prueba', 'anio' => 2026,
            'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-06-30'])->assertCreated()
            ->assertJsonPath('fecha_inicio', '2026-03-01')->json('id');
        $this->getJson("/api/periodos/$id")->assertOk()->assertJsonPath('fecha_fin', '2026-06-30');
        $this->putJson("/api/periodos/$id", ['fecha_inicio' => '2026-07-01'])->assertStatus(422);
        $this->putJson("/api/periodos/$id", ['nombre' => 'Renombrado'])->assertOk()->assertJsonPath('fecha_inicio', '2026-03-01');
        $this->postJson('/api/periodos', ['nombre' => 'Sin fechas', 'anio' => 2026])->assertCreated();
        $this->postJson('/api/periodos', ['nombre' => 'Incompleto', 'anio' => 2026, 'fecha_inicio' => '2026-03-01'])->assertStatus(422);
    }

    public function test_dashboards_usan_periodo_activo_y_no_confunden_ausencia_de_notas_con_cero(): void
    {
        $periodo = PeriodoAcademico::create(['nombre' => 'Activo', 'anio' => 2026, 'estado' => 'activo']);
        PeriodoAcademico::create(['nombre' => 'Futuro', 'anio' => 2027, 'estado' => 'inactivo']);
        $usuario = User::factory()->create(['role' => 'estudiante']);
        $estudiante = Estudiante::factory()->create(['user_id' => $usuario->id]);
        Sanctum::actingAs($usuario);
        $this->getJson('/api/dashboard/stats')->assertOk()->assertJsonPath('calificaciones.promedio', null)
            ->assertJsonPath('recordatorios.0.titulo', 'Asistencia sin registros')->assertJsonCount(1, 'recordatorios');
        Calificacion::create(['estudiante_id' => $estudiante->id, 'materia_id' => Materia::factory()->create()->id,
            'periodo_academico_id' => $periodo->id, 'nota' => 15]);
        $this->getJson('/api/dashboard/stats')->assertOk()->assertJsonPath('calificaciones.promedio', 15);
        $padreUser = User::factory()->create(['role' => 'padre']);
        $padre = Padre::factory()->create(['user_id' => $padreUser->id]);
        $padre->estudiantes()->attach($estudiante->id);
        Sanctum::actingAs($padreUser);
        $this->getJson('/api/dashboard/stats')->assertOk()->assertJsonPath('resumen.periodo_actual', 'Activo')
            ->assertJsonPath('resumen.promedio_general', 15);
    }

    public function test_dashboard_no_cuenta_como_abierta_una_eleccion_vencida(): void
    {
        \App\Models\Eleccion::factory()->create(['estado' => 'activa',
            'fecha_inicio' => now()->subDays(2), 'fecha_cierre' => now()->subDay()]);
        \App\Models\Eleccion::factory()->create(['estado' => 'activa',
            'fecha_inicio' => now()->addHour(), 'fecha_cierre' => now()->addHours(2)]);
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson('/api/dashboard/stats')->assertOk()->assertJsonPath('elecciones.activas', 0)
            ->assertJsonPath('elecciones.proximas', 1);
    }

    public function test_bibliotecario_recibe_solo_estadisticas_de_biblioteca(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'bibliotecario']));
        $data = $this->getJson('/api/dashboard/stats')->assertOk()->json();
        $this->assertSame(['biblioteca'], array_keys($data));
        $this->assertArrayHasKey('prestamos_pendientes', $data['biblioteca']);
    }
}
