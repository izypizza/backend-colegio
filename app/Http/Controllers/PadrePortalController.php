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
     * Horario semanal de un hijo, con el nombre del estudiante al que pertenece.
     */
    public function horarioHijo(Request $request, $hijo_id)
    {
        $user = $request->user();

        if (! $user->padre) {
            return response()->json(['message' => 'Usuario no es padre'], 403);
        }

        $hijo = $user->padre->estudiantes()
            ->with(['seccion:id,nombre,grado_id', 'seccion.grado:id,nombre,nivel,ciclo'])
            ->find($hijo_id);

        if (! $hijo) {
            return response()->json(['message' => 'Hijo no encontrado'], 404);
        }

        if (! $hijo->seccion_id) {
            return response()->json([
                'hijo' => [
                    'id' => $hijo->id,
                    'nombre_completo' => $hijo->nombre_completo,
                    'seccion' => null,
                ],
                'bloques' => [],
                'message' => 'El estudiante aún no tiene sección asignada.',
            ]);
        }

        $ordenDias = ['lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sabado' => 6, 'domingo' => 7];

        $docentesPorMateria = \DB::table('asignacion_docente_materia')
            ->join('docentes', 'asignacion_docente_materia.docente_id', '=', 'docentes.id')
            ->where('asignacion_docente_materia.seccion_id', $hijo->seccion_id)
            ->select('asignacion_docente_materia.materia_id', 'docentes.nombres', 'docentes.apellido_paterno', 'docentes.apellido_materno')
            ->get()
            ->keyBy('materia_id');

        $bloques = \App\Models\Horario::query()
            ->with('materia:id,nombre')
            ->where('seccion_id', $hijo->seccion_id)
            ->get()
            ->map(function ($bloque) use ($docentesPorMateria) {
                $docente = $docentesPorMateria->get($bloque->materia_id);

                return [
                    'id' => $bloque->id,
                    'dia' => $bloque->dia,
                    'hora_inicio' => $bloque->hora_inicio,
                    'hora_fin' => $bloque->hora_fin,
                    'materia' => $bloque->materia?->nombre,
                    'docente' => $docente
                        ? trim("{$docente->apellido_paterno} {$docente->apellido_materno}, {$docente->nombres}")
                        : null,
                ];
            })
            ->sortBy(fn ($bloque) => [
                $ordenDias[\App\Helpers\BusquedaHelper::normalizar($bloque['dia'])] ?? 9,
                $bloque['hora_inicio'],
            ])
            ->values();

        return response()->json([
            'hijo' => [
                'id' => $hijo->id,
                'nombre_completo' => $hijo->nombre_completo,
                'seccion' => trim(($hijo->seccion->grado->nombre ?? '') . ' - ' . $hijo->seccion->nombre),
                'grado' => $hijo->seccion->grado->nombre ?? null,
                'nivel' => $hijo->seccion->grado->nivel ?? null,
                'ciclo' => $hijo->seccion->grado->ciclo ?? null,
            ],
            'bloques' => $bloques,
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

