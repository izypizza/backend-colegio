<?php

namespace App\Http\Controllers;

use App\Models\Voto;
use App\Models\Eleccion;
use App\Models\Candidato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VotoController extends Controller
{
    /**
     * Registrar un voto
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eleccion_id' => 'required|exists:elecciones,id',
            'candidato_id' => 'required|exists:candidatos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $eleccion = Eleccion::with('candidatos')->findOrFail($request->eleccion_id);
        
        // Verificar estado de la elección con mensajes detallados
        if ($eleccion->estado === 'pendiente') {
            return response()->json([
                'error' => 'La votación aún no ha comenzado',
                'mensaje' => 'Esta elección comenzará el ' . $eleccion->fecha_inicio->format('d/m/Y H:i'),
                'estado' => 'pendiente',
                'fecha_inicio' => $eleccion->fecha_inicio,
                'fecha_cierre' => $eleccion->fecha_cierre
            ], 400);
        }

        if ($eleccion->estado === 'cerrada') {
            return response()->json([
                'error' => 'La votación ha finalizado',
                'mensaje' => 'Esta elección cerró el ' . $eleccion->fecha_cierre->format('d/m/Y H:i'),
                'estado' => 'cerrada',
                'resultados_publicados' => $eleccion->resultados_publicados
            ], 400);
        }

        if ($eleccion->estado !== 'activa') {
            return response()->json([
                'error' => 'No se puede votar en esta elección',
                'mensaje' => 'La elección no está disponible para votar',
                'estado' => $eleccion->estado
            ], 400);
        }

        // Verificar si el usuario ya votó
        if ($eleccion->usuarioYaVoto($request->user())) {
            return response()->json([
                'error' => 'Ya has votado en esta elección',
                'mensaje' => 'Solo puedes votar una vez por elección',
                'estado' => 'ya_voto'
            ], 400);
        }

        // Verificar que el candidato pertenece a la elección
        $candidato = Candidato::where('id', $request->candidato_id)
            ->where('eleccion_id', $request->eleccion_id)
            ->first();

        if (!$candidato) {
            return response()->json(['error' => 'Candidato no válido para esta elección'], 400);
        }

        // Registrar voto
        $voto = Voto::create([
            'eleccion_id' => $request->eleccion_id,
            'candidato_id' => $request->candidato_id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Voto registrado exitosamente',
            'voto' => $voto
        ], 201);
    }

    /**
     * Mis votos (historial del usuario)
     */
    public function misVotos(Request $request)
    {
        $votos = Voto::with(['eleccion', 'candidato.estudiante'])
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json($votos);
    }
}
