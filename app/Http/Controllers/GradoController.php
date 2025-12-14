<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use Illuminate\Http\Request;

class GradoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $grados = Grado::with(['secciones'])->get();
        return response()->json($grados);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        $grado = Grado::create($validated);
        return response()->json($grado, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $grado = Grado::with(['secciones'])->findOrFail($id);
        return response()->json($grado);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $grado = Grado::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255'
        ]);

        $grado->update($validated);
        return response()->json($grado);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $grado = Grado::findOrFail($id);
        $grado->delete();
        return response()->json(['message' => 'Grado eliminado correctamente'], 200);
    }
}
