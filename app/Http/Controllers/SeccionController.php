<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;

class SeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Seccion::with(['grado', 'estudiantes']);
        
        // Paginación para mejorar performance
        if ($request->has('all') && $request->all === 'true') {
            $secciones = $query->get();
            return response()->json($secciones);
        }

        $perPage = $request->get('per_page', 50);
        $secciones = $query->paginate($perPage);
        return response()->json($secciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'grado_id' => 'required|exists:grados,id',
                'capacidad' => 'nullable|integer|min:1|max:100',
                'turno' => 'nullable|in:Mañana,Tarde'
            ], [
                'nombre.required' => 'El nombre de la sección es obligatorio',
                'grado_id.required' => 'El grado es obligatorio',
                'grado_id.exists' => 'El grado seleccionado no existe',
                'capacidad.min' => 'La capacidad debe ser al menos 1',
                'capacidad.max' => 'La capacidad no puede ser mayor a 100'
            ]);

            $seccion = Seccion::create($validated);
            return response()->json($seccion->load(['grado']), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la sección',
                'error' => $e->getMessage()
            ], 500);
        }
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
        try {
            $seccion = Seccion::findOrFail($id);
            
            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'grado_id' => 'sometimes|required|exists:grados,id',
                'capacidad' => 'nullable|integer|min:1|max:100',
                'turno' => 'nullable|in:Mañana,Tarde'
            ], [
                'nombre.required' => 'El nombre de la sección es obligatorio',
                'grado_id.exists' => 'El grado seleccionado no existe',
                'capacidad.min' => 'La capacidad debe ser al menos 1'
            ]);

            $seccion->update($validated);
            return response()->json($seccion->load(['grado']));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sección no encontrada'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la sección',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $seccion = Seccion::findOrFail($id);
            
            // Verificar que no tenga estudiantes asociados
            $estudiantesCount = $seccion->estudiantes()->count();
            if ($estudiantesCount > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar la sección porque tiene estudiantes asociados',
                    'estudiantes_count' => $estudiantesCount
                ], 422);
            }
            
            $seccion->delete();
            return response()->json(['message' => 'Sección eliminada correctamente'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sección no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la sección',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
