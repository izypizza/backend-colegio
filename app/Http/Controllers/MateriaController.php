<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $materias = Materia::with(['asignaciones'])->get();
        return response()->json($materias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        $materia = Materia::create($validated);
        return response()->json($materia, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $materia = Materia::with(['asignaciones', 'horarios', 'calificaciones'])->findOrFail($id);
        return response()->json($materia);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $materia = Materia::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255'
        ]);

        $materia->update($validated);
        return response()->json($materia);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $materia = Materia::findOrFail($id);
        $materia->delete();
        return response()->json(['message' => 'Materia eliminada correctamente'], 200);
    }
}
