<?php

namespace App\Services;

use App\Models\AnioEscolar;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Seccion;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatriculaService
{
    private const ESTADO_POR_ESTUDIANTE = [
        'activo' => 'matriculado',
        'suspendido' => 'matriculado',
        'egresado' => 'culminado',
    ];

    /**
     * Devuelve el año escolar solicitado, creándolo si todavía no existe.
     * El estado se deduce comparando con el año calendario en curso.
     */
    public function obtenerOCrearAnioEscolar(int $anio): AnioEscolar
    {
        $existente = AnioEscolar::query()->delAnio($anio)->first();

        if ($existente) {
            return $existente;
        }

        return AnioEscolar::create([
            'anio' => $anio,
            'estado' => $this->estadoSegunAnio($anio),
            'fecha_inicio' => "{$anio}-03-01",
            'fecha_fin' => "{$anio}-12-31",
        ]);
    }

    /**
     * Devuelve el año del periodo académico activo y, si no hay ninguno,
     * el año calendario en curso. Evita depender solo del reloj del servidor.
     */
    public function anioEscolarEnCurso(): int
    {
        $anioDelPeriodoActivo = DB::table('periodos_academicos')
            ->where('estado', 'activo')
            ->orderByDesc('anio')
            ->value('anio');

        return $anioDelPeriodoActivo
            ? (int) $anioDelPeriodoActivo
            : (int) Carbon::now()->year;
    }

    /**
     * Genera la matrícula de todos los estudiantes que aún no la tienen en el
     * año indicado. Es idempotente: puede ejecutarse varias veces sin duplicar.
     */
    public function generarMatriculasPendientes(
        AnioEscolar $anioEscolar,
        string $origen = 'migracion',
        ?CarbonInterface $fechaMatricula = null,
        ?int $registradoPorId = null
    ): int {
        if (!in_array($origen, ['migracion', 'sistema'], true)) {
            throw new \InvalidArgumentException("Origen de matrícula no válido: {$origen}");
        }

        $fecha = ($fechaMatricula ?? Carbon::now())->toDateString();
        $generadas = 0;

        DB::transaction(function () use ($anioEscolar, $origen, $fecha, $registradoPorId, &$generadas) {
            $estudiantesSinMatricula = Estudiante::query()
                ->whereNotIn('id', function ($consulta) use ($anioEscolar) {
                    $consulta->select('estudiante_id')
                        ->from('matriculas')
                        ->where('anio_escolar_id', $anioEscolar->id);
                })
                ->chunkById(200, function ($estudiantes) use ($anioEscolar, $origen, $fecha, $registradoPorId, &$generadas) {
                    foreach ($estudiantes as $estudiante) {
                        $estado = self::ESTADO_POR_ESTUDIANTE[$estudiante->estado] ?? 'matriculado';

                        Matricula::create([
                            'estudiante_id' => $estudiante->id,
                            'anio_escolar_id' => $anioEscolar->id,
                            'seccion_id' => $estudiante->seccion_id,
                            'estado' => $estado,
                            'tipo' => 'regular',
                            'origen' => $origen,
                            'fecha_matricula' => $fecha,
                            'observacion' => $this->observacionSegunEstado($estudiante->estado),
                            'registrado_por' => $registradoPorId,
                        ]);

                        $generadas++;
                    }
                });
        });

        return $generadas;
    }

    private function estadoSegunAnio(int $anio): string
    {
        $anioActual = (int) Carbon::now()->year;

        if ($anio > $anioActual) {
            return 'planificacion';
        }

        if ($anio < $anioActual) {
            return 'cerrado';
        }

        return 'abierto';
    }

    private function observacionSegunEstado(string $estadoEstudiante): ?string
    {
        return match ($estadoEstudiante) {
            'suspendido' => 'Estudiante suspendido al generar la matrícula.',
            'egresado' => 'Estudiante egresado al generar la matrícula.',
            default => null,
        };
    }

    /**
     * Registra la matrícula de un estudiante en un año escolar concreto.
     * No modifica la sección actual del estudiante: eso lo decide el llamador.
     */
    public function matricular(
        Estudiante $estudiante,
        Seccion $seccion,
        AnioEscolar $anioEscolar,
        string $tipo = 'regular',
        ?string $observacion = null,
        ?int $registradoPorId = null,
        ?CarbonInterface $fechaMatricula = null
    ): Matricula {
        if (!$anioEscolar->permiteMatricula()) {
            throw ValidationException::withMessages([
                'anio_escolar_id' => [
                    "El año escolar {$anioEscolar->anio} está cerrado. Su historial no puede modificarse.",
                ],
            ]);
        }

        if (!in_array($tipo, Matricula::TIPOS, true)) {
            throw ValidationException::withMessages([
                'tipo' => ["El tipo de matrícula no es válido. Opciones: " . implode(', ', Matricula::TIPOS) . '.'],
            ]);
        }

        $yaMatriculado = Matricula::query()
            ->where('estudiante_id', $estudiante->id)
            ->where('anio_escolar_id', $anioEscolar->id)
            ->exists();

        if ($yaMatriculado) {
            throw ValidationException::withMessages([
                'estudiante_id' => [
                    'El estudiante ya tiene una matrícula registrada en el año ' . $anioEscolar->anio . '.',
                ],
            ]);
        }

        $this->verificarVacanteDisponible($seccion, $anioEscolar);

        return Matricula::create([
            'estudiante_id' => $estudiante->id,
            'anio_escolar_id' => $anioEscolar->id,
            'seccion_id' => $seccion->id,
            'estado' => 'matriculado',
            'tipo' => $tipo,
            'origen' => 'sistema',
            'fecha_matricula' => ($fechaMatricula ?? Carbon::now())->toDateString(),
            'observacion' => $observacion,
            'registrado_por' => $registradoPorId,
        ]);
    }

    /**
     * Cambia de sección una matrícula vigente y deja constancia del traslado.
     */
    public function trasladarDeSeccion(
        Matricula $matricula,
        Seccion $seccionDestino,
        ?string $observacion = null
    ): Matricula {
        if (!$matricula->estaVigente()) {
            throw ValidationException::withMessages([
                'matricula' => ['Solo se puede trasladar una matrícula vigente.'],
            ]);
        }

        if (!$matricula->anioEscolar->permiteMatricula()) {
            throw ValidationException::withMessages([
                'anio_escolar_id' => [
                    "El año escolar {$matricula->anioEscolar->anio} está cerrado. Su historial no puede modificarse.",
                ],
            ]);
        }

        if ((int) $matricula->seccion_id === (int) $seccionDestino->id) {
            throw ValidationException::withMessages([
                'seccion_id' => ['El estudiante ya pertenece a esa sección.'],
            ]);
        }

        $this->verificarVacanteDisponible(
            $seccionDestino,
            $matricula->anioEscolar,
            $matricula->id
        );

        $origen = $matricula->seccion()->with('grado')->first();
        $registro = trim(
            "Traslado interno desde {$origen->nombre} del {$origen->grado->nombre}"
            . ($observacion ? ". {$observacion}" : '.')
        );

        $matricula->update([
            'seccion_id' => $seccionDestino->id,
            'observacion' => $matricula->observacion
                ? "{$matricula->observacion} | {$registro}"
                : $registro,
        ]);

        return $matricula->refresh();
    }

    /**
     * Cantidad de matrículas que ocupan vacante: ni retiradas, ni trasladadas, ni culminadas.
     */
    public function contarOcupadas(Seccion $seccion, AnioEscolar $anioEscolar, ?int $matriculaExcluidaId = null): int
    {
        return Matricula::query()
            ->where('anio_escolar_id', $anioEscolar->id)
            ->where('seccion_id', $seccion->id)
            ->where('estado', 'matriculado')
            ->when(
                $matriculaExcluidaId,
                fn ($consulta) => $consulta->where('id', '!=', $matriculaExcluidaId)
            )
            ->count();
    }

    /**
     * Resumen de vacantes de una sección en un año escolar.
     *
     * @return array{capacidad: int, ocupadas: int, disponibles: int}
     */
    public function resumenVacantes(Seccion $seccion, AnioEscolar $anioEscolar): array
    {
        $capacidad = (int) ($seccion->capacidad_maxima ?? 0);
        $ocupadas = $this->contarOcupadas($seccion, $anioEscolar);

        return [
            'capacidad' => $capacidad,
            'ocupadas' => $ocupadas,
            'disponibles' => max(0, $capacidad - $ocupadas),
        ];
    }

    private function verificarVacanteDisponible(
        Seccion $seccion,
        AnioEscolar $anioEscolar,
        ?int $matriculaExcluidaId = null
    ): void {
        $capacidad = $seccion->capacidad_maxima;

        if ($capacidad === null) {
            return;
        }

        $ocupadas = $this->contarOcupadas($seccion, $anioEscolar, $matriculaExcluidaId);

        if ($ocupadas >= $capacidad) {
            $grado = $seccion->grado?->nombre ?? '';

            throw ValidationException::withMessages([
                'seccion_id' => [
                    "La sección {$seccion->nombre} {$grado} ya alcanzó su capacidad máxima ({$ocupadas} de {$capacidad} estudiantes).",
                ],
            ]);
        }
    }
}
