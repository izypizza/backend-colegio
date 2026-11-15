<?php

namespace App\Http\Controllers;

use App\Helpers\AcademicYearHelper;
use App\Models\AnioEscolar;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\MatriculaApoderado;
use App\Models\Padre;
use App\Models\Seccion;
use App\Services\MatriculaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatriculaController extends Controller
{
    public function __construct(private MatriculaService $matriculas)
    {
    }

    /**
     * Listado paginado de matrículas con filtros por año, sección y estado.
     */
    public function index(Request $request)
    {
        $request->validate([
            'anio_escolar_id' => 'nullable|exists:anios_escolares,id',
            'seccion_id' => 'nullable|exists:secciones,id',
            'grado_id' => 'nullable|exists:grados,id',
            'estado' => 'nullable|in:' . implode(',', Matricula::ESTADOS),
            'buscar' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $consulta = Matricula::query()
            ->with([
                'estudiante:id,nombres,apellido_paterno,apellido_materno,dni,seccion_id,estado',
                'anioEscolar:id,anio,estado',
                'seccion:id,nombre,grado_id',
                'seccion.grado:id,nombre,nivel',
                'apoderados:id,matricula_id,padre_id,tipo,es_contacto_principal',
                'apoderados.padre:id,nombres,apellido_paterno,apellido_materno,telefono',
            ])
            ->when(
                $request->filled('anio_escolar_id'),
                fn ($q) => $q->where('anio_escolar_id', $request->input('anio_escolar_id'))
            )
            ->when(
                $request->filled('seccion_id'),
                fn ($q) => $q->where('seccion_id', $request->input('seccion_id'))
            )
            ->when(
                $request->filled('grado_id'),
                fn ($q) => $q->whereHas(
                    'seccion',
                    fn ($seccion) => $seccion->where('grado_id', $request->input('grado_id'))
                )
            )
            ->when(
                $request->filled('estado'),
                fn ($q) => $q->where('estado', $request->input('estado'))
            )
            ->when(
                $request->filled('buscar'),
                fn ($q) => $q->whereHas(
                    'estudiante',
                    fn ($estudiante) => $estudiante
                        ->where('nombres', 'like', '%' . $request->input('buscar') . '%')
                        ->orWhere('apellido_paterno', 'like', '%' . $request->input('buscar') . '%')
                        ->orWhere('apellido_materno', 'like', '%' . $request->input('buscar') . '%')
                        ->orWhere('dni', 'like', '%' . $request->input('buscar') . '%')
                )
            )
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json($consulta);
    }

    /**
     * Registra la matrícula de un estudiante existente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'anio_escolar_id' => 'required|exists:anios_escolares,id',
            'seccion_id' => 'required|exists:secciones,id',
            'tipo' => 'nullable|in:' . implode(',', Matricula::TIPOS),
            'observacion' => 'nullable|string|max:500',
        ], [
            'estudiante_id.required' => 'Debe seleccionar un estudiante',
            'estudiante_id.exists' => 'El estudiante seleccionado no existe',
            'anio_escolar_id.required' => 'Debe seleccionar un año escolar',
            'seccion_id.required' => 'Debe seleccionar una sección',
            'seccion_id.exists' => 'La sección seleccionada no existe',
        ]);

        $seccion = Seccion::with('grado')->findOrFail($validated['seccion_id']);
        $anioEscolar = AnioEscolar::findOrFail($validated['anio_escolar_id']);
        $estudiante = Estudiante::findOrFail($validated['estudiante_id']);

        $matricula = DB::transaction(function () use ($validated, $seccion, $anioEscolar, $estudiante, $request) {
            $matricula = $this->matriculas->matricular(
                $estudiante,
                $seccion,
                $anioEscolar,
                $validated['tipo'] ?? 'regular',
                $validated['observacion'] ?? null,
                $request->user()->id
            );

            $this->sincronizarSeccionDelEstudiante($estudiante, $seccion, $anioEscolar);

            return $matricula;
        });

        return response()->json([
            'message' => "Matrícula registrada para {$estudiante->nombre_completo}",
            'matricula' => $matricula->load(['estudiante', 'seccion.grado', 'anioEscolar']),
        ], 201);
    }

    /**
     * Cambia la sección de una matrícula vigente y/o actualiza su estado.
     */
    public function update(Request $request, string $id)
    {
        $matricula = Matricula::with(['estudiante', 'anioEscolar'])->findOrFail($id);

        $validated = $request->validate([
            'seccion_id' => 'nullable|exists:secciones,id',
            'estado' => 'nullable|in:' . implode(',', Matricula::ESTADOS),
            'observacion' => 'nullable|string|max:500',
        ]);

        if (!isset($validated['seccion_id']) && !isset($validated['estado'])) {
            throw ValidationException::withMessages([
                'matricula' => ['Indique la sección de destino o el nuevo estado de la matrícula.'],
            ]);
        }

        if (!$matricula->anioEscolar->permiteMatricula()) {
            throw ValidationException::withMessages([
                'anio_escolar_id' => [
                    "El año escolar {$matricula->anioEscolar->anio} está cerrado. Su historial no puede modificarse.",
                ],
            ]);
        }

        $matricula = DB::transaction(function () use ($validated, $matricula) {
            if (isset($validated['seccion_id'])) {
                $seccionDestino = Seccion::with('grado')->findOrFail($validated['seccion_id']);
                $matricula = $this->matriculas->trasladarDeSeccion(
                    $matricula,
                    $seccionDestino,
                    $validated['observacion'] ?? null
                );

                $this->sincronizarSeccionDelEstudiante(
                    $matricula->estudiante,
                    $seccionDestino,
                    $matricula->anioEscolar
                );
            }

            if (isset($validated['estado']) && $validated['estado'] !== $matricula->estado) {
                $matricula->update([
                    'estado' => $validated['estado'],
                    'observacion' => $this->combinarObservacion(
                        $matricula->observacion,
                        $validated['observacion'] ?? null
                    ),
                ]);
            }

            return $matricula->refresh();
        });

        return response()->json([
            'message' => 'Matrícula actualizada correctamente',
            'matricula' => $matricula->load(['estudiante', 'seccion.grado', 'anioEscolar']),
        ]);
    }

    /**
     * Registra al estudiante y su matrícula en un solo paso.
     * Si la persona ya existe por DNI solo se crea la matrícula del año.
     */
    public function registroCompleto(Request $request)
    {
        $comunes = $request->validate([
            'dni' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/'],
            'anio_escolar_id' => 'required|exists:anios_escolares,id',
            'seccion_id' => 'required|exists:secciones,id',
            'tipo' => 'nullable|in:' . implode(',', Matricula::TIPOS),
            'observacion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|regex:/^9[0-9]{8}$/',
            'direccion' => 'nullable|string|min:5|max:500',
            'apoderado' => 'nullable|array',
            'apoderado.dni' => 'nullable|string|size:8|regex:/^[0-9]{8}$/',
            'apoderado.nombres' => 'nullable|string|min:2|max:255',
            'apoderado.apellido_paterno' => 'nullable|string|min:2|max:255',
            'apoderado.apellido_materno' => 'nullable|string|min:2|max:255',
            'apoderado.telefono' => 'nullable|string|regex:/^9[0-9]{8}$/',
            'apoderado.tipo' => 'nullable|in:padre,madre,apoderado',
            'apoderado.es_contacto_principal' => 'nullable|boolean',
        ], [
            'dni.required' => 'El DNI del estudiante es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.regex' => 'El DNI solo debe contener números.',
            'anio_escolar_id.required' => 'Selecciona el año escolar.',
            'seccion_id.required' => 'Selecciona la sección.',
            'telefono.regex' => 'El teléfono debe tener 9 dígitos y comenzar con 9.',
            'apoderado.dni.size' => 'El DNI del apoderado debe tener 8 dígitos.',
        ]);

        $anioEscolar = AnioEscolar::findOrFail($comunes['anio_escolar_id']);
        $seccion = Seccion::with('grado')->findOrFail($comunes['seccion_id']);

        $resultado = DB::transaction(function () use ($comunes, $anioEscolar, $seccion, $request) {
            $estudiante = Estudiante::query()->where('dni', $comunes['dni'])->first();
            $personaCreada = false;

            if (!$estudiante) {
                $personales = $request->validate([
                    'nombres' => 'required|string|min:2|max:255',
                    'apellido_paterno' => 'required|string|min:2|max:255',
                    'apellido_materno' => 'required|string|min:2|max:255',
                    'fecha_nacimiento' => 'required|date|before:today',
                ], [
                    'nombres.required' => 'Los nombres son obligatorios para un estudiante nuevo.',
                    'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
                    'apellido_materno.required' => 'El apellido materno es obligatorio.',
                    'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
                ]);

                $validacionEdad = AcademicYearHelper::validarEdadParaGrado(
                    $personales['fecha_nacimiento'],
                    $seccion->grado->nombre
                );

                if (!$validacionEdad['valido']) {
                    throw ValidationException::withMessages([
                        'fecha_nacimiento' => [$validacionEdad['mensaje']],
                    ]);
                }

                $estudiante = Estudiante::create([
                    'nombres' => $personales['nombres'],
                    'apellido_paterno' => $personales['apellido_paterno'],
                    'apellido_materno' => $personales['apellido_materno'],
                    'dni' => $comunes['dni'],
                    'fecha_nacimiento' => $personales['fecha_nacimiento'],
                    'telefono' => $comunes['telefono'] ?? null,
                    'direccion' => $comunes['direccion'] ?? null,
                    'seccion_id' => $seccion->id,
                    'estado' => 'activo',
                ]);

                $personaCreada = true;
            }

            $matricula = $this->matriculas->matricular(
                $estudiante,
                $seccion,
                $anioEscolar,
                $comunes['tipo'] ?? 'regular',
                $comunes['observacion'] ?? null,
                $request->user()->id
            );

            $this->sincronizarSeccionDelEstudiante($estudiante, $seccion, $anioEscolar);

            $apoderado = $this->asociarApoderado($estudiante, $matricula, $comunes['apoderado'] ?? null);

            return compact('estudiante', 'matricula', 'personaCreada', 'apoderado');
        });

        return response()->json([
            'message' => $resultado['personaCreada']
                ? "Estudiante registrado y matriculado en {$seccion->grado->nombre} \"{$seccion->nombre}\"."
                : "Matrícula registrada para {$resultado['estudiante']->nombre_completo}.",
            'persona_creada' => $resultado['personaCreada'],
            'estudiante' => $resultado['estudiante']->load('seccion.grado'),
            'matricula' => $resultado['matricula']->load(['anioEscolar', 'seccion.grado']),
            'apoderado' => $resultado['apoderado'],
        ], 201);
    }

    /**
     * Busca al apoderado por DNI o lo crea, y lo asocia al estudiante.
     */
    private function asociarApoderado(Estudiante $estudiante, Matricula $matricula, ?array $datos): ?Padre
    {
        if (!$datos || empty($datos['dni'])) {
            return null;
        }

        $padre = Padre::query()->where('dni', $datos['dni'])->first();

        if (!$padre) {
            if (empty($datos['nombres']) || empty($datos['apellido_paterno']) || empty($datos['apellido_materno'])) {
                throw ValidationException::withMessages([
                    'apoderado.nombres' => ['Para registrar un apoderado nuevo indica nombres y apellidos.'],
                ]);
            }

            $padre = Padre::create([
                'nombres' => $datos['nombres'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'],
                'dni' => $datos['dni'],
                'telefono' => $datos['telefono'] ?? null,
            ]);
        }

        $estudiante->padres()->syncWithoutDetaching([$padre->id]);

        MatriculaApoderado::updateOrCreate(
            [
                'matricula_id' => $matricula->id,
                'padre_id' => $padre->id,
            ],
            [
                'tipo' => $datos['tipo'] ?? 'apoderado',
                'es_contacto_principal' => (bool) ($datos['es_contacto_principal'] ?? false),
            ]
        );

        return $padre;
    }

    /**
     * Años escolares disponibles, del más reciente al más antiguo.
     */
    public function aniosEscolares()
    {
        return response()->json(
            AnioEscolar::query()
                ->orderByDesc('anio')
                ->get(['id', 'anio', 'estado', 'fecha_inicio', 'fecha_fin'])
        );
    }

    /**
     * Crea el año escolar indicado si aún no existe. Es idempotente.
     */
    public function crearAnioEscolar(Request $request)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
        ], [
            'anio.required' => 'Indique el año escolar a crear',
            'anio.integer' => 'El año escolar debe ser un número',
            'anio.min' => 'El año escolar no puede ser anterior a 2000',
            'anio.max' => 'El año escolar no puede ser posterior a 2100',
        ]);

        $existente = AnioEscolar::query()->delAnio((int) $validated['anio'])->first();

        if ($existente) {
            return response()->json([
                'message' => "El año escolar {$existente->anio} ya existe",
                'anio_escolar' => $existente,
            ]);
        }

        $anioEscolar = $this->matriculas->obtenerOCrearAnioEscolar((int) $validated['anio']);

        return response()->json([
            'message' => "Año escolar {$anioEscolar->anio} creado ({$anioEscolar->estado})",
            'anio_escolar' => $anioEscolar,
        ], 201);
    }

    /**
     * Estudiantes que todavía no tienen matrícula en el año indicado.
     */
    public function estudiantesSinMatricula(Request $request)
    {
        $request->validate([
            'anio_escolar_id' => 'required|exists:anios_escolares,id',
            'buscar' => 'nullable|string|max:100',
        ]);

        $anioEscolarId = (int) $request->input('anio_escolar_id');

        $estudiantes = Estudiante::query()
            ->select('id', 'nombres', 'apellido_paterno', 'apellido_materno', 'dni', 'estado', 'seccion_id')
            ->with('seccion:id,nombre,grado_id', 'seccion.grado:id,nombre')
            ->whereNotIn('id', function ($consulta) use ($anioEscolarId) {
                $consulta->select('estudiante_id')
                    ->from('matriculas')
                    ->where('anio_escolar_id', $anioEscolarId);
            })
            ->when(
                $request->filled('buscar'),
                fn ($q) => $q->where(function ($sub) use ($request) {
                    $termino = '%' . $request->input('buscar') . '%';
                    $sub->where('nombres', 'like', $termino)
                        ->orWhere('apellido_paterno', 'like', $termino)
                        ->orWhere('apellido_materno', 'like', $termino)
                        ->orWhere('dni', 'like', $termino);
                })
            )
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->limit(50)
            ->get();

        return response()->json($estudiantes);
    }

    /**
     * Vacantes por sección para un año escolar.
     */
    public function vacantes(Request $request)
    {
        $request->validate([
            'anio_escolar_id' => 'required|exists:anios_escolares,id',
            'grado_id' => 'nullable|exists:grados,id',
        ]);

        $anioEscolar = AnioEscolar::findOrFail((int) $request->input('anio_escolar_id'));

        $secciones = Seccion::query()
            ->with('grado:id,nombre,nivel')
            ->when(
                $request->filled('grado_id'),
                fn ($q) => $q->where('grado_id', $request->input('grado_id'))
            )
            ->orderBy('grado_id')
            ->orderBy('nombre')
            ->get();

        $resumen = $secciones->map(function (Seccion $seccion) use ($anioEscolar) {
            $vacantes = $this->matriculas->resumenVacantes($seccion, $anioEscolar);

            return [
                'seccion_id' => $seccion->id,
                'seccion' => $seccion->nombre,
                'grado' => $seccion->grado?->nombre,
                'nivel' => $seccion->grado?->nivel,
                'capacidad' => $vacantes['capacidad'],
                'ocupadas' => $vacantes['ocupadas'],
                'disponibles' => $vacantes['disponibles'],
            ];
        })->values();

        return response()->json([
            'anio_escolar_id' => $anioEscolar->id,
            'anio' => $anioEscolar->anio,
            'secciones' => $resumen,
            'totales' => [
                'capacidad' => $resumen->sum('capacidad'),
                'ocupadas' => $resumen->sum('ocupadas'),
                'disponibles' => $resumen->sum('disponibles'),
            ],
        ]);
    }

    /**
     * Indicadores generales de matrícula del año.
     */
    public function estadisticas(Request $request)
    {
        $request->validate([
            'anio_escolar_id' => 'nullable|exists:anios_escolares,id',
        ]);

        $anioEscolar = $request->filled('anio_escolar_id')
            ? AnioEscolar::findOrFail((int) $request->input('anio_escolar_id'))
            : AnioEscolar::vigente() ?? AnioEscolar::orderByDesc('anio')->first();

        if (!$anioEscolar) {
            return response()->json([
                'anio' => null,
                'totales' => [
                    'matriculados' => 0,
                    'retirados' => 0,
                    'trasladados' => 0,
                    'culminados' => 0,
                    'total' => 0,
                ],
            ]);
        }

        $porEstado = Matricula::query()
            ->where('anio_escolar_id', $anioEscolar->id)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $totales = [
            'matriculados' => (int) ($porEstado['matriculado'] ?? 0),
            'retirados' => (int) ($porEstado['retirado'] ?? 0),
            'trasladados' => (int) ($porEstado['trasladado'] ?? 0),
            'culminados' => (int) ($porEstado['culminado'] ?? 0),
        ];
        $totales['total'] = array_sum($totales);

        return response()->json([
            'anio_escolar_id' => $anioEscolar->id,
            'anio' => $anioEscolar->anio,
            'estado_anio' => $anioEscolar->estado,
            'totales' => $totales,
        ]);
    }

    /**
     * La sección operativa del estudiante se mantiene alineada con su matrícula
     * mientras el histórico académico aún no dependa de la matrícula.
     */
    private function sincronizarSeccionDelEstudiante(
        Estudiante $estudiante,
        Seccion $seccion,
        AnioEscolar $anioEscolar
    ): void {
        $vigente = AnioEscolar::vigente();

        if ($vigente && $vigente->id !== $anioEscolar->id) {
            return;
        }

        if ((int) $estudiante->seccion_id !== (int) $seccion->id) {
            $estudiante->update(['seccion_id' => $seccion->id]);
        }
    }

    private function combinarObservacion(?string $actual, ?string $nueva): ?string
    {
        if (!$nueva) {
            return $actual;
        }

        return $actual ? "{$actual} | {$nueva}" : $nueva;
    }
}
