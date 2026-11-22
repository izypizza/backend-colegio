<?php

namespace App\Console\Commands;

use App\Helpers\CnebHelper;
use App\Models\Asistencia;
use App\Models\AsistenciaSesion;
use App\Models\AsignacionDocenteMateria;
use App\Models\Calificacion;
use App\Models\CalificacionCompetencia;
use App\Models\Competencia;
use App\Models\Estudiante;
use App\Models\SesionClase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DerivarCompetenciasDesdeCalificaciones extends Command
{
    protected $signature = 'competencias:derivar
        {--anio= : Año escolar a procesar}
        {--eliminar : Reemplaza las calificaciones por competencia existentes}';

    protected $description = 'Genera un nivel de logro por competencia a partir de las calificaciones de área existentes';

    public function handle(): int
    {
        $anio = $this->option('anio');

        if ($this->option('eliminar')) {
            $eliminadas = CalificacionCompetencia::query()->delete();
            $this->warn("Se eliminaron {$eliminadas} calificaciones de competencia previas.");
        }

        $competenciasPorAreaCiclo = Competencia::query()
            ->activas()
            ->get()
            ->groupBy(fn (Competencia $competencia) => $competencia->materia_id . '-' . $competencia->ciclo);

        if ($competenciasPorAreaCiclo->isEmpty()) {
            $this->error('No hay competencias registradas. Ejecuta el CompetenciaSeeder primero.');

            return self::FAILURE;
        }

        $insertados = 0;
        $omitidos = 0;
        $lote = [];
        $ahora = now();

        $vaciarLote = function () use (&$lote, &$insertados) {
            if ($lote === []) {
                return;
            }

            // insertOrIgnore evita sobrescribir notas registradas manualmente
            // y convierte decenas de miles de consultas en unas pocas.
            $insertados += CalificacionCompetencia::query()->insertOrIgnore($lote);
            $lote = [];
        };

        Calificacion::query()
            ->with([
                'estudiante:id,seccion_id',
                'estudiante.seccion:id,grado_id',
                'estudiante.seccion.grado:id,nombre,nivel,ciclo',
            ])
            ->whereNotNull('nota')
            ->when($anio, fn ($query) => $query->whereHas('periodoAcademico', fn ($periodo) => $periodo->where('anio', $anio)))
            ->chunkById(1000, function ($calificaciones) use ($competenciasPorAreaCiclo, &$omitidos, &$lote, $ahora) {
                foreach ($calificaciones as $calificacion) {
                    $grado = $calificacion->estudiante?->seccion?->grado;
                    $ciclo = $grado?->ciclo ?: \App\Models\Grado::calcularCiclo($grado?->nombre, $grado?->nivel);
                    $competencias = $competenciasPorAreaCiclo->get($calificacion->materia_id . '-' . $ciclo);

                    if (! $competencias) {
                        $omitidos++;

                        continue;
                    }

                    $nivel = CnebHelper::nivelDesdeNota((float) $calificacion->nota);

                    foreach ($competencias as $competencia) {
                        $lote[] = [
                            'estudiante_id' => $calificacion->estudiante_id,
                            'competencia_id' => $competencia->id,
                            'periodo_academico_id' => $calificacion->periodo_academico_id,
                            'matricula_id' => $calificacion->matricula_id,
                            'escala' => 'numerica',
                            'nivel' => $nivel,
                            'nota' => $calificacion->nota,
                            'calculada_desde_actividades' => false,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ];
                    }
                }
            }, 'id');

        $vaciarLote();

        $this->info("Calificaciones por competencia insertadas: {$insertados}");
        $this->line("Calificaciones sin competencias aplicables: {$omitidos}");

        return self::SUCCESS;
    }
}
