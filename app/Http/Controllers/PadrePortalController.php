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
            ->with(['seccion.grado', 'user'])
            ->get()
            ->map(function ($hijo) {
                return [
                    'id' => $hijo->id,
                    'nombre' => $hijo->nombre,
                    'apellido' => $hijo->apellido,
                    'codigo' => $hijo->codigo,
                    'email' => $hijo->user ? $hijo->user->email : null,
                    'seccion_id' => $hijo->seccion_id,
                    'seccion' => $hijo->seccion ? [
                        'id' => $hijo->seccion->id,
                        'nombre' => $hijo->seccion->nombre,
                        'grado' => [
                            'id' => $hijo->seccion->grado->id,
                            'nombre' => $hijo->seccion->grado->nombre,
                            'nivel' => $hijo->seccion->grado->nivel,
                        ]
                    ] : null,
                ];
            });

        return response()->json([
            'hijos' => $hijos,
            'total_hijos' => $hijos->count()
        ]);
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
            ->get()
            ->map(function ($hijo) {
                // Asegurar que calificaciones siempre sea un array
                return [
                    'id' => $hijo->id,
                    'nombre' => $hijo->nombre,
                    'apellido' => $hijo->apellido,
                    'codigo' => $hijo->codigo,
                    'email' => $hijo->user ? $hijo->user->email : null,
                    'seccion_id' => $hijo->seccion_id,
                    'seccion' => $hijo->seccion ? [
                        'id' => $hijo->seccion->id,
                        'nombre' => $hijo->seccion->nombre,
                        'grado' => [
                            'id' => $hijo->seccion->grado->id,
                            'nombre' => $hijo->seccion->grado->nombre,
                            'nivel' => $hijo->seccion->grado->nivel,
                        ]
                    ] : null,
                    'calificaciones' => $hijo->calificaciones->map(function ($calif) {
                        return [
                            'id' => $calif->id,
                            'nota' => $calif->nota,
                            'materia' => [
                                'id' => $calif->materia->id,
                                'nombre' => $calif->materia->nombre,
                            ],
                            'periodo' => [
                                'id' => $calif->periodoAcademico->id,
                                'nombre' => $calif->periodoAcademico->nombre,
                            ]
                        ];
                    })
                ];
            });

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
            ->with(['materia:id,nombre'])
            ->select('id', 'estudiante_id', 'materia_id', 'fecha', 'estado', 'observaciones');

        // Filtros opcionales
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        } else {
            // Por defecto, últimos 90 días
            $query->where('fecha', '>=', now()->subDays(90));
        }

        $asistencias = $query->orderBy('fecha', 'desc')->limit(500)->get();

        $total = $asistencias->count();
        $presentes = $asistencias->where('estado', 'presente')->count();
        $ausentes = $asistencias->where('estado', 'ausente')->count();
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
            ->with(['materia:id,nombre', 'periodoAcademico:id,nombre'])
            ->select('id', 'estudiante_id', 'materia_id', 'periodo_academico_id', 'nota')
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

    /**
     * Obtener docentes de un hijo (para chat)
     */
    public function docentesHijo(Request $request, $hijo_id)
    {
        $user = $request->user();
        
        if (!$user->padre) {
            return response()->json(['message' => 'Usuario no es padre'], 403);
        }

        // Verificar que el hijo pertenezca al padre
        $hijo = $user->padre->estudiantes()->find($hijo_id);
        
        if (!$hijo) {
            return response()->json(['message' => 'Hijo no encontrado'], 404);
        }

        // Obtener docentes de la sección del hijo
        $docentes = \DB::table('asignacion_docente_materia')
            ->join('docentes', 'asignacion_docente_materia.docente_id', '=', 'docentes.id')
            ->where('asignacion_docente_materia.seccion_id', $hijo->seccion_id)
            ->select('docentes.*')
            ->distinct()
            ->get();

        return response()->json([
            'docentes' => $docentes,
            'total' => $docentes->count()
        ]);
    }
}

