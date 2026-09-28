<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiErrorsTest extends TestCase
{
    public function test_oculta_excepciones_incluso_con_debug(): void
    {
        config(['app.debug' => true]);
        Route::get('api/prueba-error', fn () => throw new \RuntimeException('SQLSTATE host=privado password=secreto'));
        $response = $this->getJson('/api/prueba-error')->assertStatus(500);
        $response->assertExactJson(['message' => 'No se pudo completar la solicitud. Inténtalo nuevamente más tarde.']);
    }

    public function test_oculta_errores_devuelto_por_controlador_y_conserva_validacion_y_mantenimiento(): void
    {
        Route::get('api/prueba-respuesta', fn () => response()->json(['error' => 'SQL privado'], 500));
        $this->getJson('/api/prueba-respuesta')->assertStatus(500)->assertJsonMissing(['error' => 'SQL privado']);
        Route::get('api/prueba-validacion', fn () => response()->json(['errors' => ['nota' => ['Fuera de rango']]], 422));
        $this->getJson('/api/prueba-validacion')->assertStatus(422)->assertJsonPath('errors.nota.0', 'Fuera de rango');
        Route::get('api/prueba-mantenimiento', fn () => response()->json(['maintenance_mode' => true, 'message' => 'Mantenimiento'], 503));
        $this->getJson('/api/prueba-mantenimiento')->assertStatus(503)->assertJsonPath('maintenance_mode', true);
    }
}
