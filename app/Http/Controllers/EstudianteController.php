<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $estudiantes = Estudiante::with(['seccion.grado', 'padres'])->get();
        return response()->json($estudiantes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'seccion_id' => 'required|exists:secciones,id'
        ]);

        $estudiante = Estudiante::create($validated);
        return response()->json($estudiante->load(['seccion']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $estudiante = Estudiante::with(['seccion.grado', 'padres', 'asistencias', 'calificaciones'])->findOrFail($id);
        return response()->json($estudiante);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $estudiante = Estudiante::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'seccion_id' => 'sometimes|required|exists:secciones,id'
        ]);

        $estudiante->update($validated);
        return response()->json($estudiante->load(['seccion']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $estudiante->delete();
        return response()->json(['message' => 'Estudiante eliminado correctamente'], 200);
    }
}
