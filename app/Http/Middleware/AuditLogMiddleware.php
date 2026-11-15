<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    private const TABLA_POR_ENTIDAD = [
        'estudiantes' => 'estudiantes',
        'docentes' => 'docentes',
        'padres' => 'padres',
        'grados' => 'grados',
        'secciones' => 'secciones',
        'materias' => 'materias',
        'periodos' => 'periodos_academicos',
        'anios-escolares' => 'anios_escolares',
        'matriculas' => 'matriculas',
        'asignaciones' => 'asignacion_docente_materia',
        'horarios' => 'horarios',
        'calificaciones' => 'calificaciones',
        'asistencias' => 'asistencias',
        'usuarios' => 'users',
        'users' => 'users',
        'libros' => 'libros',
        'prestamos' => 'prestamos_libros',
        'elecciones' => 'elecciones',
    ];

    private const CAMPOS_SENSIBLES = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'two_factor_secret',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $metodo = $request->method();
        $debeAuditar = in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        $segmentos = array_values(
            array_filter(explode('/', trim($request->path(), '/')))
        );
        $entidad = $segmentos[1] ?? ($segmentos[0] ?? 'sistema');
        $entidadId = $debeAuditar ? $this->idDelRecurso($request, $segmentos) : null;

        // Estado previo: debe leerse antes de que el controlador modifique el registro
        $estadoAnterior = null;
        if ($debeAuditar && $entidadId && in_array($metodo, ['PUT', 'PATCH', 'DELETE'], true)) {
            $estadoAnterior = $this->estadoPrevio($entidad, $entidadId);
        }

        $response = $next($request);

        if (!$debeAuditar || $response->getStatusCode() >= 400) {
            return $response;
        }

        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $accion = match ($metodo) {
            'POST' => 'crear',
            'PUT', 'PATCH' => 'actualizar',
            'DELETE' => 'eliminar',
            default => strtolower($metodo),
        };

        AuditLog::create([
            'user_id' => $user->id,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'descripcion' => $this->descripcion($accion, $entidad, $entidadId),
            'cambios_antes' => $estadoAnterior,
            'cambios_despues' => $request->except(self::CAMPOS_SENSIBLES),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function estadoPrevio(string $entidad, int $id): ?array
    {
        $tabla = self::TABLA_POR_ENTIDAD[$entidad] ?? null;

        if (!$tabla) {
            return null;
        }

        $registro = DB::table($tabla)->where('id', $id)->first();

        if (!$registro) {
            return null;
        }

        return array_diff_key((array) $registro, array_flip(self::CAMPOS_SENSIBLES));
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