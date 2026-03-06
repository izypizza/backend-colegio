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
     * Restricciones: una materia con calificaciones, asistencias, horarios
     * o asignaciones no puede eliminarse —es parte del registro académico oficial.
     */
    public function destroy(string $id)
    {
        try {
            $materia = Materia::withCount([
                'calificaciones',
                'asistencias',
                'horarios',
                'asignaciones',
            ])->findOrFail($id);

            if ($materia->calificaciones_count > 0) {
                return response()->json([
                    'message' => "No se puede eliminar la materia \"{$materia->nombre}\" porque tiene {$materia->calificaciones_count} calificación(es) registrada(s).",
                    'calificaciones' => $materia->calificaciones_count,
                ], 422);
            }

            if ($materia->asistencias_count > 0) {
                return response()->json([
                    'message' => "No se puede eliminar la materia \"{$materia->nombre}\" porque tiene {$materia->asistencias_count} registro(s) de asistencia.",
                    'asistencias' => $materia->asistencias_count,
                ], 422);
            }

            if ($materia->horarios_count > 0) {
                return response()->json([
                    'message' => "No se puede eliminar la materia \"{$materia->nombre}\" porque tiene {$materia->horarios_count} horario(s) asignado(s). Elimina primero los horarios.",
                    'horarios' => $materia->horarios_count,
                ], 422);
            }

            if ($materia->asignaciones_count > 0) {
                return response()->json([
                    'message' => "No se puede eliminar la materia \"{$materia->nombre}\" porque tiene {$materia->asignaciones_count} asignación(es) docente activa(s). Elimina primero las asignaciones.",
                    'asignaciones' => $materia->asignaciones_count,
                ], 422);
            }

            $materia->delete();
            return response()->json(['message' => "Materia \"{$materia->nombre}\" eliminada correctamente"], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Materia no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la materia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
