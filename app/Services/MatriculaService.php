<?php

namespace App\Services;

use App\Models\AnioEscolar;
use App\Models\Estudiante;
use App\Models\Matricula;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
}
