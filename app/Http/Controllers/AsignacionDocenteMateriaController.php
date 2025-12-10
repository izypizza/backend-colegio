<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDocenteMateria;
use Illuminate\Http\Request;

class AsignacionDocenteMateriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $asignaciones = AsignacionDocenteMateria::with(['docente', 'materia', 'seccion.grado', 'periodoAcademico'])->get();
        return response()->json($asignaciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'docente_id' => 'required|exists:docentes,id',
            'materia_id' => 'required|exists:materias,id',
            'seccion_id' => 'required|exists:secciones,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id'
        ]);

        $asignacion = AsignacionDocenteMateria::create($validated);
        return response()->json($asignacion->load(['docente', 'materia', 'seccion', 'periodoAcademico']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $asignacion = AsignacionDocenteMateria::with(['docente', 'materia', 'seccion.grado', 'periodoAcademico'])->findOrFail($id);
        return response()->json($asignacion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $asignacion = AsignacionDocenteMateria::findOrFail($id);
        
        $validated = $request->validate([
            'docente_id' => 'sometimes|required|exists:docentes,id',
            'materia_id' => 'sometimes|required|exists:materias,id',
            'seccion_id' => 'sometimes|required|exists:secciones,id',
            'periodo_academico_id' => 'sometimes|required|exists:periodos_academicos,id'
        ]);

        $asignacion->update($validated);
        return response()->json($asignacion->load(['docente', 'materia', 'seccion', 'periodoAcademico']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $asignacion = AsignacionDocenteMateria::findOrFail($id);
        $asignacion->delete();
        return response()->json(['message' => 'Asignación eliminada correctamente'], 200);
    }
}
