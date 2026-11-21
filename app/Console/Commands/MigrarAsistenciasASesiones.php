<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\AsistenciaSesion;
use App\Models\AsignacionDocenteMateria;
use App\Models\PeriodoAcademico;
use App\Models\SesionClase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarAsistenciasASesiones extends Command
{
    protected $signature = 'asistencias:migrar-sesiones {--anio= : Año escolar a procesar}';

    protected $description = 'Convierte la asistencia histórica por materia y fecha en sesiones de clase';

    public function handle(): int
    {
        $anio = $this->option('anio');
        $sesionesCreadas = 0;
        $registrosCopiados = 0;
        $sinAsignacion = 0;

        $grupos = Asistencia::query()
            ->select('materia_id', 'fecha')
            ->when($anio, fn ($query) => $query->whereYear('fecha', $anio))
            ->groupBy('materia_id', 'fecha')
            ->orderBy('fecha')
            ->get();

        if ($grupos->isEmpty()) {
            $this->info('No hay asistencias históricas para convertir.');

            return self::SUCCESS;
        }

        $periodosPorFecha = PeriodoAcademico::query()
            ->get()
            ->keyBy(fn (PeriodoAcademico $periodo) => $periodo->anio);

        foreach ($grupos as $grupo) {
            $fecha = (string) $grupo->fecha;
            $periodo = $periodosPorFecha->get((int) substr($fecha, 0, 4));

            $secciones = Asistencia::query()
                ->where('materia_id', $grupo->materia_id)
                ->whereDate('fecha', $fecha)
                ->with(['estudiante:id,seccion_id', 'estudiante.seccion:id,grado_id'])
                ->get()
                ->groupBy(fn (Asistencia $asistencia) => $asistencia->estudiante?->seccion_id);

            foreach ($secciones as $seccionId => $asistencias) {
                $asignacion = AsignacionDocenteMateria::query()
                    ->where('materia_id', $grupo->materia_id)
                    ->where('seccion_id', $seccionId)
                    ->when($periodo, fn ($query) => $query->where('periodo_academico_id', $periodo->id))
                    ->first();

                if (! $asignacion) {
                    $sinAsignacion += $asistencias->count();

                    continue;
                }

                DB::transaction(function () use ($asignacion, $fecha, $asistencias, &$sesionesCreadas, &$registrosCopiados) {
                    $sesion = SesionClase::firstOrCreate(
                        [
                            'asignacion_docente_materia_id' => $asignacion->id,
                            'fecha' => $fecha,
                            'hora_inicio' => '00:00:00',
                        ],
                        [
                            'estado' => 'dictada',
                        ]
                    );

                    if ($sesion->wasRecentlyCreated) {
                        $sesionesCreadas++;
                    }

                    foreach ($asistencias as $asistencia) {
                        $estado = match (true) {
                            (bool) $asistencia->justificada => 'justificado',
                            $asistencia->estado === 'tarde' => 'tarde',
                            $asistencia->estado === 'ausente' => 'ausente',
                            default => 'presente',
                        };

                        AsistenciaSesion::updateOrCreate(
                            [
                                'sesion_clase_id' => $sesion->id,
                                'estudiante_id' => $asistencia->estudiante_id,
                            ],
                            [
                                'matricula_id' => $asistencia->matricula_id,
                                'estado' => $estado,
                                'justificacion' => $asistencia->motivo_justificacion,
                            ]
                        );
                        $registrosCopiados++;
                    }
                });
            }
        }

        $this->info("Sesiones creadas: {$sesionesCreadas}");
        $this->info("Registros de asistencia migrados: {$registrosCopiados}");

        if ($sinAsignacion > 0) {
            $this->warn("Registros sin asignación docente equivalente: {$sinAsignacion}");
        }

        return self::SUCCESS;
    }
}
