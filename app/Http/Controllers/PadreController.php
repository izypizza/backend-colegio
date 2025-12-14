<?php

namespace App\Http\Controllers;

use App\Models\Padre;
use Illuminate\Http\Request;

class PadreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $padres = Padre::with(['estudiantes'])->get();
        return response()->json($padres);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255'
        ]);

        $padre = Padre::create($validated);
        return response()->json($padre, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $padre = Padre::with(['estudiantes'])->findOrFail($id);
        return response()->json($padre);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $padre = Padre::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255'
        ]);

        $padre->update($validated);
        return response()->json($padre);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $padre = Padre::findOrFail($id);
        $padre->delete();
        return response()->json(['message' => 'Padre eliminado correctamente'], 200);
    }
}
