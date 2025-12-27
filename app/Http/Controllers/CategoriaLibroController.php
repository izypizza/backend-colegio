<?php

namespace App\Http\Controllers;

use App\Models\CategoriaLibro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaLibroController extends Controller
{
    /**
     * Listar todas las categorías
     */
    public function index()
    {
        $categorias = CategoriaLibro::withCount('libros')->get();
        return response()->json($categorias);
    }

    /**
     * Crear una nueva categoría
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:categorias_libros,nombre',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $categoria = CategoriaLibro::create($request->all());
        return response()->json($categoria, 201);
    }

    /**
     * Mostrar una categoría específica
     */
    public function show($id)
    {
        $categoria = CategoriaLibro::with('libros')->findOrFail($id);
        return response()->json($categoria);
    }

    /**
     * Actualizar una categoría
     */
    public function update(Request $request, $id)
    {
        $categoria = CategoriaLibro::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'string|max:255|unique:categorias_libros,nombre,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $categoria->update($request->all());
        return response()->json($categoria);
    }

    /**
     * Eliminar una categoría
     */
    public function destroy($id)
    {
        $categoria = CategoriaLibro::findOrFail($id);
        
        // Verificar si tiene libros asociados
        if ($categoria->libros()->count() > 0) {
            return response()->json([
                'error' => 'No se puede eliminar la categoría porque tiene libros asociados'
            ], 400);
        }

        $categoria->delete();
        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}
