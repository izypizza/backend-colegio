<?php

namespace App\Http\Controllers;

use App\Models\Eleccion;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class EleccionController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Listar todas las elecciones
     */
    public function index()
    {
        $elecciones = Eleccion::with(['candidatos.partido', 'partidos'])
            ->withCount('votos')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($elecciones);
    }

    /**
     * Crear una nueva elección
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'fecha' => 'required|date',
                'fecha_inicio' => 'required|date|after_or_equal:now',
                'fecha_cierre' => 'required|date|after:fecha_inicio',
                'estado' => 'string|in:pendiente,activa,cerrada'
            ], [
                'titulo.required' => 'El título es requerido',
                'fecha_inicio.required' => 'La fecha de inicio es requerida',
                'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser igual o posterior a hoy',
                'fecha_cierre.required' => 'La fecha de cierre es requerida',
                'fecha_cierre.after' => 'La fecha de cierre debe ser posterior a la fecha de inicio'
            ]);

            $validated['estado'] = $validated['estado'] ?? 'pendiente';
            $validated['resultados_publicados'] = false;

            $eleccion = Eleccion::create($validated);
            return response()->json([
                'message' => 'Elección creada exitosamente',
                'eleccion' => $eleccion
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear la elección: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar una elección específica
     */
    public function show($id)
    {
        $eleccion = Eleccion::with([
                'candidatos.partido',
                'candidatos.votos',
                'partidos'
            ])
            ->withCount('votos')
            ->findOrFail($id);
        
        return response()->json($eleccion);
    }

    /**
     * Obtener resultados de una elección
     */
    public function resultados($id)
    {
        try {
            $eleccion = Eleccion::findOrFail($id);
            
            // Solo mostrar resultados si la elección está cerrada Y los resultados están publicados
            if ($eleccion->estado !== 'cerrada' || !$eleccion->resultados_publicados) {
                return response()->json([
                    'message' => 'Los resultados aún no están disponibles',
                    'estado' => $eleccion->estado,
                    'resultados_publicados' => $eleccion->resultados_publicados
                ], 403);
            }

            $resultados = $eleccion->resultados();
            $totalVotos = $eleccion->votos()->count();
            
            $data = [
                'eleccion' => $eleccion,
                'total_votos' => $totalVotos,
                'resultados' => $resultados->map(function($candidato) use ($totalVotos) {
                    return [
                        'candidato' => $candidato,
                        'votos' => $candidato->votos_count,
                        'porcentaje' => $totalVotos > 0 ? round(($candidato->votos_count / $totalVotos) * 100, 2) : 0,
                    ];
                }),
            ];
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los resultados'], 500);
        }
    }

    /**
     * Verificar si el usuario ya votó
     */
    public function yaVote(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $eleccion = Eleccion::findOrFail($id);
            
            // Verificar si la elección está activa
            if ($eleccion->estado !== 'activa') {
                return response()->json([
                    'ya_voto' => false,
                    'puede_votar' => false,
                    'mensaje' => 'La elección no está activa',
                    'estado' => $eleccion->estado
                ]);
            }

            // Verificar fechas (solo si están definidas)
            if ($eleccion->fecha_inicio && $eleccion->fecha_cierre) {
                $now = now();
                if ($now->lt($eleccion->fecha_inicio) || $now->gt($eleccion->fecha_cierre)) {
                    return response()->json([
                        'ya_voto' => false,
                        'puede_votar' => false,
                        'mensaje' => 'La votación no está dentro del período permitido',
                        'estado' => $eleccion->estado
                    ]);
                }
            }

            // Solo estudiantes pueden votar
            $puedeVotar = $user->role === 'estudiante';
            $yaVoto = $puedeVotar ? $eleccion->usuarioYaVoto($user) : false;
            
            return response()->json([
                'ya_voto' => $yaVoto,
                'puede_votar' => $puedeVotar && !$yaVoto,
                'estado' => $eleccion->estado,
                'es_estudiante' => $puedeVotar
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en yaVote: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al verificar el voto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una elección
     */
    public function update(Request $request, $id)
    {
        try {
            $eleccion = Eleccion::findOrFail($id);

            $validated = $request->validate([
                'titulo' => 'string|max:255',
                'fecha' => 'date',
                'fecha_inicio' => 'date',
                'fecha_cierre' => 'date|after:fecha_inicio',
                'estado' => 'string|in:pendiente,activa,cerrada'
            ], [
                'fecha_cierre.after' => 'La fecha de cierre debe ser posterior a la fecha de inicio'
            ]);

            $eleccion->update($validated);
            return response()->json([
                'message' => 'Elección actualizada exitosamente',
                'eleccion' => $eleccion
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la elección'], 500);
        }
    }

    /**
     * Activar una elección (solo admin)
     * Envía notificaciones a estudiantes y padres
     */
    public function activar($id)
    {
        try {
            $eleccion = Eleccion::findOrFail($id);
            
            if ($eleccion->estado === 'cerrada') {
                return response()->json(['message' => 'No se puede activar una elección cerrada'], 422);
            }

            $eleccion->estado = 'activa';
            $eleccion->save();

            // Enviar notificaciones a estudiantes y padres
            $descripcion = "Ya puedes participar en la votación";
            if ($eleccion->fecha_cierre) {
                $descripcion .= " hasta el " . $eleccion->fecha_cierre->format('d/m/Y H:i');
            }
            
            $count = $this->notificationService->notificarEleccionHabilitada(
                $eleccion->id,
                $eleccion->titulo,
                $descripcion
            );

            return response()->json([
                'message' => 'Elección activada exitosamente',
                'notificaciones_enviadas' => $count,
                'eleccion' => $eleccion
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al activar la elección: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cerrar una elección (solo admin)
     */
    public function cerrar($id)
    {
        try {
            $eleccion = Eleccion::with(['candidatos.votos', 'partidos'])->findOrFail($id);
            
            if ($eleccion->estado === 'cerrada') {
                return response()->json(['message' => 'La elección ya está cerrada'], 422);
            }
            
            $eleccion->estado = 'cerrada';
            $eleccion->save();

            // Conteo automático de votos
            $resultados = [
                'total_votos' => $eleccion->votos()->count(),
                'candidatos' => [],
                'partidos' => []
            ];

            // Contar votos por candidato
            foreach ($eleccion->candidatos as $candidato) {
                $votos = $candidato->votos()->count();
                $resultados['candidatos'][] = [
                    'candidato_id' => $candidato->id,
                    'nombre' => $candidato->nombre,
                    'cargo' => $candidato->cargo,
                    'votos' => $votos,
                    'partido' => $candidato->partido ? $candidato->partido->nombre : null
                ];
            }

            // Contar votos por partido
            foreach ($eleccion->partidos as $partido) {
                $votosPartido = 0;
                foreach ($partido->candidatos as $candidato) {
                    $votosPartido += $candidato->votos()->count();
                }
                $resultados['partidos'][] = [
                    'partido_id' => $partido->id,
                    'nombre' => $partido->nombre,
                    'siglas' => $partido->siglas,
                    'color' => $partido->color,
                    'votos' => $votosPartido
                ];
            }

            // Ordenar por votos
            usort($resultados['candidatos'], fn($a, $b) => $b['votos'] - $a['votos']);
            usort($resultados['partidos'], fn($a, $b) => $b['votos'] - $a['votos']);

            return response()->json([
                'message' => 'Elección cerrada exitosamente. Conteo realizado.',
                'eleccion' => $eleccion,
                'resultados' => $resultados
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cerrar la elección: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Publicar resultados (solo admin)
     * Envía notificaciones a estudiantes y padres
     */
    public function publicarResultados($id)
    {
        try {
            $eleccion = Eleccion::findOrFail($id);
            
            if ($eleccion->estado !== 'cerrada') {
                return response()->json(['message' => 'Solo se pueden publicar resultados de elecciones cerradas'], 422);
            }

            $eleccion->resultados_publicados = true;
            $eleccion->save();

            // Enviar notificaciones sobre resultados publicados
            $count = $this->notificationService->crearPorRol(
                'estudiante',
                'Resultados de Elección',
                "Los resultados de '{$eleccion->titulo}' ya están disponibles. ¡Revísalos ahora!",
                'eleccion',
                ['eleccion_id' => $eleccion->id],
                "/dashboard/estudiante/elecciones/{$eleccion->id}/resultados"
            );

            $count += $this->notificationService->crearPorRol(
                'padre',
                'Resultados de Elección Estudiantil',
                "Ya se publicaron los resultados de '{$eleccion->titulo}'.",
                'eleccion',
                ['eleccion_id' => $eleccion->id],
                "/dashboard/padre/elecciones"
            );

            return response()->json([
                'message' => 'Resultados publicados exitosamente',
                'notificaciones_enviadas' => $count,
                'eleccion' => $eleccion
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al publicar los resultados: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar una elección.
     * Restricciones: no se puede eliminar una elección activa
     * ni una que ya tenga votos emitidos —son registros electorales oficiales.
     */
    public function destroy($id)
    {
        try {
            $eleccion = Eleccion::withCount('votos')->findOrFail($id);

            // Bloquear si está activa
            if ($eleccion->estado === 'activa') {
                return response()->json([
                    'message' => "No se puede eliminar la elección \"{$eleccion->titulo}\" porque está activa. Ciérrala primero.",
                ], 422);
            }

            // Bloquear si ya tiene votos emitidos
            if ($eleccion->votos_count > 0) {
                return response()->json([
                    'message' => "No se puede eliminar la elección \"{$eleccion->titulo}\" porque tiene {$eleccion->votos_count} voto(s) emitido(s). Los registros electorales no pueden borrarse.",
                    'votos' => $eleccion->votos_count,
                ], 422);
            }

            // Eliminar candidatos vinculados (sin votos) antes de eliminar la elección
            $eleccion->candidatos()->delete();
            $eleccion->delete();

            return response()->json(['message' => "Elección \"{$eleccion->titulo}\" eliminada correctamente"]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Elección no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la elección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
