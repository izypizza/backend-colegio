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
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'fecha' => 'required|date',
            'activa' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $eleccion = Eleccion::create($request->all());
        return response()->json($eleccion, 201);
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
        $eleccion = Eleccion::findOrFail($id);
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
    }

    /**
     * Verificar si el usuario ya votó
     */
    public function yaVote(Request $request, $id)
    {
        $eleccion = Eleccion::findOrFail($id);
        $yaVoto = $eleccion->usuarioYaVoto($request->user());
        
        return response()->json(['ya_voto' => $yaVoto]);
    }

    /**
     * Actualizar una elección
     */
    public function update(Request $request, $id)
    {
        $eleccion = Eleccion::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'titulo' => 'string|max:255',
            'fecha' => 'date',
            'activa' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $eleccion->update($request->all());
        return response()->json($eleccion);
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
