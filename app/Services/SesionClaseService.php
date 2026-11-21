<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\AsistenciaSesion;
use App\Models\AsignacionDocenteMateria;
use App\Models\Estudiante;
use App\Models\SesionClase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SesionClaseService
{
    /**
     * Abre (o reutiliza) la sesión de clase de una asignación en una fecha.
     */
    public function abrirSesion(
        AsignacionDocenteMateria $asignacion,
        string $fecha,
        ?string $horaInicio = null,
        ?string $tema = null,
        ?User $usuario = null
    ): SesionClase {
        $hora = $horaInicio ?: '00:00:00';

        return SesionClase::firstOrCreate(
            [
                'asignacion_docente_materia_id' => $asignacion->id,
                'fecha' => Carbon::parse($fecha)->toDateString(),
                'hora_inicio' => $hora,
            ],
            [
                'tema' => $tema,
                'estado' => 'dictada',
                'registrado_por' => $usuario?->id,
            ]
        );
    }

    /**
     * Registra la lista completa de una sesión y mantiene sincronizada
     * la tabla histórica de asistencias.
     *
     * @param  array<int, array{estudiante_id: int, estado: string, minutos_tardanza?: ?int, justificacion?: ?string}>  $registros
     */
    public function registrarLista(SesionClase $sesion, array $registros, ?User $usuario = null): array
    {
        if ($registros === []) {
            throw ValidationException::withMessages([
                'registros' => 'No hay estudiantes para registrar.',
            ]);
        }

        $sesion->loadMissing('asignacion');
        $asignacion = $sesion->asignacion;

        if (! $asignacion) {
            throw ValidationException::withMessages([
                'sesion' => 'La sesión no tiene una asignación docente válida.',
            ]);
        }

        $ids = collect($registros)->pluck('estudiante_id')->map(fn ($id) => (int) $id)->unique();

        $permitidos = Estudiante::query()
            ->whereIn('id', $ids)
            ->where('seccion_id', $asignacion->seccion_id)
            ->pluck('id');

        if ($permitidos->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'registros' => 'Hay estudiantes que no pertenecen a la sección de esta sesión.',
            ]);
        }

        $creados = 0;
        $actualizados = 0;

        DB::transaction(function () use ($sesion, $asignacion, $registros, &$creados, &$actualizados) {
            foreach ($registros as $registro) {
                $estado = $registro['estado'];

                if (! in_array($estado, AsistenciaSesion::ESTADOS, true)) {
                    throw ValidationException::withMessages([
                        'registros' => "El estado {$estado} no es válido para la asistencia.",
                    ]);
                }

                $existente = AsistenciaSesion::query()
                    ->where('sesion_clase_id', $sesion->id)
                    ->where('estudiante_id', $registro['estudiante_id'])
                    ->first();

                $atributos = [
                    'estado' => $estado,
                    'minutos_tardanza' => $registro['minutos_tardanza'] ?? null,
                    'justificacion' => $registro['justificacion'] ?? null,
                ];

                if ($existente) {
                    $existente->update($atributos);
                    $actualizados++;
                } else {
                    AsistenciaSesion::create($atributos + [
                        'sesion_clase_id' => $sesion->id,
                        'estudiante_id' => $registro['estudiante_id'],
                    ]);
                    $creados++;
                }

                $this->sincronizarAsistenciaHistorica($sesion, $asignacion, (int) $registro['estudiante_id'], $estado, $registro);
            }

            $sesion->update(['estado' => 'dictada']);
        });

        return [
            'sesion_id' => $sesion->id,
            'creados' => $creados,
            'actualizados' => $actualizados,
        ];
    }

    /**
     * Resumen de la sesión para mostrar el avance del aula.
     */
    public function resumen(SesionClase $sesion): array
    {
        $asistencias = $sesion->asistencias()->get();

        $conteo = [];
        foreach (AsistenciaSesion::ESTADOS as $estado) {
            $conteo[$estado] = $asistencias->where('estado', $estado)->count();
        }

        $total = $asistencias->count();
        $presentes = $conteo['presente'] + $conteo['tarde'] + $conteo['justificado'] + $conteo['permiso'];

        return [
            'total' => $total,
            'presentes' => $presentes,
            'ausentes' => $conteo['ausente'],
            'tardanzas' => $conteo['tarde'],
            'justificados' => $conteo['justificado'] + $conteo['permiso'],
            'porcentaje_asistencia' => $total > 0 ? round(($presentes / $total) * 100, 2) : 0.0,
            'detalle' => $conteo,
        ];
    }

    /**
     * Refleja el registro en la tabla histórica para no romper reportes previos.
     */
    private function sincronizarAsistenciaHistorica(
        SesionClase $sesion,
        AsignacionDocenteMateria $asignacion,
        int $estudianteId,
        string $estado,
        array $registro
    ): void {
        $justificada = in_array($estado, ['justificado', 'permiso'], true);

        Asistencia::updateOrCreate(
            [
                'estudiante_id' => $estudianteId,
                'materia_id' => $asignacion->materia_id,
                'fecha' => $sesion->fecha->toDateString(),
            ],
            [
                'estado' => match ($estado) {
                    'tarde' => 'tarde',
                    'ausente' => 'ausente',
                    'justificado', 'permiso' => 'ausente',
                    default => 'presente',
                },
                'justificada' => $justificada,
                'motivo_justificacion' => $justificada ? ($registro['justificacion'] ?? null) : null,
            ]
        );
    }
}
