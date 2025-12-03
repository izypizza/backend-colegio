<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use Illuminate\Http\Request;

class CalificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $calificaciones = Calificacion::with(['estudiante.seccion', 'materia', 'periodoAcademico'])->get();
        return response()->json($calificaciones);
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
            'nota' => 'required|numeric|min:0|max:100'
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
            'nota' => 'sometimes|required|numeric|min:0|max:100'
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
