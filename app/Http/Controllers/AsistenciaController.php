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

        $asistencias = $query->orderBy('fecha', 'desc')->get();
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
        $validated = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'materia_id' => 'required|exists:materias,id',
            'fecha' => 'required|date',
            'presente' => 'required|boolean'
        ]);

        $asistencia = Asistencia::create($validated);
        return response()->json($asistencia->load(['estudiante', 'materia']), 201);
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
        $asistencia = Asistencia::findOrFail($id);
        
        $validated = $request->validate([
            'estudiante_id' => 'sometimes|required|exists:estudiantes,id',
            'materia_id' => 'sometimes|required|exists:materias,id',
            'fecha' => 'sometimes|required|date',
            'presente' => 'sometimes|required|boolean'
        ]);

        $asistencia->update($validated);
        return response()->json($asistencia->load(['estudiante', 'materia']));
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
