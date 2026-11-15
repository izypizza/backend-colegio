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

        // Solo se audita lo que realmente se ejecutó
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $path = $request->path();
        $segmentos = array_values(array_filter(explode('/', trim($path, '/'))));
        $entidad = $segmentos[1] ?? ($segmentos[0] ?? 'sistema');

        $accion = match ($request->method()) {
            'POST' => 'crear',
            'PUT', 'PATCH' => 'actualizar',
            'DELETE' => 'eliminar',
            default => strtolower($request->method()),
        };

        $entidadId = $this->idDelRecurso($request, $segmentos);

        AuditLog::create([
            'user_id' => $user->id,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'descripcion' => $this->descripcion($accion, $entidad, $entidadId),
            'cambios_antes' => null,
            'cambios_despues' => $request->except([
                'password',
                'password_confirmation',
                'current_password',
            ]),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }

    /**
     * @param  array<int, string>  $segmentos
     */
    private function idDelRecurso(Request $request, array $segmentos): ?int
    {
        foreach ($request->route()?->parameters() ?? [] as $valor) {
            if (is_numeric($valor)) {
                return (int) $valor;
            }
        }

        foreach ($segmentos as $segmento) {
            if (ctype_digit($segmento)) {
                return (int) $segmento;
            }
        }

        return null;
    }

    private function descripcion(string $accion, string $entidad, ?int $entidadId): string
    {
        $recurso = strlen($entidad) > 1 ? rtrim($entidad, 's') : $entidad;

        return $entidadId
            ? ucfirst($accion) . " {$recurso} #{$entidadId}"
            : ucfirst($accion) . " en {$entidad}";
    }
}