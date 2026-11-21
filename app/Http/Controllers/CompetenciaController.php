<?php

namespace App\Http\Controllers;

use App\Helpers\CnebHelper;
use App\Models\Competencia;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompetenciaController extends Controller
{
    /**
     * Catálogo de competencias del Currículo Nacional.
     */
    public function index(Request $request)
    {
        $request->validate([
            'materia_id' => 'nullable|exists:materias,id',
            'ciclo' => ['nullable', Rule::in(CnebHelper::CICLOS)],
            'activo' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:5|max:200',
        ]);

        $query = Competencia::query()
            ->with('materia:id,nombre')
            ->when($request->filled('materia_id'), fn ($q) => $q->where('materia_id', $request->input('materia_id')))
            ->when($request->filled('ciclo'), fn ($q) => $q->where('ciclo', $request->input('ciclo')))
            ->when($request->filled('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = '%' . mb_strtolower(trim($request->input('buscar'))) . '%';
                $q->where(function ($sub) use ($termino) {
                    $sub->whereRaw('LOWER(nombre) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(codigo) LIKE ?', [$termino]);
                });
            })
            ->ordenadas();

        if ($request->boolean('all')) {
            return response()->json($query->get());
        }

        return response()->json($query->paginate($request->integer('per_page', 50)));
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);

        $competencia = Competencia::create($validated);

        return response()->json([
            'message' => 'Competencia registrada correctamente',
            'competencia' => $competencia->load('materia:id,nombre'),
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $competencia = Competencia::findOrFail($id);
        $validated = $this->validar($request, $competencia->id);

        $competencia->update($validated);

        return response()->json([
            'message' => 'Competencia actualizada correctamente',
            'competencia' => $competencia->load('materia:id,nombre'),
        ]);
    }

    public function destroy(string $id)
    {
        $competencia = Competencia::withCount('calificaciones')->findOrFail($id);

        if ($competencia->calificaciones_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar una competencia con calificaciones registradas. Desactívela en su lugar.',
            ], 422);
        }

        $competencia->delete();

        return response()->json(['message' => 'Competencia eliminada correctamente']);
    }

    /**
     * Competencias aplicables a una sección y materia según su ciclo.
     */
    public function porCurso(Request $request)
    {
        $validated = $request->validate([
            'seccion_id' => 'required|exists:secciones,id',
            'materia_id' => 'required|exists:materias,id',
        ]);

        $seccion = Seccion::with('grado:id,nombre,nivel,ciclo')->findOrFail($validated['seccion_id']);
        $ciclo = $seccion->grado?->ciclo;

        $competencias = Competencia::query()
            ->activas()
            ->delCiclo($ciclo)
            ->where('materia_id', $validated['materia_id'])
            ->ordenadas()
            ->get(['id', 'codigo', 'nombre', 'descripcion', 'orden']);

        return response()->json([
            'seccion' => [
                'id' => $seccion->id,
                'nombre' => trim(($seccion->grado->nombre ?? '') . ' - ' . $seccion->nombre),
                'ciclo' => $ciclo,
            ],
            'competencias' => $competencias,
        ]);
    }

    /**
     * Catálogo agrupado por área para una sección concreta.
     */
    public function catalogoSeccion(string $seccionId)
    {
        $seccion = Seccion::with('grado:id,nombre,nivel,ciclo')->findOrFail($seccionId);
        $ciclo = $seccion->grado?->ciclo;

        $competencias = Competencia::query()
            ->activas()
            ->delCiclo($ciclo)
            ->with('materia:id,nombre')
            ->ordenadas()
            ->get()
            ->groupBy('materia_id')
            ->map(fn ($grupo) => [
                'materia_id' => $grupo->first()->materia_id,
                'materia' => $grupo->first()->materia?->nombre,
                'competencias' => $grupo->map(fn (Competencia $competencia) => [
                    'id' => $competencia->id,
                    'codigo' => $competencia->codigo,
                    'nombre' => $competencia->nombre,
                    'orden' => $competencia->orden,
                ])->values(),
            ])
            ->values();

        return response()->json([
            'seccion' => [
                'id' => $seccion->id,
                'nombre' => trim(($seccion->grado->nombre ?? '') . ' - ' . $seccion->nombre),
                'ciclo' => $ciclo,
            ],
            'areas' => $competencias,
        ]);
    }

    /**
     * Ciclo que corresponde a cada grado registrado.
     */
    public function ciclos()
    {
        $materias = Materia::query()->orderBy('nombre')->get(['id', 'nombre']);
        $estudiantes = Estudiante::query()->whereNotNull('seccion_id')->count();

        return response()->json([
            'ciclos' => CnebHelper::CICLOS,
            'niveles' => CnebHelper::NIVELES,
            'escalas' => CnebHelper::ESCALAS,
            'materias' => $materias,
            'estudiantes_con_seccion' => $estudiantes,
        ]);
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'ciclo' => ['required', Rule::in(CnebHelper::CICLOS)],
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('competencias', 'codigo')
                    ->where('materia_id', $request->input('materia_id'))
                    ->where('ciclo', $request->input('ciclo'))
                    ->ignore($ignorarId),
            ],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'orden' => 'nullable|integer|min:0|max:99',
            'activo' => 'nullable|boolean',
        ], [
            'codigo.unique' => 'Ya existe una competencia con ese código en esta área y ciclo.',
            'ciclo.in' => 'El ciclo debe ser III, IV, V, VI o VII.',
        ]);
    }
}
