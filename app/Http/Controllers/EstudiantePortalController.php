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
            ->orderBy('periodo_academico_id')
            ->get();

        $promedio = $calificaciones->avg('nota');

        return response()->json([
            'calificaciones' => $calificaciones,
            'promedio' => round($promedio ?? 0, 2),
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
