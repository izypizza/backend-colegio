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

        $periodosPorAnio = PeriodoAcademico::query()->get()->keyBy('anio');

        $asignaciones = AsignacionDocenteMateria::query()
            ->get(['id', 'materia_id', 'seccion_id', 'periodo_academico_id'])
            ->keyBy(fn ($asignacion) => $asignacion->materia_id . '|' . $asignacion->seccion_id . '|' . $asignacion->periodo_academico_id);

        $sesiones = [];
        $registros = [];
        $sinAsignacion = 0;
        $total = 0;

        Asistencia::query()
            ->with(['estudiante:id,seccion_id'])
            ->select('id', 'estudiante_id', 'matricula_id', 'materia_id', 'fecha', 'estado', 'justificada', 'motivo_justificacion')
            ->when($anio, fn ($query) => $query->whereYear('fecha', $anio))
            ->chunkById(2000, function ($asistencias) use (&$sesiones, &$registros, &$sinAsignacion, &$total, $periodosPorAnio, $asignaciones) {
                foreach ($asistencias as $asistencia) {
                    $total++;
                    $fecha = \Carbon\Carbon::parse($asistencia->fecha)->toDateString();
                    $periodo = $periodosPorAnio->get((int) substr($fecha, 0, 4));
                    $seccionId = $asistencia->estudiante?->seccion_id;

                    if (! $seccionId || ! $periodo) {
                        $sinAsignacion++;

                        continue;
                    }

                    $asignacion = $asignaciones->get($asistencia->materia_id . '|' . $seccionId . '|' . $periodo->id);

                    if (! $asignacion) {
                        $sinAsignacion++;

                        continue;
                    }

                    $clave = $asignacion->id . '|' . $fecha;

                    $sesiones[$clave] = [
                        'asignacion_docente_materia_id' => $asignacion->id,
                        'fecha' => $fecha,
                        'hora_inicio' => '00:00:00',
                        'estado' => 'dictada',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $estado = $asistencia->justificada ? 'justificado' : match ($asistencia->estado) {
                        'tarde' => 'tarde',
                        'ausente' => 'ausente',
                        default => 'presente',
                    };

                    $registros[] = [
                        'clave' => $clave,
                        'estudiante_id' => $asistencia->estudiante_id,
                        'matricula_id' => $asistencia->matricula_id,
                        'estado' => $estado,
                        'justificacion' => $asistencia->motivo_justificacion,
                    ];
                }
            }, 'id');

        if ($total === 0) {
            $this->info('No hay asistencias históricas para convertir.');

            return self::SUCCESS;
        }

        $sesionesInsertadas = 0;

        foreach (array_chunk(array_values($sesiones), 500) as $lote) {
            $sesionesInsertadas += SesionClase::query()->insertOrIgnore($lote);
        }

        $mapaSesiones = SesionClase::query()
            ->where('hora_inicio', '00:00:00')
            ->whereIn('asignacion_docente_materia_id', array_values(array_unique(array_map(
                fn ($clave) => (int) explode('|', $clave)[0],
                array_keys($sesiones)
            ))))
            ->get(['id', 'asignacion_docente_materia_id', 'fecha'])
            ->keyBy(fn ($sesion) => $sesion->asignacion_docente_materia_id . '|' . \Carbon\Carbon::parse($sesion->fecha)->toDateString());

        $filas = [];

        foreach ($registros as $registro) {
            $sesionId = $mapaSesiones->get($registro['clave'])?->id;

            if (! $sesionId) {
                continue;
            }

            $filas[] = [
                'sesion_clase_id' => $sesionId,
                'estudiante_id' => $registro['estudiante_id'],
                'matricula_id' => $registro['matricula_id'],
                'estado' => $registro['estado'],
                'justificacion' => $registro['justificacion'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $registrosInsertados = 0;

        foreach (array_chunk($filas, 1000) as $lote) {
            $registrosInsertados += AsistenciaSesion::query()->insertOrIgnore($lote);
        }

        $this->info("Sesiones creadas: {$sesionesInsertadas}");
        $this->info("Registros de asistencia migrados: {$registrosInsertados}");

        if ($sinAsignacion > 0) {
            $this->warn("Registros sin asignación docente equivalente: {$sinAsignacion}");
        }

        return self::SUCCESS;
    }
}
