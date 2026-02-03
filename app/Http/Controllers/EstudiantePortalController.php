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
     * Optimizado con filtros y paginación
     */
    public function misCalificaciones(Request $request)
    {
        $user = $request->user();
        
        if (!$user->estudiante) {
            return response()->json(['message' => 'Usuario no es estudiante'], 403);
        }

        // Validar filtros
        $request->validate([
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'materia_id' => 'nullable|exists:materias,id',
        ]);

        $query = Calificacion::where('estudiante_id', $user->estudiante->id)
            ->with([
                'materia:id,nombre',
                'periodoAcademico:id,nombre,fecha_inicio,fecha_fin,estado'
            ])
            ->select('id', 'estudiante_id', 'materia_id', 'periodo_academico_id', 'nota', 'observaciones', 'created_at');

        // Filtro por periodo (por defecto el activo)
        if ($request->filled('periodo_academico_id')) {
            $query->where('periodo_academico_id', $request->periodo_academico_id);
        } else {
            // Por defecto, solo el periodo activo
            $periodoActivo = \App\Models\PeriodoAcademico::where('estado', 'activo')->first();
            if ($periodoActivo) {
                $query->where('periodo_academico_id', $periodoActivo->id);
            }
        }

        // Filtro por materia
        if ($request->filled('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        $query->orderBy('periodo_academico_id', 'desc')->orderBy('materia_id');

        $calificaciones = $query->get();

        $promedio = $calificaciones->avg('nota');

        return response()->json([
            'calificaciones' => $calificaciones,
            'promedio' => round($promedio ?? 0, 2),
            'total' => $calificaciones->count(),
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
            ->with(['materia:id,nombre'])
            ->select('id', 'estudiante_id', 'materia_id', 'fecha', 'estado', 'observaciones');

        // Filtros opcionales
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        } else {
            // Por defecto, últimos 90 días para estudiantes
            $query->where('fecha', '>=', now()->subDays(90));
        }

        $asistencias = $query->orderBy('fecha', 'desc')->limit(500)->get();

        $total = $asistencias->count();
        $presentes = $asistencias->where('estado', 'presente')->count();
        $ausentes = $asistencias->where('estado', 'ausente')->count();
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
