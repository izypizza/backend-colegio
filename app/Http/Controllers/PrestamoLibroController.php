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

        // Paginación
        if ($request->has('all') && $request->all === 'true') {
            $prestamos = $query->orderBy('created_at', 'desc')->get();
            return response()->json($prestamos);
        }

        $perPage = $request->get('per_page', 50);
        $prestamos = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
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

        $userId = $request->user()->id;
        $libro = Libro::findOrFail($request->libro_id);

        // 1. Verificar stock disponible
        if ($libro->cantidad_disponible <= 0) {
            return response()->json([
                'error' => 'Libro no disponible',
                'mensaje' => 'Este libro no tiene copias disponibles en este momento',
                'stock_total' => $libro->cantidad,
                'copias_disponibles' => $libro->cantidad_disponible,
                'copias_prestadas' => $libro->cantidad - $libro->cantidad_disponible
            ], 400);
        }

        // 2. Verificar que el usuario no tenga préstamos vencidos
        $prestamosVencidos = PrestamoLibro::where('user_id', $userId)
            ->where('estado', 'aprobado')
            ->where('devuelto', false)
            ->where('fecha_devolucion', '<', now())
            ->count();

        if ($prestamosVencidos > 0) {
            return response()->json([
                'error' => 'Tienes préstamos vencidos',
                'mensaje' => 'Debes devolver los libros vencidos antes de solicitar uno nuevo',
                'prestamos_vencidos' => $prestamosVencidos
            ], 400);
        }

        // 3. Verificar límite de 3 préstamos activos APROBADOS
        $prestamosActivos = PrestamoLibro::where('user_id', $userId)
            ->where('estado', 'aprobado')
            ->where('devuelto', false)
            ->count();

        if ($prestamosActivos >= 3) {
            return response()->json([
                'error' => 'Límite de préstamos alcanzado',
                'mensaje' => 'Solo puedes tener máximo 3 libros prestados al mismo tiempo',
                'prestamos_activos' => $prestamosActivos,
                'limite_maximo' => 3
            ], 400);
        }

        // 4. Verificar que el usuario no tenga un préstamo activo/pendiente del mismo libro
        $prestamoActivo = PrestamoLibro::where('user_id', $userId)
            ->where('libro_id', $request->libro_id)
            ->whereIn('estado', ['pendiente', 'aprobado'])
            ->where('devuelto', false)
            ->first();

        if ($prestamoActivo) {
            $mensaje = $prestamoActivo->estado === 'pendiente' 
                ? 'Ya tienes una solicitud pendiente de este libro' 
                : 'Ya tienes este libro prestado';
            
            return response()->json([
                'error' => $mensaje,
                'estado' => $prestamoActivo->estado,
                'fecha_devolucion_esperada' => $prestamoActivo->fecha_devolucion ? $prestamoActivo->fecha_devolucion->format('d/m/Y') : null
            ], 400);
        }

        // Crear préstamo con fecha de devolución de 15 días
        $fechaPrestamo = now();
        $fechaDevolucion = now()->addDays(15);

        // Obtener el estudiante_id del usuario
        $user = $request->user();
        $estudianteId = $user->estudiante ? $user->estudiante->id : null;

        if (!$estudianteId) {
            return response()->json([
                'error' => 'Usuario no válido',
                'mensaje' => 'Solo los estudiantes pueden solicitar préstamos de libros'
            ], 403);
        }

        $prestamo = PrestamoLibro::create([
            'libro_id' => $request->libro_id,
            'estudiante_id' => $estudianteId,
            'user_id' => $userId,
            'fecha_prestamo' => $fechaPrestamo,
            'fecha_devolucion' => $fechaDevolucion,
            'devuelto' => false,
            'estado' => 'pendiente', // Estado inicial: pendiente de aprobación
        ]);

        return response()->json([
            'message' => 'Solicitud de préstamo enviada. Espera la aprobación del bibliotecario.',
            'prestamo' => $prestamo->load(['libro.categoria']),
            'fecha_devolucion' => $fechaDevolucion->format('d/m/Y'),
            'dias_prestamo' => 15,
            'prestamos_activos_totales' => $prestamosActivos + 1
        ], 201);
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

    /**
     * Aprobar préstamo (bibliotecario/admin)
     */
    public function aprobar(Request $request, $id)
    {
        $prestamo = PrestamoLibro::findOrFail($id);

        if ($prestamo->estado !== 'pendiente') {
            return response()->json([
                'error' => 'Este préstamo ya fue procesado',
                'estado_actual' => $prestamo->estado
            ], 400);
        }

        // Verificar nuevamente stock disponible
        $libro = $prestamo->libro;
        if ($libro->cantidad_disponible <= 0) {
            return response()->json([
                'error' => 'No hay stock disponible',
                'mensaje' => 'Ya no quedan copias disponibles de este libro'
            ], 400);
        }

        $prestamo->update([
            'estado' => 'aprobado',
            'aprobado_por' => $request->user()->id,
            'fecha_respuesta' => now(),
        ]);

        return response()->json([
            'message' => 'Préstamo aprobado exitosamente',
            'prestamo' => $prestamo->load(['libro.categoria', 'usuario', 'aprobador'])
        ]);
    }

    /**
     * Rechazar préstamo (bibliotecario/admin)
     */
    public function rechazar(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prestamo = PrestamoLibro::findOrFail($id);

        if ($prestamo->estado !== 'pendiente') {
            return response()->json([
                'error' => 'Este préstamo ya fue procesado',
                'estado_actual' => $prestamo->estado
            ], 400);
        }

        $prestamo->update([
            'estado' => 'rechazado',
            'aprobado_por' => $request->user()->id,
            'fecha_respuesta' => now(),
            'motivo_rechazo' => $request->motivo,
        ]);

        return response()->json([
            'message' => 'Préstamo rechazado',
            'prestamo' => $prestamo->load(['libro.categoria', 'usuario', 'aprobador'])
        ]);
    }

    /**
     * Obtener reportes y estadísticas de biblioteca
     */
    public function reportes()
    {
        $stats = [
            'total_libros' => Libro::count(),
            'libros_fisicos' => Libro::where('tipo', 'fisico')->count(),
            'libros_digitales' => Libro::where('tipo', 'digital')->count(),
            'total_prestamos' => PrestamoLibro::count(),
            'prestamos_activos' => PrestamoLibro::where('estado', 'aprobado')
                ->whereNull('fecha_devolucion')
                ->count(),
            'prestamos_pendientes' => PrestamoLibro::where('estado', 'pendiente')->count(),
            'prestamos_vencidos' => PrestamoLibro::where('estado', 'aprobado')
                ->whereNull('fecha_devolucion')
                ->where('fecha_devolucion', '<', now())
                ->count(),
            'libros_mas_prestados' => \DB::table('prestamos_libros')
                ->join('libros', 'prestamos_libros.libro_id', '=', 'libros.id')
                ->select(
                    'libros.id',
                    'libros.titulo',
                    'libros.autor',
                    \DB::raw('COUNT(*) as total_prestamos')
                )
                ->where('prestamos_libros.estado', 'aprobado')
                ->groupBy('libros.id', 'libros.titulo', 'libros.autor')
                ->orderBy('total_prestamos', 'desc')
                ->limit(5)
                ->get(),
            'estudiantes_activos' => PrestamoLibro::where('estado', 'aprobado')
                ->whereNull('fecha_devolucion')
                ->distinct('user_id')
                ->count('user_id'),
            'categorias_count' => \DB::table('categorias_libros')->count(),
        ];

        return response()->json($stats);
    }
}
