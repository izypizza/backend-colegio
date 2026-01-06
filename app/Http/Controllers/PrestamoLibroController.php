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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar que el libro tenga stock disponible
        $libro = Libro::findOrFail($request->libro_id);
        if ($libro->cantidad_disponible <= 0) {
            return response()->json(['error' => 'El libro no tiene stock disponible'], 400);
        }

        // Verificar que el usuario no tenga un préstamo activo del mismo libro
        $prestamoActivo = PrestamoLibro::where('user_id', $request->user()->id)
            ->where('libro_id', $request->libro_id)
            ->where('devuelto', false)
            ->first();

        if ($prestamoActivo) {
            return response()->json(['error' => 'Ya tienes un préstamo activo de este libro'], 400);
        }

        // Crear préstamo con fecha de devolución de 15 días
        $fechaPrestamo = now();
        $fechaDevolucion = now()->addDays(15);

        $prestamo = PrestamoLibro::create([
            'libro_id' => $request->libro_id,
            'user_id' => $request->user()->id,
            'fecha_prestamo' => $fechaPrestamo,
            'fecha_devolucion' => $fechaDevolucion,
            'devuelto' => false,
        ]);

        // Reducir cantidad disponible del libro
        $libro->decrement('cantidad_disponible');

        return response()->json($prestamo->load(['libro.categoria']), 201);
    }

    /**
     * Registrar devolución de un libro
     */
    public function devolver(Request $request, $id)
    {
        $prestamo = PrestamoLibro::findOrFail($id);

        if ($prestamo->devuelto) {
            return response()->json(['error' => 'Este préstamo ya fue devuelto'], 400);
        }

        // Registrar devolución
        $prestamo->update(['devuelto' => true]);

        // Incrementar cantidad disponible del libro
        $prestamo->libro->increment('cantidad_disponible');

        return response()->json($prestamo->load(['libro.categoria']));
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
