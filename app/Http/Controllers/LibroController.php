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

        // Paginación
        if ($request->has('all') && $request->all === 'true') {
            $libros = $query->get();
            return response()->json($libros);
        }

        $perPage = $request->get('per_page', 50);
        $libros = $query->paginate($perPage);
        
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
        try {
            $libro = Libro::findOrFail($id);
            
            // Verificar que no tenga préstamos activos
            $prestamosActivos = $libro->prestamos()
                ->where('devuelto', false)
                ->where('estado', 'aprobado')
                ->count();
                
            if ($prestamosActivos > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar el libro porque tiene préstamos activos',
                    'prestamos_activos' => $prestamosActivos
                ], 422);
            }
            
            $libro->delete();
            return response()->json(['message' => 'Libro eliminado correctamente'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Libro no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el libro',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
