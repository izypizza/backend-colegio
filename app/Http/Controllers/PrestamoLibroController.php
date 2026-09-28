<?php

namespace App\Http\Controllers;

use App\Models\Libro;
use App\Models\PrestamoLibro;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrestamoLibroController extends Controller
{
    /**
     * Listar todos los préstamos
     */
    public function index(Request $request)
    {
        $query = PrestamoLibro::with(['libro.categoria', 'usuario', 'estudiante']);

        // Filtrar préstamos activos
        if ($request->has('activos') && $request->activos == 'true') {
            $query->where('estado', 'aprobado')->where('devuelto', false);
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
        $esPersonal = $request->user()->hasAnyRole(['admin', 'bibliotecario']);
        abort_unless($esPersonal || $request->user()->isEstudiante(), 403);

        $datos = $request->validate([
            'libro_id' => 'required|integer|exists:libros,id',
            'user_id' => $esPersonal ? 'required|integer|exists:users,id' : 'prohibited',
            'fecha_devolucion' => $esPersonal
                ? 'required|date_format:Y-m-d|after_or_equal:today' : 'prohibited',
        ]);

        return DB::transaction(function () use ($request, $datos, $esPersonal) {
            $usuario = User::lockForUpdate()->findOrFail(
                $esPersonal ? $datos['user_id'] : $request->user()->id
            );
            // Tomar ambos bloqueos antes de las lecturas de stock y relaciones.
            $libro = Libro::lockForUpdate()->findOrFail($datos['libro_id']);
            if (! $usuario->isEstudiante() || ! $usuario->is_active || ! $usuario->estudiante
                || $usuario->estudiante->estado !== 'activo') {
                throw ValidationException::withMessages(['user_id' => 'Seleccione un estudiante activo con cuenta vinculada.']);
            }

            if (! $libro->disponible || $libro->cantidad_disponible <= 0) {
                throw ValidationException::withMessages(['libro_id' => 'El libro no tiene copias disponibles.']);
            }
            $this->verificarLimites($usuario->id);

            if (PrestamoLibro::where('user_id', $usuario->id)->where('libro_id', $libro->id)
                ->whereIn('estado', ['pendiente', 'aprobado'])->where('devuelto', false)->exists()) {
                throw ValidationException::withMessages(['libro_id' => 'El estudiante ya tiene una solicitud o préstamo activo de este libro.']);
            }

            $fechaDevolucion = $esPersonal ? $datos['fecha_devolucion'] : now()->addDays(15)->toDateString();
            $prestamo = PrestamoLibro::create([
                'libro_id' => $libro->id,
                'estudiante_id' => $usuario->estudiante->id,
                'user_id' => $usuario->id,
                'fecha_prestamo' => now(),
                'fecha_devolucion' => $fechaDevolucion,
                'devuelto' => false,
                'estado' => $esPersonal ? 'aprobado' : 'pendiente',
                'aprobado_por' => $esPersonal ? $request->user()->id : null,
                'fecha_respuesta' => $esPersonal ? now() : null,
            ]);

            return response()->json([
                'message' => $esPersonal ? 'Préstamo registrado correctamente.' : 'Solicitud enviada. Espera la aprobación del bibliotecario.',
                'prestamo' => $prestamo->load(['libro.categoria', 'usuario']),
                'fecha_devolucion' => $prestamo->fecha_devolucion->format('d/m/Y'),
            ], 201);
        });
    }

    private function verificarLimites(int $usuarioId): void
    {
        $activos = PrestamoLibro::where('user_id', $usuarioId)
            ->where('estado', 'aprobado')->where('devuelto', false);

        if ((clone $activos)->whereDate('fecha_devolucion', '<', today())->exists()) {
            throw ValidationException::withMessages(['user_id' => 'El estudiante tiene préstamos vencidos.']);
        }
        if ($activos->count() >= 3) {
            throw ValidationException::withMessages(['user_id' => 'El estudiante alcanzó el límite de tres préstamos activos.']);
        }
    }

    /**
     * Registrar devolución de un libro
     */
    public function devolver(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $prestamo = PrestamoLibro::lockForUpdate()->findOrFail($id);
            if ($prestamo->devuelto || $prestamo->estado !== 'aprobado') {
                return response()->json(['message' => 'Solo se puede devolver un préstamo aprobado y no devuelto.'], 422);
            }
            $prestamo->update(['devuelto' => true]);

            return response()->json($prestamo->load(['libro.categoria', 'usuario']));
        });
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
        return DB::transaction(function () use ($request, $id) {
            $prestamo = PrestamoLibro::lockForUpdate()->findOrFail($id);
            if ($prestamo->estado !== 'pendiente' || $prestamo->devuelto) {
                return response()->json(['message' => 'Este préstamo ya fue procesado.'], 422);
            }
            $usuario = User::lockForUpdate()->findOrFail($prestamo->user_id);
            $libro = Libro::lockForUpdate()->findOrFail($prestamo->libro_id);
            if (! $usuario->is_active || ! $usuario->estudiante || $usuario->estudiante->estado !== 'activo') {
                throw ValidationException::withMessages(['user_id' => 'El estudiante no está activo.']);
            }
            if (! $libro->disponible || $libro->cantidad_disponible <= 0) {
                throw ValidationException::withMessages(['libro_id' => 'No hay copias disponibles.']);
            }
            $this->verificarLimites($usuario->id);
            $prestamo->update([
                'estado' => 'aprobado',
                'aprobado_por' => $request->user()->id,
                'fecha_respuesta' => now(),
                'fecha_prestamo' => today(),
                'fecha_devolucion' => today()->addDays(15),
            ]);

            return response()->json([
                'message' => 'Préstamo aprobado exitosamente',
                'prestamo' => $prestamo->load(['libro.categoria', 'usuario', 'aprobador']),
            ]);
        });
    }

    /**
     * Rechazar préstamo (bibliotecario/admin)
     */
    public function rechazar(Request $request, $id)
    {
        $datos = $request->validate(['motivo' => 'required|string|max:500']);

        return DB::transaction(function () use ($request, $id, $datos) {
            $prestamo = PrestamoLibro::lockForUpdate()->findOrFail($id);
            if ($prestamo->estado !== 'pendiente') {
                return response()->json(['message' => 'Este préstamo ya fue procesado.'], 422);
            }
            $prestamo->update([
                'estado' => 'rechazado',
                'aprobado_por' => $request->user()->id,
                'fecha_respuesta' => now(),
                'motivo_rechazo' => $datos['motivo'],
            ]);

            return response()->json([
                'message' => 'Préstamo rechazado',
                'prestamo' => $prestamo->load(['libro.categoria', 'usuario', 'aprobador']),
            ]);
        });
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
            'prestamos_devueltos' => PrestamoLibro::where('devuelto', true)->count(),
            'prestamos_activos' => PrestamoLibro::where('estado', 'aprobado')
                ->where('devuelto', false)
                ->count(),
            'prestamos_pendientes' => PrestamoLibro::where('estado', 'pendiente')->count(),
            'prestamos_vencidos' => PrestamoLibro::where('estado', 'aprobado')
                ->where('devuelto', false)
                ->whereDate('fecha_devolucion', '<', today())
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
                ->where('devuelto', false)
                ->distinct('user_id')
                ->count('user_id'),
            'categorias_count' => \DB::table('categorias_libros')->count(),
        ];

        return response()->json($stats);
    }
}
