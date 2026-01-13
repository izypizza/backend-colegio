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
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'nivel' => 'required|in:primaria,secundaria'
            ], [
                'nombre.required' => 'El nombre del grado es obligatorio',
                'nivel.required' => 'El nivel es obligatorio',
                'nivel.in' => 'El nivel debe ser primaria o secundaria'
            ]);

            $grado = Grado::create($validated);
            return response()->json($grado, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el grado',
                'error' => $e->getMessage()
            ], 500);
        }
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
        try {
            $grado = Grado::findOrFail($id);
            
            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'nivel' => 'sometimes|required|in:primaria,secundaria'
            ], [
                'nombre.required' => 'El nombre del grado es obligatorio',
                'nivel.in' => 'El nivel debe ser primaria o secundaria'
            ]);

            $grado->update($validated);
            return response()->json($grado);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Grado no encontrado'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el grado',
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
            $grado = Grado::findOrFail($id);
            
            // Verificar que no tenga secciones asociadas
            $seccionesCount = $grado->secciones()->count();
            if ($seccionesCount > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar el grado porque tiene secciones asociadas',
                    'secciones_count' => $seccionesCount
                ], 422);
            }
            
            $grado->delete();
            return response()->json(['message' => 'Grado eliminado correctamente'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Grado no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el grado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
