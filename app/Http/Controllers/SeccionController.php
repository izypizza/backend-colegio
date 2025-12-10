<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;

class SeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $secciones = Seccion::with(['grado', 'estudiantes'])->get();
        return response()->json($secciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'grado_id' => 'required|exists:grados,id'
        ]);

        $seccion = Seccion::create($validated);
        return response()->json($seccion->load(['grado']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $seccion = Seccion::with(['grado', 'estudiantes', 'horarios'])->findOrFail($id);
        return response()->json($seccion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $seccion = Seccion::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'grado_id' => 'sometimes|required|exists:grados,id'
        ]);

        $seccion->update($validated);
        return response()->json($seccion->load(['grado']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $seccion = Seccion::findOrFail($id);
        $seccion->delete();
        return response()->json(['message' => 'Sección eliminada correctamente'], 200);
    }
}
