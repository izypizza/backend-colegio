<?php

namespace App\Providers;

use App\Models\Configuracion;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Límite de intentos de inicio de sesión (previene fuerza bruta).
        // El valor se administra desde Configuraciones; si no está disponible se usa 5.
        RateLimiter::for('login', function (Request $request) {
            $intentosPermitidos = 5;

            try {
                $valorConfigurado = (int) Configuracion::obtener('seguridad_intentos_login', 5);

                if ($valorConfigurado >= 1 && $valorConfigurado <= 20) {
                    $intentosPermitidos = $valorConfigurado;
                }
            } catch (\Throwable) {
                // Si la configuración no está disponible se mantiene el valor por defecto.
            }

            return Limit::perMinute($intentosPermitidos)
                ->by($request->input('email') . '|' . $request->ip());
        });

        // Límite del chequeo de disponibilidad
        RateLimiter::for('ping', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
