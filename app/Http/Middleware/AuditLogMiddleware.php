<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $path = $request->path();
        $segments = explode('/', trim($path, '/'));
        $entidad = $segments[0] ?? 'sistema';
        $entidadId = $request->route('id') ?? null;

        AuditLog::create([
            'user_id' => $user->id,
            'accion' => $request->method(),
            'entidad' => $entidad,
            'entidad_id' => is_numeric($entidadId) ? (int) $entidadId : null,
            'descripcion' => 'Operacion realizada en ' . $path,
            'cambios_antes' => null,
            'cambios_despues' => $request->except(['password', 'password_confirmation']),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }
}