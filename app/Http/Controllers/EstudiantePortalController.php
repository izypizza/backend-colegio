<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\Asistencia;
use Illuminate\Http\Request;

class EstudiantePortalController extends Controller
{
    /**
     * Ver mis calificaciones
     */
    public function misCalificaciones(Request $request)
    {
        $user = $request->user();
        
        if (!$user->estudiante) {
            return response()->json(['message' => 'Usuario no es estudiante'], 403);
        }

        $calificaciones = Calificacion::where('estudiante_id', $user->estudiante->id)
            ->with(['materia', 'periodoAcademico'])
            ->get();

        $promedio = $calificaciones->avg('nota');

        return response()->json([
            'calificaciones' => $calificaciones,
            'promedio' => round($promedio, 2),
        ]);
    }

    /**
     * Ver mis asistencias
     */
    public function misAsistencias(Request $request)
    {
        $user = $request->user();
        
        if (!$user->estudiante) {
            return response()->json(['message' => 'Usuario no es estudiante'], 403);
        }

        $query = Asistencia::where('estudiante_id', $user->estudiante->id)
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
