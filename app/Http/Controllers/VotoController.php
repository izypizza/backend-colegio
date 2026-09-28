<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Eleccion;
use App\Models\Voto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use ($request) {
            $eleccion = Eleccion::lockForUpdate()->findOrFail($request->eleccion_id);
            if (! $eleccion->votacion_abierta) {
                return response()->json([
                    'message' => 'La elección no está abierta para votar en este momento.',
                ], 400);
            }

            // Verificar si el usuario ya votó
            if ($eleccion->usuarioYaVoto($request->user())) {
                return response()->json([
                    'error' => 'Ya has votado en esta elección',
                    'mensaje' => 'Solo puedes votar una vez por elección',
                    'estado' => 'ya_voto',
                ], 400);
            }

            // Verificar que el candidato pertenece a la elección
            $candidato = Candidato::where('id', $request->candidato_id)
                ->where('eleccion_id', $request->eleccion_id)
                ->first();

            if (! $candidato) {
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
                'voto' => $voto,
            ], 201);
        });
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
