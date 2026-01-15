<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use Illuminate\Http\Request;

class CalificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Calificacion::with(['estudiante.seccion', 'materia', 'periodoAcademico']);

        // Filtrar por estudiante
        if ($request->has('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        // Filtrar por materia
        if ($request->has('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        // Filtrar por periodo académico
        if ($request->has('periodo_academico_id')) {
            $query->where('periodo_academico_id', $request->periodo_academico_id);
        }

        // Filtrar por sección
        if ($request->has('seccion_id')) {
            $query->whereHas('estudiante.seccion', function ($q) use ($request) {
                $q->where('id', $request->seccion_id);
            });
        }

        // Paginación para mejorar performance (7.8k+ registros)
        if ($request->has('all') && $request->all === 'true') {
            $calificaciones = $query->get();
            return response()->json($calificaciones);
        }

        $perPage = $request->get('per_page', 100);
        $calificaciones = $query->paginate($perPage);

        return response()->json($calificaciones);
    }

    /**
     * Obtener calificaciones de un estudiante (para padres)
     */
    public function misHijosCalificaciones(Request $request)
    {
        // Obtener los estudiantes del padre autenticado
        $padre = $request->user()->padre;

        if (! $padre) {
            return response()->json(['message' => 'Usuario no es un padre registrado'], 403);
        }

        $estudiantes = $padre->estudiantes()->with([
            'calificaciones.materia',
            'calificaciones.periodoAcademico',
            'seccion.grado',
        ])->get();

        return response()->json($estudiantes);
    }

    /**
     * Boletín de notas por estudiante y periodo
     */
    public function boletin($estudiante_id, $periodo_id)
    {
        $calificaciones = Calificacion::where('estudiante_id', $estudiante_id)
            ->where('periodo_academico_id', $periodo_id)
            ->with(['materia', 'periodoAcademico'])
            ->get();

        $promedio = $calificaciones->avg('nota');

        return response()->json([
            'estudiante_id' => $estudiante_id,
            'periodo_academico_id' => $periodo_id,
            'calificaciones' => $calificaciones,
            'promedio' => round($promedio, 2),
            'aprobado' => $promedio >= 11,
        ]);
    }

    /**
     * Reporte de calificaciones por materia
     */
    public function reportePorMateria($materia_id, Request $request)
    {
        $periodo_id = $request->input('periodo_academico_id');

        $query = Calificacion::where('materia_id', $materia_id)
            ->with(['estudiante.seccion']);

        if ($periodo_id) {
            $query->where('periodo_academico_id', $periodo_id);
        }

        $calificaciones = $query->get();

        return response()->json([
            'calificaciones' => $calificaciones,
            'estadisticas' => [
                'total_estudiantes' => $calificaciones->count(),
                'promedio_general' => round($calificaciones->avg('nota'), 2),
                'aprobados' => $calificaciones->where('nota', '>=', 11)->count(),
                'desaprobados' => $calificaciones->where('nota', '<', 11)->count(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'estudiante_id' => 'required|exists:estudiantes,id',
                'materia_id' => 'required|exists:materias,id',
                'periodo_academico_id' => 'required|exists:periodos_academicos,id',
                'nota' => 'required|numeric|min:0|max:20',
                'observaciones' => 'nullable|string|max:500',
            ], [
                'estudiante_id.required' => 'Debe seleccionar un estudiante',
                'estudiante_id.exists' => 'El estudiante seleccionado no existe',
                'materia_id.required' => 'Debe seleccionar una materia',
                'materia_id.exists' => 'La materia seleccionada no existe',
                'periodo_academico_id.required' => 'Debe seleccionar un período académico',
                'periodo_academico_id.exists' => 'El período académico seleccionado no existe',
                'nota.required' => 'La nota es obligatoria',
                'nota.numeric' => 'La nota debe ser un número',
                'nota.min' => 'La nota mínima es 0',
                'nota.max' => 'La nota máxima es 20. Ingresó: '.$request->nota,
                'observaciones.max' => 'Las observaciones no deben exceder 500 caracteres',
            ]);

            // Si es docente, verificar que tenga asignada esta materia
            $user = $request->user();
            if ($user->role === 'docente') {
                $docente = \App\Models\Docente::where('user_id', $user->id)->first();
                
                if (!$docente) {
                    return response()->json([
                        'message' => 'No se encontró el registro de docente'
                    ], 403);
                }

                // Verificar que el estudiante esté en una sección del docente
                $estudiante = \App\Models\Estudiante::find($validated['estudiante_id']);
                
                // Verificar que el docente tenga asignada esta materia en la sección del estudiante
                $tieneAsignacion = \App\Models\AsignacionDocenteMateria::where('docente_id', $docente->id)
                    ->where('materia_id', $validated['materia_id'])
                    ->where('seccion_id', $estudiante->seccion_id)
                    ->where('periodo_academico_id', $validated['periodo_academico_id'])
                    ->exists();

                if (!$tieneAsignacion) {
                    return response()->json([
                        'message' => 'No tienes autorización para calificar en esta materia o sección'
                    ], 403);
                }
            }

            // Validar que no exista ya una calificación para este estudiante en esta materia y período
            $existente = Calificacion::where('estudiante_id', $validated['estudiante_id'])
                ->where('materia_id', $validated['materia_id'])
                ->where('periodo_academico_id', $validated['periodo_academico_id'])
                ->first();

            if ($existente) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => ['nota' => ['Ya existe una calificación registrada para este estudiante en esta materia y período']],
                ], 422);
            }

            // Validación adicional del rango de notas
            $nota = floatval($validated['nota']);
            if ($nota < 0 || $nota > 20) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => ['nota' => ['La nota debe estar entre 0 y 20. Valor ingresado: '.$nota]],
                ], 422);
            }

            $calificacion = Calificacion::create($validated);

            return response()->json([
                'message' => 'Calificación registrada correctamente',
                'calificacion' => $calificacion->load(['estudiante', 'materia', 'periodoAcademico']),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar la calificación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $calificacion = Calificacion::with(['estudiante.seccion', 'materia', 'periodoAcademico'])->findOrFail($id);

        return response()->json($calificacion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $calificacion = Calificacion::findOrFail($id);

            $validated = $request->validate([
                'estudiante_id' => 'sometimes|required|exists:estudiantes,id',
                'materia_id' => 'sometimes|required|exists:materias,id',
                'periodo_academico_id' => 'sometimes|required|exists:periodos_academicos,id',
                'nota' => 'sometimes|required|numeric|min:0|max:20',
                'observaciones' => 'nullable|string|max:500',
            ], [
                'estudiante_id.exists' => 'El estudiante seleccionado no existe',
                'materia_id.exists' => 'La materia seleccionada no existe',
                'periodo_academico_id.exists' => 'El período académico seleccionado no existe',
                'nota.numeric' => 'La nota debe ser un número',
                'nota.min' => 'La nota mínima es 0',
                'nota.max' => 'La nota máxima es 20. Ingresó: '.$request->nota,
                'observaciones.max' => 'Las observaciones no deben exceder 500 caracteres',
            ]);

            // Si es docente, verificar límite de modificaciones (máximo 3 cambios)
            $user = $request->user();
            if ($user->role === 'docente') {
                // Verificar que tenga asignada esta materia
                $docente = \App\Models\Docente::where('user_id', $user->id)->first();
                
                if (!$docente) {
                    return response()->json([
                        'message' => 'No se encontró el registro de docente'
                    ], 403);
                }

                // Si se está modificando la nota, verificar límite
                if (isset($validated['nota']) && $validated['nota'] != $calificacion->nota) {
                    if ($calificacion->modificaciones_count >= 3) {
                        return response()->json([
                            'message' => 'Ha alcanzado el límite máximo de 3 modificaciones para esta calificación',
                            'errors' => ['nota' => ['Esta calificación ya fue modificada 3 veces. Contacte al administrador si necesita cambiarla nuevamente.']]
                        ], 422);
                    }
                    
                    // Incrementar contador de modificaciones
                    $validated['modificaciones_count'] = $calificacion->modificaciones_count + 1;
                    $validated['ultima_modificacion'] = now();
                }

                // Usar la materia actual o la nueva si se está actualizando
                $materiaId = $validated['materia_id'] ?? $calificacion->materia_id;
                $estudianteId = $validated['estudiante_id'] ?? $calificacion->estudiante_id;
                $periodoId = $validated['periodo_academico_id'] ?? $calificacion->periodo_academico_id;

                $estudiante = \App\Models\Estudiante::find($estudianteId);
                
                // Verificar que el docente tenga asignada esta materia
                $tieneAsignacion = \App\Models\AsignacionDocenteMateria::where('docente_id', $docente->id)
                    ->where('materia_id', $materiaId)
                    ->where('seccion_id', $estudiante->seccion_id)
                    ->where('periodo_academico_id', $periodoId)
                    ->exists();

                if (!$tieneAsignacion) {
                    return response()->json([
                        'message' => 'No tienes autorización para modificar esta calificación'
                    ], 403);
                }
            }

            // Validación adicional del rango de notas
            if (isset($validated['nota'])) {
                $nota = floatval($validated['nota']);
                if ($nota < 0 || $nota > 20) {
                    return response()->json([
                        'message' => 'Error de validación',
                        'errors' => ['nota' => ['La nota debe estar entre 0 y 20. Valor ingresado: '.$nota]],
                    ], 422);
                }
            }

            $calificacion->update($validated);

            return response()->json([
                'message' => 'Calificación actualizada correctamente',
                'calificacion' => $calificacion->load(['estudiante', 'materia', 'periodoAcademico']),
                'modificaciones_restantes' => max(0, 3 - ($calificacion->modificaciones_count ?? 0))
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la calificación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $calificacion = Calificacion::findOrFail($id);
        $calificacion->delete();

        return response()->json(['message' => 'Calificación eliminada correctamente'], 200);
    }

    /**
     * Estadísticas avanzadas por grado y nivel (OPTIMIZADO con SQL agregado)
     */
    public function estadisticasAvanzadas(Request $request)
    {
        $periodo_id = $request->input('periodo_academico_id');
        
        // Verificar si hay calificaciones en el sistema
        $totalCalificaciones = Calificacion::count();
        if ($totalCalificaciones === 0) {
            return response()->json([
                'general' => [
                    'total' => 0,
                    'promedio' => 0,
                    'aprobados' => 0,
                    'desaprobados' => 0,
                    'porcentaje_aprobados' => 0,
                ],
                'por_nivel' => [],
                'por_grado' => [],
                'distribucion' => [
                    'excelente' => 0,
                    'bueno' => 0,
                    'regular' => 0,
                    'deficiente' => 0,
                ],
                'mensaje' => 'No hay calificaciones registradas en el sistema'
            ]);
        }
        
        // Estadísticas generales usando agregaciones SQL
        $generalQuery = \DB::table('calificaciones');
        if ($periodo_id) {
            $generalQuery->where('periodo_academico_id', $periodo_id);
        }
        
        $general = $generalQuery->selectRaw('
            COUNT(*) as total,
            ROUND(AVG(nota), 2) as promedio,
            SUM(CASE WHEN nota >= 11 THEN 1 ELSE 0 END) as aprobados,
            SUM(CASE WHEN nota < 11 THEN 1 ELSE 0 END) as desaprobados,
            SUM(CASE WHEN nota >= 16 THEN 1 ELSE 0 END) as excelentes,
            SUM(CASE WHEN nota >= 14 AND nota < 16 THEN 1 ELSE 0 END) as buenos,
            SUM(CASE WHEN nota >= 11 AND nota < 14 THEN 1 ELSE 0 END) as regulares,
            SUM(CASE WHEN nota < 11 THEN 1 ELSE 0 END) as deficientes
        ')->first();
        
        // Si el filtro por periodo no devuelve resultados
        if (!$general || $general->total == 0) {
            return response()->json([
                'general' => [
                    'total' => 0,
                    'promedio' => 0,
                    'aprobados' => 0,
                    'desaprobados' => 0,
                    'porcentaje_aprobados' => 0,
                ],
                'por_nivel' => [],
                'por_grado' => [],
                'distribucion' => [
                    'excelente' => 0,
                    'bueno' => 0,
                    'regular' => 0,
                    'deficiente' => 0,
                ],
                'mensaje' => 'No hay calificaciones para el período seleccionado'
            ]);
        }

        // Estadísticas por nivel (usando el campo nivel de grados)
        $porNivelQuery = \DB::table('calificaciones')
            ->join('estudiantes', 'calificaciones.estudiante_id', '=', 'estudiantes.id')
            ->join('secciones', 'estudiantes.seccion_id', '=', 'secciones.id')
            ->join('grados', 'secciones.grado_id', '=', 'grados.id');
            
        if ($periodo_id) {
            $porNivelQuery->where('calificaciones.periodo_academico_id', $periodo_id);
        }
        
        $porNivel = $porNivelQuery
            ->selectRaw('
                grados.nivel,
                COUNT(*) as total,
                ROUND(AVG(calificaciones.nota), 2) as promedio,
                SUM(CASE WHEN calificaciones.nota >= 11 THEN 1 ELSE 0 END) as aprobados,
                SUM(CASE WHEN calificaciones.nota < 11 THEN 1 ELSE 0 END) as desaprobados,
                SUM(CASE WHEN calificaciones.nota >= 16 THEN 1 ELSE 0 END) as excelentes,
                SUM(CASE WHEN calificaciones.nota >= 14 AND calificaciones.nota < 16 THEN 1 ELSE 0 END) as buenos,
                SUM(CASE WHEN calificaciones.nota >= 11 AND calificaciones.nota < 14 THEN 1 ELSE 0 END) as regulares
            ')
            ->groupBy('grados.nivel')
            ->get()
            ->keyBy('nivel');

        // Estadísticas por grado
        $porGradoQuery = \DB::table('calificaciones')
            ->join('estudiantes', 'calificaciones.estudiante_id', '=', 'estudiantes.id')
            ->join('secciones', 'estudiantes.seccion_id', '=', 'secciones.id')
            ->join('grados', 'secciones.grado_id', '=', 'grados.id');
            
        if ($periodo_id) {
            $porGradoQuery->where('calificaciones.periodo_academico_id', $periodo_id);
        }
        
        $porGrado = $porGradoQuery
            ->selectRaw('
                grados.nombre as grado,
                COUNT(*) as total,
                ROUND(AVG(calificaciones.nota), 2) as promedio,
                SUM(CASE WHEN calificaciones.nota >= 11 THEN 1 ELSE 0 END) as aprobados,
                SUM(CASE WHEN calificaciones.nota < 11 THEN 1 ELSE 0 END) as desaprobados,
                SUM(CASE WHEN calificaciones.nota >= 16 THEN 1 ELSE 0 END) as excelentes,
                SUM(CASE WHEN calificaciones.nota >= 14 AND calificaciones.nota < 16 THEN 1 ELSE 0 END) as buenos,
                SUM(CASE WHEN calificaciones.nota >= 11 AND calificaciones.nota < 14 THEN 1 ELSE 0 END) as regulares
            ')
            ->groupBy('grados.id', 'grados.nombre')
            ->orderBy('grados.nombre')
            ->get();

        // Distribución de notas
        $distribucion = [
            'excelente' => (int) $general->excelentes,
            'bueno' => (int) $general->buenos,
            'regular' => (int) $general->regulares,
            'deficiente' => (int) $general->deficientes,
        ];

        return response()->json([
            'general' => [
                'total' => (int) $general->total,
                'promedio' => (float) $general->promedio,
                'aprobados' => (int) $general->aprobados,
                'desaprobados' => (int) $general->desaprobados,
                'porcentaje_aprobados' => $general->total > 0 ? round(($general->aprobados / $general->total) * 100, 2) : 0,
            ],
            'por_nivel' => $porNivel,
            'por_grado' => $porGrado,
            'distribucion' => $distribucion,
        ]);
    }
}
