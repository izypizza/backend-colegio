<?php

namespace App\Http\Controllers;

use App\Models\PrestamoLibro;
use App\Models\Libro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrestamoLibroController extends Controller
{
    /**
     * Listar todos los préstamos
     */
    public function index(Request $request)
    {
        $query = PrestamoLibro::with(['libro.categoria', 'usuario']);

        // Filtrar préstamos activos
        if ($request->has('activos') && $request->activos == 'true') {
            $query->whereNull('fecha_devolucion');
        }

        // Filtrar por usuario
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $prestamos = $query->orderBy('created_at', 'desc')->get();
        return response()->json($prestamos);
    }

    /**
     * Crear un nuevo préstamo
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libro_id' => 'required|exists:libros,id',
            'user_id' => 'required|exists:users,id',
            'fecha_prestamo' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar que el libro esté disponible
        $libro = Libro::findOrFail($request->libro_id);
        if (!$libro->disponible) {
            return response()->json(['error' => 'El libro no está disponible'], 400);
        }

        // Crear préstamo
        $prestamo = PrestamoLibro::create($request->all());

        // Marcar libro como no disponible
        $libro->update(['disponible' => false]);

        return response()->json($prestamo->load(['libro.categoria', 'usuario']), 201);
    }

    /**
     * Registrar devolución de un libro
     */
    public function devolver(Request $request, $id)
    {
        $prestamo = PrestamoLibro::findOrFail($id);

        if (!$prestamo->estaActivo()) {
            return response()->json(['error' => 'Este préstamo ya fue devuelto'], 400);
        }

        $validator = Validator::make($request->all(), [
            'fecha_devolucion' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Registrar devolución
        $prestamo->update(['fecha_devolucion' => $request->fecha_devolucion]);

        // Marcar libro como disponible
        $prestamo->libro->update(['disponible' => true]);

        return response()->json($prestamo->load(['libro.categoria', 'usuario']));
    }

    /**
     * Mis préstamos (usuario autenticado)
     */
    public function misPrestamos(Request $request)
    {
        $prestamos = PrestamoLibro::with(['libro.categoria'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($prestamos);
    }
}
