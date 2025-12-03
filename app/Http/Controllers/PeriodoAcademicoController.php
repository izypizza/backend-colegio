<?php

namespace App\Http\Controllers;

use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;

class PeriodoAcademicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $periodos = PeriodoAcademico::with(['asignaciones', 'calificaciones'])->get();
        return response()->json($periodos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'anio' => 'required|integer|min:2000|max:2100'
        ]);

        $periodo = PeriodoAcademico::create($validated);
        return response()->json($periodo, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $periodo = PeriodoAcademico::with(['asignaciones', 'calificaciones'])->findOrFail($id);
        return response()->json($periodo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $periodo = PeriodoAcademico::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'anio' => 'sometimes|required|integer|min:2000|max:2100'
        ]);

        $periodo->update($validated);
        return response()->json($periodo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $periodo = PeriodoAcademico::findOrFail($id);
        $periodo->delete();
        return response()->json(['message' => 'Periodo académico eliminado correctamente'], 200);
    }
}
