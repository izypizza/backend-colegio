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

        $calificaciones = $query->get();
        return response()->json($calificaciones);
    }

    /**
     * Obtener calificaciones de un estudiante (para padres)
     */
    public function misHijosCalificaciones(Request $request)
    {
        // Obtener los estudiantes del padre autenticado
        $padre = $request->user()->padre;
        
        if (!$padre) {
            return response()->json(['message' => 'Usuario no es un padre registrado'], 403);
        }

        $estudiantes = $padre->estudiantes()->with([
            'calificaciones.materia',
            'calificaciones.periodoAcademico',
            'seccion.grado'
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
            'aprobado' => $promedio >= 11
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
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'nota' => 'required|numeric|min:0|max:20'
        ]);

        $calificacion = Calificacion::create($validated);
        return response()->json($calificacion->load(['estudiante', 'materia', 'periodoAcademico']), 201);
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
        $calificacion = Calificacion::findOrFail($id);
        
        $validated = $request->validate([
            'estudiante_id' => 'sometimes|required|exists:estudiantes,id',
            'materia_id' => 'sometimes|required|exists:materias,id',
            'periodo_academico_id' => 'sometimes|required|exists:periodos_academicos,id',
            'nota' => 'sometimes|required|numeric|min:0|max:20'
        ]);

        $calificacion->update($validated);
        return response()->json($calificacion->load(['estudiante', 'materia', 'periodoAcademico']));
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
}
