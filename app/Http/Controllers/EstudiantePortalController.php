<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Models\Horario;
use Illuminate\Http\Request;

class EstudiantePortalController extends Controller
{
    /**
     * Ver mi horario de clases
     */
    public function miHorario(Request $request)
    {
        $user = $request->user();
        
        if (!$user->estudiante) {
            return response()->json([
                'error' => 'Usuario no es estudiante',
                'message' => 'No tienes un perfil de estudiante asociado'
            ], 403);
        }

        $estudiante = Estudiante::with('seccion.grado')->find($user->estudiante->id);
        
        if (!$estudiante || !$estudiante->seccion_id) {
            return response()->json([
                'message' => 'No tienes una sección asignada',
                'horarios' => [],
                'total' => 0
            ], 200);
        }

        $horarios = Horario::where('seccion_id', $estudiante->seccion_id)
            ->with(['materia', 'seccion.grado'])
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        return response()->json([
            'estudiante' => [
                'id' => $estudiante->id,
                'nombre_completo' => $estudiante->nombre_completo,
                'seccion' => $estudiante->seccion->nombre ?? 'Sin sección',
                'grado' => $estudiante->seccion->grado->nombre ?? 'Sin grado',
            ],
            'horarios' => $horarios,
            'total' => $horarios->count()
        ]);
    }

    /**
     * Ver mis calificaciones (optimizado con paginación)
     */
    public function misCalificaciones(Request $request)
    {
        $user = $request->user();
        
        if (!$user->estudiante) {
            return response()->json(['message' => 'Usuario no es estudiante'], 403);
        }

        // Paginación: 50 por página por defecto
        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);
        
        // Caché con paginación incluida
        $cacheKey = 'calificaciones_estudiante_' . $user->estudiante->id . '_' . ($request->periodo_academico_id ?? 'all') . '_page_' . $page . '_' . $perPage;
        
        $resultado = cache()->remember($cacheKey, 300, function () use ($user, $request, $perPage) {
            $query = Calificacion::where('estudiante_id', $user->estudiante->id)
                ->with([
                    'materia:id,nombre',
                    'periodoAcademico:id,nombre,anio,estado'
                ])
                ->select('id', 'estudiante_id', 'materia_id', 'periodo_academico_id', 'nota', 'tipo_calificacion', 'observaciones', 'created_at')
                ->orderBy('periodo_academico_id', 'desc')
                ->orderBy('materia_id');

            // Filtro por periodo
            if ($request->has('periodo_academico_id')) {
                $query->where('periodo_academico_id', $request->periodo_academico_id);
            }

            // Paginar resultados
            $calificaciones = $query->paginate($perPage);

            // Calcular promedio del total (no solo de la página)
            $promedioTotal = Calificacion::where('estudiante_id', $user->estudiante->id)
                ->when($request->has('periodo_academico_id'), function($q) use ($request) {
                    return $q->where('periodo_academico_id', $request->periodo_academico_id);
                })
                ->avg('nota');

            return [
                'calificaciones' => $calificaciones->items(),
                'promedio' => round($promedioTotal ?? 0, 2),
                'pagination' => [
                    'current_page' => $calificaciones->currentPage(),
                    'last_page' => $calificaciones->lastPage(),
                    'per_page' => $calificaciones->perPage(),
                    'total' => $calificaciones->total(),
                    'from' => $calificaciones->firstItem(),
                    'to' => $calificaciones->lastItem(),
                ],
            ];
        });

        return response()->json($resultado);
    }

    /**
     * Ver mis asistencias (optimizado con paginación)
     */
    public function misAsistencias(Request $request)
    {
        $user = $request->user();
        
        if (!$user->estudiante) {
            return response()->json(['message' => 'Usuario no es estudiante'], 403);
        }

        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);

        // Caché con paginación
        $cacheKey = 'asistencias_estudiante_' . $user->estudiante->id . '_' . ($request->fecha_inicio ?? 'recent') . '_page_' . $page;
        
        $resultado = cache()->remember($cacheKey, 120, function () use ($user, $request, $perPage) {
            $query = Asistencia::where('estudiante_id', $user->estudiante->id)
                ->with(['materia:id,nombre'])
                ->select('id', 'estudiante_id', 'materia_id', 'fecha', 'estado', 'observaciones');

            // Filtros opcionales
            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
            } else {
                // Por defecto, últimos 90 días
                $query->where('fecha', '>=', now()->subDays(90));
            }

            $asistencias = $query->orderBy('fecha', 'desc')->paginate($perPage);

            // Estadísticas del total (no solo de la página actual)
            $totalQuery = Asistencia::where('estudiante_id', $user->estudiante->id);
            
            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                $totalQuery->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
            } else {
                $totalQuery->where('fecha', '>=', now()->subDays(90));
            }

            $totalRegistros = $totalQuery->count();
            $presentes = $totalQuery->where('estado', 'presente')->count();
            $ausentes = $totalQuery->where('estado', 'ausente')->count();
            $porcentaje = $totalRegistros > 0 ? round(($presentes / $totalRegistros) * 100, 2) : 0;

            return [
                'asistencias' => $asistencias->items(),
                'estadisticas' => [
                    'total' => $totalRegistros,
                    'presentes' => $presentes,
                    'ausentes' => $ausentes,
                    'porcentaje_asistencia' => $porcentaje,
                ],
                'pagination' => [
                    'current_page' => $asistencias->currentPage(),
                    'last_page' => $asistencias->lastPage(),
                    'per_page' => $asistencias->perPage(),
                    'total' => $asistencias->total(),
                    'from' => $asistencias->firstItem(),
                    'to' => $asistencias->lastItem(),
                ],
            ];
        });

        return response()->json($resultado);
    }

    /**
     * Ver mi información personal
     */
    public function miPerfil(Request $request)
    {
        $user = $request->user();
        
        if (!$user->estudiante) {
            return response()->json(['message' => 'Usuario no es estudiante'], 403);
        }

        $estudiante = Estudiante::with(['seccion.grado', 'padres'])
            ->findOrFail($user->estudiante->id);

        return response()->json(['estudiante' => $estudiante]);
    }

    /**
     * Ver mi boletín de notas
     */
    public function miBoletin(Request $request, $periodo_id)
    {
        $user = $request->user();
        
        if (!$user->estudiante) {
            return response()->json(['message' => 'Usuario no es estudiante'], 403);
        }

        $calificaciones = Calificacion::where('estudiante_id', $user->estudiante->id)
            ->where('periodo_academico_id', $periodo_id)
            ->with(['materia', 'periodoAcademico'])
            ->get();

        $promedio = $calificaciones->avg('nota');

        return response()->json([
            'estudiante_id' => $user->estudiante->id,
            'periodo_academico_id' => $periodo_id,
            'calificaciones' => $calificaciones,
            'promedio' => round($promedio, 2),
            'aprobado' => $promedio >= 11
        ]);
    }
}
