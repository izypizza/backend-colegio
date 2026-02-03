<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registrar middleware de roles personalizados
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'modulo.activo' => \App\Http\Middleware\CheckModuloActivo::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
            'audit' => \App\Http\Middleware\AuditLogMiddleware::class,
        ]);
        
        // Aplicar middleware de mantenimiento globalmente a todas las rutas API
        $middleware->api(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\AuditLogMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
