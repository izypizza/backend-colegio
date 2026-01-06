<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
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

        // Paginación para mejorar performance (67k+ registros)
        // Si se solicita sin paginación (para exportar), usar parámetro 'all'
        if ($request->has('all') && $request->all === 'true') {
            $asistencias = $query->orderBy('fecha', 'desc')->get();
            return response()->json($asistencias);
        }

        // Por defecto, paginar resultados
        $perPage = $request->get('per_page', 100); // 100 registros por página
        $asistencias = $query->orderBy('fecha', 'desc')->paginate($perPage);
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
        
        $total = $asistencias->count();
        $presentes = $asistencias->where('presente', true)->count();
        $ausentes = $asistencias->where('presente', false)->count();
        $porcentaje_asistencia = $total > 0 ? round(($presentes / $total) * 100, 2) : 0;

        return response()->json([
            'asistencias' => $asistencias,
            'estadisticas' => [
                'total' => $total,
                'presentes' => $presentes,
                'ausentes' => $ausentes,
                'porcentaje_asistencia' => $porcentaje_asistencia
            ]
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

        $total = $asistencias->count();
        $presentes = $asistencias->where('presente', true)->count();
        $ausentes = $asistencias->where('presente', false)->count();

        return response()->json([
            'fecha' => $fecha,
            'asistencias' => $asistencias,
            'estadisticas' => [
                'total' => $total,
                'presentes' => $presentes,
                'ausentes' => $ausentes,
                'porcentaje_asistencia' => $total > 0 ? round(($presentes / $total) * 100, 2) : 0
            ]
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
                'fecha' => 'required|date|before_or_equal:today',
                'presente' => 'required|boolean',
                'observaciones' => 'nullable|string|max:500'
            ], [
                'estudiante_id.required' => 'Debe seleccionar un estudiante',
                'estudiante_id.exists' => 'El estudiante seleccionado no existe',
                'materia_id.required' => 'Debe seleccionar una materia',
                'materia_id.exists' => 'La materia seleccionada no existe',
                'fecha.required' => 'La fecha es obligatoria',
                'fecha.date' => 'El formato de fecha no es válido',
                'fecha.before_or_equal' => 'No se puede registrar asistencia para fechas futuras',
                'presente.required' => 'Debe indicar si el estudiante estuvo presente o ausente',
                'presente.boolean' => 'El valor de asistencia no es válido',
                'observaciones.max' => 'Las observaciones no deben exceder 500 caracteres'
            ]);

            // Validar que la fecha no sea muy antigua (máximo 60 días atrás)
            $fecha = \Carbon\Carbon::parse($validated['fecha']);
            $diasAtras = $fecha->diffInDays(now());
            
            if ($fecha->isPast() && $diasAtras > 60) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => ['fecha' => ['No se puede registrar asistencia para fechas con más de 60 días de antigüedad. La fecha ingresada tiene ' . $diasAtras . ' días']]
                ], 422);
            }

            // Validar que no exista ya un registro de asistencia para este estudiante en esta fecha y materia
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

            // Si es docente, verificar que tenga asignada esta materia
            $user = $request->user();
            if ($user->role === 'docente') {
                $docente = \App\Models\Docente::where('user_id', $user->id)->first();
                
                if (!$docente) {
                    return response()->json([
                        'message' => 'No se encontró el registro de docente'
                    ], 403);
                }

                $estudiante = \App\Models\Estudiante::find($validated['estudiante_id']);
                
                // Verificar que el docente tenga asignada esta materia en la sección del estudiante
                $tieneAsignacion = \App\Models\AsignacionDocenteMateria::where('docente_id', $docente->id)
                    ->where('materia_id', $validated['materia_id'])
                    ->where('seccion_id', $estudiante->seccion_id)
                    ->exists();

                if (!$tieneAsignacion) {
                    return response()->json([
                        'message' => 'No tienes autorización para registrar asistencia en esta materia o sección'
                    ], 403);
                }
            }

            $asistencia = Asistencia::create($validated);
            return response()->json([
                'message' => 'Asistencia registrada correctamente',
                'asistencia' => $asistencia->load(['estudiante', 'materia'])
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
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
     */
    public function update(Request $request, string $id)
    {
        try {
            $asistencia = Asistencia::findOrFail($id);
            
            $validated = $request->validate([
                'estudiante_id' => 'sometimes|required|exists:estudiantes,id',
                'materia_id' => 'sometimes|required|exists:materias,id',
                'fecha' => 'sometimes|required|date|before_or_equal:today',
                'presente' => 'sometimes|required|boolean',
                'observaciones' => 'nullable|string|max:500'
            ], [
                'estudiante_id.exists' => 'El estudiante seleccionado no existe',
                'materia_id.exists' => 'La materia seleccionada no existe',
                'fecha.date' => 'El formato de fecha no es válido',
                'fecha.before_or_equal' => 'No se puede registrar asistencia para fechas futuras',
                'presente.boolean' => 'El valor de asistencia no es válido',
                'observaciones.max' => 'Las observaciones no deben exceder 500 caracteres'
            ]);

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
                $materiaId = $validated['materia_id'] ?? $asistencia->materia_id;
                $estudianteId = $validated['estudiante_id'] ?? $asistencia->estudiante_id;

                $estudiante = \App\Models\Estudiante::find($estudianteId);
                
                // Verificar que el docente tenga asignada esta materia
                $tieneAsignacion = \App\Models\AsignacionDocenteMateria::where('docente_id', $docente->id)
                    ->where('materia_id', $materiaId)
                    ->where('seccion_id', $estudiante->seccion_id)
                    ->exists();

                if (!$tieneAsignacion) {
                    return response()->json([
                        'message' => 'No tienes autorización para modificar esta asistencia'
                    ], 403);
                }
            }

            $asistencia->update($validated);
            return response()->json([
                'message' => 'Asistencia actualizada correctamente',
                'asistencia' => $asistencia->load(['estudiante', 'materia'])
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
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
