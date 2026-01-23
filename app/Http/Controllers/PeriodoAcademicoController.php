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
        // Sin relaciones para evitar carga pesada (20k+ calificaciones)
        $periodos = PeriodoAcademico::orderBy('anio', 'desc')->orderBy('id', 'desc')->get();
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

    /**
     * Activar un periodo académico (desactiva todos los demás)
     */
    public function activar(Request $request, string $id)
    {
        $periodo = PeriodoAcademico::findOrFail($id);
        
        // Desactivar todos los periodos
        PeriodoAcademico::query()->update(['estado' => 'inactivo']);
        
        // Activar el periodo seleccionado
        $periodo->estado = 'activo';
        $periodo->save();
        
        return response()->json([
            'message' => "Periodo '{$periodo->nombre}' activado correctamente",
            'periodo' => $periodo
        ]);
    }

    /**
     * Generar periodos para un año específico
     */
    public function generarAnio(Request $request)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100'
        ]);
        
        $anio = $validated['anio'];
        
        // Verificar si ya existen periodos para este año
        $periodosExistentes = PeriodoAcademico::where('anio', $anio)->get();
        
        if ($periodosExistentes->count() > 0) {
            return response()->json([
                'message' => "Ya existen periodos para el año {$anio}",
                'periodos_existentes' => $periodosExistentes
            ], 400);
        }
        
        // Crear los 4 bimestres
        $bimestres = [
            ['nombre' => "I Bimestre {$anio}", 'anio' => $anio, 'estado' => 'inactivo'],
            ['nombre' => "II Bimestre {$anio}", 'anio' => $anio, 'estado' => 'inactivo'],
            ['nombre' => "III Bimestre {$anio}", 'anio' => $anio, 'estado' => 'inactivo'],
            ['nombre' => "IV Bimestre {$anio}", 'anio' => $anio, 'estado' => 'inactivo'],
        ];
        
        $periodosCreados = [];
        foreach ($bimestres as $bimestre) {
            $periodosCreados[] = PeriodoAcademico::create($bimestre);
        }
        
        return response()->json([
            'message' => "Periodos para {$anio} creados correctamente",
            'periodos' => $periodosCreados
        ], 201);
    }}