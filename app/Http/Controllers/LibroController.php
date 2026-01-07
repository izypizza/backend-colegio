<?php

namespace App\Http\Controllers;

use App\Models\Libro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LibroController extends Controller
{
    /**
     * Listar todos los libros
     */
    public function index(Request $request)
    {
        $query = Libro::with('categoria');

        // Filtrar por disponibilidad
        if ($request->has('disponible')) {
            $query->where('disponible', $request->disponible);
        }

        // Buscar por título o autor
        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('titulo', 'like', "%{$buscar}%")
                  ->orWhere('autor', 'like', "%{$buscar}%");
            });
        }

        $libros = $query->get();
        return response()->json($libros);
    }

    /**
     * Crear un nuevo libro
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'autor' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50',
            'editorial' => 'nullable|string|max:255',
            'anio_publicacion' => 'nullable|integer|min:1000|max:9999',
            'cantidad_total' => 'required|integer|min:1',
            'categoria_id' => 'required|exists:categorias_libros,id',
            'disponible' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $libro = Libro::create($request->all());
        return response()->json($libro->load('categoria'), 201);
    }

    /**
     * Mostrar un libro específico
     */
    public function show($id)
    {
        $libro = Libro::with(['categoria', 'prestamoActivo.usuario'])->findOrFail($id);
        return response()->json($libro);
    }

    /**
     * Actualizar un libro
     */
    public function update(Request $request, $id)
    {
        $libro = Libro::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'titulo' => 'string|max:255',
            'autor' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50',
            'editorial' => 'nullable|string|max:255',
            'anio_publicacion' => 'nullable|integer|min:1000|max:9999',
            'cantidad_total' => 'integer|min:1',
            'categoria_id' => 'exists:categorias_libros,id',
            'disponible' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $libro->update($request->all());
        return response()->json($libro->load('categoria'));
    }

    /**
     * Eliminar un libro
     */
    public function destroy($id)
    {
        $libro = Libro::findOrFail($id);
        $libro->delete();
        return response()->json(['message' => 'Libro eliminado correctamente']);
    }
}
