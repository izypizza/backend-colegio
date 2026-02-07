<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Illuminate\Http\Request;
use App\Http\Requests\AsistenciaStoreRequest;
use App\Http\Requests\AsistenciaUpdateRequest;
use App\Traits\VerificaAutorizacionDocente;
use App\Traits\ConPaginacionOpcional;
use App\Services\EstadisticasAsistenciaService;

class AsistenciaController extends Controller
{
    use VerificaAutorizacionDocente, ConPaginacionOpcional;

    protected $estadisticasService;

    public function __construct(EstadisticasAsistenciaService $estadisticasService)
    {
        $this->estadisticasService = $estadisticasService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Asistencia::with(['estudiante.seccion', 'materia']);

        // Filtrar por fecha
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Filtrar por estudiante
        if ($request->has('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        // Filtrar por materia
        if ($request->has('materia_id')) {
            $query->where('materia_id', $request->materia_id);
        }

        // Filtrar por sección
        if ($request->has('seccion_id')) {
            $query->whereHas('estudiante.seccion', function ($q) use ($request) {
                $q->where('id', $request->seccion_id);
            });
        }

        // Aplicar paginación con trait
        $asistencias = $this->paginateOrAll($query->orderBy('fecha', 'desc'), $request, 100);
        return response()->json($asistencias);
    }

    /**
     * Reporte de asistencias por estudiante
     */
    public function reportePorEstudiante($estudiante_id, Request $request)
    {
        $query = Asistencia::where('estudiante_id', $estudiante_id)
            ->with(['materia']);

        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        $asistencias = $query->get();
        $estadisticas = $this->estadisticasService->calcular($asistencias);

        return response()->json([
            'asistencias' => $asistencias,
            'estadisticas' => $estadisticas
        ]);
    }

    /**
     * Reporte de asistencias por sección
     */
    public function reportePorSeccion($seccion_id, Request $request)
    {
        $fecha = $request->input('fecha', now()->format('Y-m-d'));

        $asistencias = Asistencia::whereHas('estudiante.seccion', function ($q) use ($seccion_id) {
                $q->where('id', $seccion_id);
            })
            ->where('fecha', $fecha)
            ->with(['estudiante', 'materia'])
            ->get();

        $estadisticas = $this->estadisticasService->calcular($asistencias);

        return response()->json([
            'fecha' => $fecha,
            'asistencias' => $asistencias,
            'estadisticas' => $estadisticas
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Refactorizado: Usa FormRequest y Trait de autorización
     */
    public function store(AsistenciaStoreRequest $request)
    {
        try {
            $validated = $request->validated();

            // Validar que la fecha no sea muy antigua (máximo 60 días atrás)
            $fecha = \Carbon\Carbon::parse($validated['fecha']);
            $diasAtras = $fecha->diffInDays(now());
            
            if ($fecha->isPast() && $diasAtras > 60) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => ['fecha' => ['No se puede registrar asistencia para fechas con más de 60 días de antigüedad. La fecha ingresada tiene ' . $diasAtras . ' días']]
                ], 422);
            }

            // Validar que no exista ya un registro de asistencia
            $existente = Asistencia::where('estudiante_id', $validated['estudiante_id'])
                ->where('materia_id', $validated['materia_id'])
                ->where('fecha', $validated['fecha'])
                ->first();

            if ($existente) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => ['fecha' => ['Ya existe un registro de asistencia para este estudiante en esta fecha y materia']]
                ], 422);
            }

            // Verificar autorización de docente usando Trait
            $estudiante = \App\Models\Estudiante::findOrFail($validated['estudiante_id']);
            $autorizacion = $this->verificarDocenteAsignado(
                $validated['materia_id'],
                $estudiante->seccion_id
            );
            
            if ($autorizacion !== true) {
                return $autorizacion; // Retorna JsonResponse con error
            }

            $asistencia = Asistencia::create($validated);
            return response()->json([
                'message' => 'Asistencia registrada correctamente',
                'asistencia' => $asistencia->load(['estudiante', 'materia'])
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar la asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $asistencia = Asistencia::with(['estudiante.seccion', 'materia'])->findOrFail($id);
        return response()->json($asistencia);
    }

    /**
     * Update the specified resource in storage.
     * Refactorizado: Usa FormRequest y Trait de autorización
     */
    public function update(AsistenciaUpdateRequest $request, string $id)
    {
        try {
            $asistencia = Asistencia::findOrFail($id);
            $validated = $request->validated();

            // Validar que la fecha no sea muy antigua si se está actualizando
            if (isset($validated['fecha'])) {
                $fecha = \Carbon\Carbon::parse($validated['fecha']);
                $diasAtras = $fecha->diffInDays(now());
                
                if ($fecha->isPast() && $diasAtras > 60) {
                    return response()->json([
                        'message' => 'Error de validación',
                        'errors' => ['fecha' => ['No se puede actualizar a una fecha con más de 60 días de antigüedad. La fecha ingresada tiene ' . $diasAtras . ' días']]
                    ], 422);
                }
            }

            // Verificar autorización de docente usando Trait
            $materiaId = $validated['materia_id'] ?? $asistencia->materia_id;
            $estudianteId = $validated['estudiante_id'] ?? $asistencia->estudiante_id;
            $estudiante = \App\Models\Estudiante::findOrFail($estudianteId);
            
            $autorizacion = $this->verificarDocenteAsignado($materiaId, $estudiante->seccion_id);
            if ($autorizacion !== true) {
                return $autorizacion; // Retorna JsonResponse con error
            }

            $asistencia->update($validated);
            return response()->json([
                'message' => 'Asistencia actualizada correctamente',
                'asistencia' => $asistencia->load(['estudiante', 'materia'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $asistencia = Asistencia::findOrFail($id);
        $asistencia->delete();
        return response()->json(['message' => 'Asistencia eliminada correctamente'], 200);
    }
}
