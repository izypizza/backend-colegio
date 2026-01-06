<?php

namespace App\Http\Controllers;

use App\Models\Eleccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EleccionController extends Controller
{
    /**
     * Listar todas las elecciones
     */
    public function index()
    {
        $elecciones = Eleccion::with('candidatos.estudiante')->get();
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
        $eleccion = Eleccion::with(['candidatos.estudiante', 'candidatos.votos'])
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
            $eleccion = Eleccion::findOrFail($id);
            
            // Verificar si la elección está activa
            if ($eleccion->estado !== 'activa') {
                return response()->json([
                    'puede_votar' => false,
                    'mensaje' => 'La elección no está activa'
                ]);
            }

            // Verificar fechas
            $now = now();
            if ($now->lt($eleccion->fecha_inicio) || $now->gt($eleccion->fecha_cierre)) {
                return response()->json([
                    'puede_votar' => false,
                    'mensaje' => 'La votación no está dentro del período permitido'
                ]);
            }

            $yaVoto = $eleccion->usuarioYaVoto($request->user());
            
            return response()->json([
                'ya_voto' => $yaVoto,
                'puede_votar' => !$yaVoto,
                'estado' => $eleccion->estado
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al verificar el voto'], 500);
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

            return response()->json([
                'message' => 'Elección activada exitosamente',
                'eleccion' => $eleccion
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al activar la elección'], 500);
        }
    }

    /**
     * Cerrar una elección (solo admin)
     */
    public function cerrar($id)
    {
        try {
            $eleccion = Eleccion::findOrFail($id);
            
            $eleccion->estado = 'cerrada';
            $eleccion->save();

            return response()->json([
                'message' => 'Elección cerrada exitosamente',
                'eleccion' => $eleccion
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cerrar la elección'], 500);
        }
    }

    /**
     * Publicar resultados (solo admin)
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

            return response()->json([
                'message' => 'Resultados publicados exitosamente',
                'eleccion' => $eleccion
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al publicar los resultados'], 500);
        }
    }

    /**
     * Eliminar una elección
     */
    public function destroy($id)
    {
        $eleccion = Eleccion::findOrFail($id);
        $eleccion->delete();
        return response()->json(['message' => 'Elección eliminada correctamente']);
    }
}
