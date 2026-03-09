<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PartidoController extends Controller
{
    /**
     * Listar partidos de una elección
     */
    public function index(Request $request)
    {
        $eleccion_id = $request->query('eleccion_id');
        
        $query = Partido::with(['candidatos.estudiante']);
        
        if ($eleccion_id) {
            $query->where('eleccion_id', $eleccion_id);
        }
        
        $partidos = $query->get();
        return response()->json($partidos);
    }

    /**
     * Crear un nuevo partido
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'eleccion_id' => 'required|exists:elecciones,id',
                'nombre' => 'required|string|max:255',
                'siglas' => 'required|string|max:20',
                'descripcion' => 'nullable|string',
                'logo' => 'nullable|image|max:2048', // 2MB max
                'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6})$/',
            ], [
                'eleccion_id.required' => 'La elección es requerida',
                'eleccion_id.exists' => 'La elección no existe',
                'nombre.required' => 'El nombre del partido es requerido',
                'siglas.required' => 'Las siglas son requeridas',
                'logo.image' => 'El logo debe ser una imagen',
                'logo.max' => 'El logo no puede superar 2MB',
                'color.regex' => 'El color debe ser un código hexadecimal válido',
            ]);

            // Manejar logo si existe
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('partidos', 'public');
                $validated['logo'] = $path;
            }

            // Color por defecto si no se proporciona
            if (!isset($validated['color'])) {
                $validated['color'] = $this->getRandomColor();
            }

            $partido = Partido::create($validated);
            
            return response()->json([
                'message' => 'Partido creado exitosamente',
                'partido' => $partido
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el partido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un partido
     */
    public function update(Request $request, $id)
    {
        try {
            $partido = Partido::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:255',
                'siglas' => 'sometimes|string|max:20',
                'descripcion' => 'nullable|string',
                'logo' => 'nullable|image|max:2048',
                'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6})$/',
            ]);

            // Manejar logo si existe
            if ($request->hasFile('logo')) {
                // Eliminar logo anterior si existe
                if ($partido->logo && Storage::disk('public')->exists($partido->logo)) {
                    Storage::disk('public')->delete($partido->logo);
                }
                $path = $request->file('logo')->store('partidos', 'public');
                $validated['logo'] = $path;
            }

            $partido->update($validated);

            return response()->json([
                'message' => 'Partido actualizado exitosamente',
                'partido' => $partido
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el partido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un partido
     */
    public function destroy($id)
    {
        try {
            $partido = Partido::findOrFail($id);

            // Eliminar logo si existe
            if ($partido->logo && Storage::disk('public')->exists($partido->logo)) {
                Storage::disk('public')->delete($partido->logo);
            }

            $partido->delete();

            return response()->json([
                'message' => 'Partido eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el partido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener colores aleatorios para partidos
     */
    private function getRandomColor()
    {
        $colors = [
            '#EF4444', // Rojo
            '#3B82F6', // Azul
            '#10B981', // Verde
            '#F59E0B', // Amarillo
            '#8B5CF6', // Púrpura
            '#EC4899', // Rosa
            '#14B8A6', // Turquesa
            '#F97316', // Naranja
        ];
        
        return $colors[array_rand($colors)];
    }
}
