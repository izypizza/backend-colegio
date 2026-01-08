<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\Asistencia;
use Illuminate\Http\Request;

class PadrePortalController extends Controller
{
    /**
     * Ver mis hijos
     */
    public function misHijos(Request $request)
    {
        $user = $request->user();
        
        if (!$user->padre) {
            return response()->json(['message' => 'Usuario no es padre'], 403);
        }

        $hijos = $user->padre->estudiantes()
            ->with(['seccion.grado'])
            ->get();

        return response()->json(['hijos' => $hijos]);
    }

    /**
     * Ver calificaciones de mis hijos
     */
    public function calificacionesHijos(Request $request)
    {
        $user = $request->user();
        
        if (!$user->padre) {
            return response()->json(['message' => 'Usuario no es padre'], 403);
        }

        $hijos = $user->padre->estudiantes()
            ->with([
                'calificaciones.materia',
                'calificaciones.periodoAcademico',
                'seccion.grado'
            ])
            ->get();

        // Formatear respuesta para que sea consistente
        return response()->json([
            'hijos' => $hijos,
            'total_hijos' => $hijos->count()
        ]);
    }

    /**
     * Ver asistencias de un hijo específico
     */
    public function asistenciasHijo(Request $request, $hijo_id)
    {
        $user = $request->user();
        
        if (!$user->padre) {
            return response()->json(['message' => 'Usuario no es padre'], 403);
        }

        // Verificar que el estudiante es hijo del padre
        $hijo = $user->padre->estudiantes()->find($hijo_id);
        
        if (!$hijo) {
            return response()->json(['message' => 'No tiene permiso para ver este estudiante'], 403);
        }

        $query = Asistencia::where('estudiante_id', $hijo_id)
            ->with(['materia']);

        // Filtros opcionales
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        $asistencias = $query->orderBy('fecha', 'desc')->get();

        $total = $asistencias->count();
        $presentes = $asistencias->where('presente', true)->count();
        $ausentes = $asistencias->where('presente', false)->count();
        $porcentaje = $total > 0 ? round(($presentes / $total) * 100, 2) : 0;

        return response()->json([
            'hijo' => $hijo->load(['seccion.grado']),
            'asistencias' => $asistencias,
            'estadisticas' => [
                'total' => $total,
                'presentes' => $presentes,
                'ausentes' => $ausentes,
                'porcentaje_asistencia' => $porcentaje,
            ]
        ]);
    }

    /**
     * Ver boletín de notas de un hijo
     */
    public function boletinHijo(Request $request, $hijo_id, $periodo_id)
    {
        $user = $request->user();
        
        if (!$user->padre) {
            return response()->json(['message' => 'Usuario no es padre'], 403);
        }

        // Verificar que el estudiante es hijo del padre
        $hijo = $user->padre->estudiantes()->find($hijo_id);
        
        if (!$hijo) {
            return response()->json(['message' => 'No tiene permiso para ver este estudiante'], 403);
        }

        $calificaciones = Calificacion::where('estudiante_id', $hijo_id)
            ->where('periodo_academico_id', $periodo_id)
            ->with(['materia', 'periodoAcademico'])
            ->get();

        $promedio = $calificaciones->avg('nota');

        return response()->json([
            'hijo' => $hijo->load(['seccion.grado']),
            'periodo_academico_id' => $periodo_id,
            'calificaciones' => $calificaciones,
            'promedio' => round($promedio, 2),
            'aprobado' => $promedio >= 11
        ]);
    }
}
