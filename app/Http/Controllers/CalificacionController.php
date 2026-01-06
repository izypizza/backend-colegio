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

            // Si es docente, verificar que tenga asignada esta materia
            $user = $request->user();
            if ($user->role === 'docente') {
                $docente = \App\Models\Docente::where('user_id', $user->id)->first();
                
                if (!$docente) {
                    return response()->json([
                        'message' => 'No se encontró el registro de docente'
                    ], 403);
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
     * Estadísticas avanzadas por grado y nivel
     */
    public function estadisticasAvanzadas(Request $request)
    {
        // Obtener filtro de período si se proporciona
        $periodo_id = $request->input('periodo_academico_id');
        
        $query = Calificacion::with([
            'estudiante.seccion.grado',
            'materia',
            'periodoAcademico'
        ]);

        if ($periodo_id) {
            $query->where('periodo_academico_id', $periodo_id);
        }

        $calificaciones = $query->get();

        // Estadísticas generales
        $total = $calificaciones->count();
        $promedioGeneral = round($calificaciones->avg('nota'), 2);
        $aprobados = $calificaciones->where('nota', '>=', 11)->count();
        $desaprobados = $calificaciones->where('nota', '<', 11)->count();

        // Agrupar por nivel educativo
        $porNivel = [];
        $niveles = ['Inicial', 'Primaria', 'Secundaria'];
        
        foreach ($niveles as $nivel) {
            $calificacionesNivel = $calificaciones->filter(function($cal) use ($nivel) {
                $nombreGrado = $cal->estudiante->seccion->grado->nombre ?? '';
                return stripos($nombreGrado, $nivel) !== false;
            });

            if ($calificacionesNivel->count() > 0) {
                $porNivel[$nivel] = [
                    'total' => $calificacionesNivel->count(),
                    'promedio' => round($calificacionesNivel->avg('nota'), 2),
                    'aprobados' => $calificacionesNivel->where('nota', '>=', 11)->count(),
                    'desaprobados' => $calificacionesNivel->where('nota', '<', 11)->count(),
                    'excelentes' => $calificacionesNivel->where('nota', '>=', 16)->count(),
                    'buenos' => $calificacionesNivel->where('nota', '>=', 14)->where('nota', '<', 16)->count(),
                    'regulares' => $calificacionesNivel->where('nota', '>=', 11)->where('nota', '<', 14)->count(),
                ];
            }
        }

        // Agrupar por grado
        $porGrado = [];
        $grados = $calificaciones->groupBy(function($cal) {
            return $cal->estudiante->seccion->grado->nombre ?? 'Sin Grado';
        });

        foreach ($grados as $nombreGrado => $calificacionesGrado) {
            $porGrado[] = [
                'grado' => $nombreGrado,
                'total' => $calificacionesGrado->count(),
                'promedio' => round($calificacionesGrado->avg('nota'), 2),
                'aprobados' => $calificacionesGrado->where('nota', '>=', 11)->count(),
                'desaprobados' => $calificacionesGrado->where('nota', '<', 11)->count(),
                'excelentes' => $calificacionesGrado->where('nota', '>=', 16)->count(),
                'buenos' => $calificacionesGrado->where('nota', '>=', 14)->where('nota', '<', 16)->count(),
                'regulares' => $calificacionesGrado->where('nota', '>=', 11)->where('nota', '<', 14)->count(),
            ];
        }

        // Ordenar por nombre de grado
        usort($porGrado, function($a, $b) {
            return strcmp($a['grado'], $b['grado']);
        });

        // Distribución de notas
        $distribucion = [
            'excelente' => $calificaciones->where('nota', '>=', 16)->count(),
            'bueno' => $calificaciones->where('nota', '>=', 14)->where('nota', '<', 16)->count(),
            'regular' => $calificaciones->where('nota', '>=', 11)->where('nota', '<', 14)->count(),
            'deficiente' => $calificaciones->where('nota', '<', 11)->count(),
        ];

        return response()->json([
            'general' => [
                'total' => $total,
                'promedio' => $promedioGeneral,
                'aprobados' => $aprobados,
                'desaprobados' => $desaprobados,
                'porcentaje_aprobados' => $total > 0 ? round(($aprobados / $total) * 100, 2) : 0,
            ],
            'por_nivel' => $porNivel,
            'por_grado' => $porGrado,
            'distribucion' => $distribucion,
        ]);
    }
}
