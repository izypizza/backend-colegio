<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use Illuminate\Http\Request;
use App\Http\Requests\CalificacionStoreRequest;
use App\Http\Requests\CalificacionUpdateRequest;
use App\Traits\VerificaAutorizacionDocente;
use App\Helpers\PeriodoAcademicoHelper;

class CalificacionController extends Controller
{
    use VerificaAutorizacionDocente;
    /**
     * Display a listing of the resource.
     * Consulta optimizada con filtros avanzados y paginación obligatoria
     */
    public function index(Request $request)
    {
        // Validar filtros
        $request->validate([
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'materia_id' => 'nullable|exists:materias,id',
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'seccion_id' => 'nullable|exists:secciones,id',
            'grado_id' => 'nullable|exists:grados,id',
            'nota_minima' => 'nullable|numeric|min:0|max:20',
            'nota_maxima' => 'nullable|numeric|min:0|max:20',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        // Seleccionar solo columnas necesarias
        $query = Calificacion::select('id', 'estudiante_id', 'materia_id', 'periodo_academico_id', 'nota', 'observaciones', 'created_at')
            ->with([
                'estudiante:id,nombres,apellido_paterno,apellido_materno,seccion_id',
                'estudiante.seccion:id,nombre,grado_id',
                'estudiante.seccion.grado:id,nombre,nivel',
                'materia:id,nombre',
                'periodoAcademico:id,nombre,anio,estado'
            ]);

        // Filtrar por estudiante
        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        // Filtrar por materia
        if ($request->filled('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        // Filtrar por periodo académico (por defecto el activo)
        if ($request->filled('periodo_academico_id')) {
            $query->where('periodo_academico_id', $request->periodo_academico_id);
        } elseif (!$request->has('todos')) {
            // Si no se especifica periodo y no se pide todos, usar el periodo activo
            try {
                $periodoActivo = PeriodoAcademicoHelper::obtenerIdPeriodoActivo();
                if ($periodoActivo) {
                    $query->where('periodo_academico_id', $periodoActivo);
                }
            } catch (\Exception $e) {
                // Si no hay período activo, continuar sin filtrar
            }
        }

        // Filtrar por sección
        if ($request->filled('seccion_id')) {
            $query->whereHas('estudiante', function ($q) use ($request) {
                $q->where('seccion_id', $request->seccion_id);
            });
        }

        // Filtrar por grado
        if ($request->filled('grado_id')) {
            $query->whereHas('estudiante.seccion', function ($q) use ($request) {
                $q->where('grado_id', $request->grado_id);
            });
        }

        // Filtrar por rango de notas
        if ($request->filled('nota_minima')) {
            $query->where('nota', '>=', $request->nota_minima);
        }
        if ($request->filled('nota_maxima')) {
            $query->where('nota', '<=', $request->nota_maxima);
        }

        // Orden por defecto (más recientes primero)
        $query->orderBy('created_at', 'desc');

        // Paginación obligatoria (por defecto 50 registros)
        $perPage = $request->get('per_page', 50);
        $calificaciones = $query->paginate($perPage);

        return response()->json($calificaciones);
    }

    /**
     * Obtener calificaciones de un estudiante (para padres)
     * Optimizado con filtro por periodo y límite
     */
    public function misHijosCalificaciones(Request $request)
    {
        // Obtener los estudiantes del padre autenticado
        $padre = $request->user()->padre;

        if (! $padre) {
            return response()->json(['message' => 'Usuario no es un padre registrado'], 403);
        }

        // Validar filtros opcionales
        $request->validate([
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
        ]);

        $query = $padre->estudiantes()
            ->select('id', 'nombres', 'apellido_paterno', 'apellido_materno', 'seccion_id')
            ->with([
                'seccion:id,nombre,grado_id',
                'seccion.grado:id,nombre,nivel',
            ]);

        // Filtrar por hijo específico si se proporciona
        if ($request->filled('estudiante_id')) {
            $query->where('id', $request->estudiante_id);
        }

        $estudiantes = $query->get();

        // Cargar calificaciones con filtros
        $estudiantes->each(function ($estudiante) use ($request) {
            $calificacionesQuery = $estudiante->calificaciones()
                ->select('id', 'estudiante_id', 'materia_id', 'periodo_academico_id', 'nota', 'observaciones', 'created_at')
                ->with([
                    'materia:id,nombre',
                    'periodoAcademico:id,nombre,anio,estado'
                ]);

            // Filtrar por periodo (por defecto el activo)
            if ($request->filled('periodo_academico_id')) {
                $calificacionesQuery->where('periodo_academico_id', $request->periodo_academico_id);
            } else {
                $periodoActivo = \App\Models\PeriodoAcademico::where('estado', 'activo')->first();
                if ($periodoActivo) {
                    $calificacionesQuery->where('periodo_academico_id', $periodoActivo->id);
                }
            }

            $estudiante->calificaciones = $calificacionesQuery->orderBy('created_at', 'desc')->get();
        });

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
     * Refactorizado: Usa FormRequest y Trait de autorización
     */
    public function store(CalificacionStoreRequest $request)
    {
        try {
            $validated = $request->validated();

            // Verificar autorización de docente usando Trait
            $estudiante = \App\Models\Estudiante::findOrFail($validated['estudiante_id']);
            $autorizacion = $this->verificarDocenteAsignado(
                $validated['materia_id'],
                $estudiante->seccion_id
            );
            
            if ($autorizacion !== true) {
                return $autorizacion; // Retorna JsonResponse con error
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

            $calificacion = Calificacion::create($validated);

            // Limpiar caché de calificaciones del estudiante
            cache()->forget('calificaciones_estudiante_' . $validated['estudiante_id'] . '_all');
            cache()->forget('calificaciones_estudiante_' . $validated['estudiante_id'] . '_' . $validated['periodo_academico_id']);

            return response()->json([
                'message' => 'Calificación registrada correctamente',
                'calificacion' => $calificacion->load(['estudiante', 'materia', 'periodoAcademico']),
            ], 201);

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
     * Refactorizado: Usa FormRequest y Trait de autorización
     */
    public function update(CalificacionUpdateRequest $request, string $id)
    {
        try {
            $calificacion = Calificacion::findOrFail($id);
            $validated = $request->validated();

            // Verificar autorización de docente usando Trait
            $materiaId = $validated['materia_id'] ?? $calificacion->materia_id;
            $estudianteId = $validated['estudiante_id'] ?? $calificacion->estudiante_id;
            $estudiante = \App\Models\Estudiante::findOrFail($estudianteId);
            
            $autorizacion = $this->verificarDocenteAsignado($materiaId, $estudiante->seccion_id);
            if ($autorizacion !== true) {
                return $autorizacion; // Retorna JsonResponse con error
            }

            // Si es docente, verificar límite de modificaciones (máximo 3 cambios)
            if ($this->esDocente() && isset($validated['nota']) && $validated['nota'] != $calificacion->nota) {
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

            $calificacion->update($validated);

            // Limpiar caché de calificaciones del estudiante
            cache()->forget('calificaciones_estudiante_' . $calificacion->estudiante_id . '_all');
            cache()->forget('calificaciones_estudiante_' . $calificacion->estudiante_id . '_' . $calificacion->periodo_academico_id);

            return response()->json([
                'message' => 'Calificación actualizada correctamente',
                'calificacion' => $calificacion->load(['estudiante', 'materia', 'periodoAcademico']),
                'modificaciones_restantes' => max(0, 3 - ($calificacion->modificaciones_count ?? 0))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la calificación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Restricciones: solo admin/auxiliar pueden eliminar calificaciones
     * y únicamente si el período académico todavía está activo.
     * Los registros de períodos cerrados son inmutables.
     */
    public function destroy(string $id)
    {
        try {
            $calificacion = Calificacion::with(['periodoAcademico', 'estudiante', 'materia'])->findOrFail($id);

            // Bloquear si el período ya está cerrado/inactivo
            if ($calificacion->periodoAcademico && $calificacion->periodoAcademico->estado !== 'activo') {
                return response()->json([
                    'message' => "No se puede eliminar la calificación porque pertenece al período \"{$calificacion->periodoAcademico->nombre}\" que ya está cerrado. Los registros de períodos finalizados son inmutables.",
                    'periodo' => $calificacion->periodoAcademico->nombre,
                    'estado_periodo' => $calificacion->periodoAcademico->estado,
                ], 422);
            }

            // Limpiar caché relacionada
            cache()->forget('calificaciones_estudiante_' . $calificacion->estudiante_id . '_all');
            cache()->forget('calificaciones_estudiante_' . $calificacion->estudiante_id . '_' . $calificacion->periodo_academico_id);

            $calificacion->delete();

            return response()->json(['message' => 'Calificación eliminada correctamente'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Calificación no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la calificación',
                'error' => $e->getMessage(),
            ], 500);
        }
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
