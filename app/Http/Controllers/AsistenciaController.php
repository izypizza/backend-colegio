<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $asistencias = Asistencia::with(['estudiante.seccion', 'materia'])->get();
        return response()->json($asistencias);
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
