<?php

namespace App\Http\Controllers;

use App\Helpers\BusquedaHelper;
use App\Models\EjemplarLibro;
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
        $query = PrestamoLibro::with(['libro.categoria', 'usuario', 'estudiante', 'ejemplar']);

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
     * Buscar estudiantes con cuenta activa para registrar un préstamo.
     * Evita que el bibliotecario tenga que memorizar un ID numérico.
     */
    public function estudiantesDisponibles(Request $request)
    {
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'auxiliar', 'bibliotecario']),
            403
        );

        $consulta = User::query()
            ->where('role', 'estudiante')
            ->where('is_active', true)
            ->whereHas('estudiante', fn ($q) => $q->where('estado', 'activo'))
            ->with(['estudiante.seccion.grado']);

        if ($request->filled('buscar')) {
            $texto = $request->input('buscar');

            $consulta->where(function ($q) use ($texto) {
                BusquedaHelper::aplicar($q, $texto, ['name', 'email']);

                $q->orWhereHas('estudiante', function ($sub) use ($texto) {
                    BusquedaHelper::aplicar($sub, $texto, [
                        'nombres',
                        'apellido_paterno',
                        'apellido_materno',
                        'dni',
                    ]);
                });
            });
        }

        $estudiantes = $consulta
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(function (User $usuario) {
                $estudiante = $usuario->estudiante;
                $seccion = $estudiante?->seccion;

                $nombreCompleto = trim(
                    ($estudiante->apellido_paterno ?? '') . ' ' .
                    ($estudiante->apellido_materno ?? '') . ', ' .
                    ($estudiante->nombres ?? '')
                );

                return [
                    'user_id' => $usuario->id,
                    'estudiante_id' => $estudiante?->id,
                    'nombre_completo' => $nombreCompleto !== '' && $nombreCompleto !== ','
                        ? $nombreCompleto
                        : $usuario->name,
                    'dni' => $estudiante?->dni,
                    'seccion' => $seccion
                        ? trim(($seccion->grado->nombre ?? '') . ' ' . $seccion->nombre)
                        : null,
                ];
            })
            ->values();

        return response()->json(['estudiantes' => $estudiantes]);
    }

    /**
     * Listar ejemplares disponibles de un libro.
     */
    public function ejemplaresDisponibles(Request $request)
    {
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'auxiliar', 'bibliotecario']),
            403
        );

        $datos = $request->validate([
            'libro_id' => 'required|integer|exists:libros,id',
        ]);

        $ejemplares = EjemplarLibro::where('libro_id', $datos['libro_id'])
            ->where('estado', 'disponible')
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'estado']);

        return response()->json(['ejemplares' => $ejemplares]);
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
            'ejemplar_id' => $esPersonal ? 'nullable|integer|exists:ejemplares_libros,id' : 'prohibited',
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

            $ejemplar = null;

            if ($esPersonal) {
                $this->asegurarEjemplares($libro);

                $ejemplar = ! empty($datos['ejemplar_id'])
                    ? EjemplarLibro::lockForUpdate()->findOrFail($datos['ejemplar_id'])
                    : EjemplarLibro::where('libro_id', $libro->id)
                        ->where('estado', 'disponible')
                        ->lockForUpdate()
                        ->orderBy('id')
                        ->first();

                if (! $ejemplar || $ejemplar->libro_id !== $libro->id || $ejemplar->estado !== 'disponible') {
                    throw ValidationException::withMessages(['ejemplar_id' => 'El ejemplar seleccionado no está disponible.']);
                }

                $ejemplar->update(['estado' => 'prestado']);
            } elseif (! $libro->disponible || $libro->cantidad_disponible <= 0) {
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
                'ejemplar_id' => $ejemplar?->id,
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
                'prestamo' => $prestamo->load(['libro.categoria', 'usuario', 'ejemplar']),
                'fecha_devolucion' => $prestamo->fecha_devolucion->format('d/m/Y'),
            ], 201);
        });
    }

    private function asegurarEjemplares(Libro $libro): void
    {
        $actual = $libro->ejemplares()->count();
        $cantidad = max(1, (int) ($libro->cantidad_total ?? 1));

        for ($numero = $actual + 1; $numero <= $cantidad; $numero++) {
            EjemplarLibro::create([
                'libro_id' => $libro->id,
                'codigo' => 'LIB-' . $libro->id . '-' . str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'estado' => 'disponible',
            ]);
        }
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
            $prestamo->update(['devuelto' => true, 'estado' => 'devuelto']);

            if ($prestamo->ejemplar_id) {
                EjemplarLibro::whereKey($prestamo->ejemplar_id)
                    ->update(['estado' => 'disponible']);
            }

            return response()->json($prestamo->load(['libro.categoria', 'usuario', 'ejemplar']));
        });
    }

    /**
     * Mis préstamos (usuario autenticado)
     */
    public function misPrestamos(Request $request)
    {
        $prestamos = PrestamoLibro::with(['libro.categoria', 'ejemplar'])
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
            $this->asegurarEjemplares($libro);

            $ejemplar = EjemplarLibro::where('libro_id', $libro->id)
                ->where('estado', 'disponible')
                ->lockForUpdate()
                ->orderBy('id')
                ->first();

            if (! $ejemplar) {
                throw ValidationException::withMessages(['ejemplar_id' => 'No hay copias disponibles.']);
            }

            $ejemplar->update(['estado' => 'prestado']);
            $this->verificarLimites($usuario->id);
            $prestamo->update([
                'ejemplar_id' => $ejemplar->id,
                'estado' => 'aprobado',
                'aprobado_por' => $request->user()->id,
                'fecha_respuesta' => now(),
                'fecha_prestamo' => today(),
                'fecha_devolucion' => today()->addDays(15),
            ]);

            return response()->json([
                'message' => 'Préstamo aprobado exitosamente',
                'prestamo' => $prestamo->load(['libro.categoria', 'usuario', 'aprobador', 'ejemplar']),
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
                ->whereIn('prestamos_libros.estado', ['aprobado', 'devuelto'])
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
