<?php

namespace App\Console\Commands;

use App\Models\AsistenciaSesion;
use App\Models\CalificacionCompetencia;
use App\Models\Competencia;
use App\Models\SesionClase;
use Illuminate\Console\Command;

class InstalarSistema extends Command
{
    protected $signature = 'sistema:instalar
        {--anio= : Año escolar para convertir los datos históricos}
        {--fresh : Recrea la base de datos y carga los datos de ejemplo}
        {--sin-historicos : No convierte calificaciones ni asistencias anteriores}';

    protected $description = 'Deja el sistema listo en un solo paso: migra, carga competencias y convierte el histórico';

    public function handle(): int
    {
        $anio = $this->option('anio') ?: (string) now()->year;

        if ($this->option('fresh')) {
            if (! $this->option('no-interaction') && ! $this->confirm('Esto eliminará TODOS los datos actuales. ¿Continuar?', false)) {
                $this->warn('Operación cancelada.');

                return self::FAILURE;
            }

            $this->info('1/2 Recreando la base de datos y cargando datos de ejemplo...');
            $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $this->info('2/2 Datos de ejemplo cargados.');
        } else {
            $this->info('1/4 Aplicando migraciones pendientes...');
            $this->call('migrate', ['--force' => true]);

            $this->info('2/4 Cargando el catálogo de competencias...');
            $this->call('db:seed', ['--class' => 'CompetenciaSeeder', '--force' => true]);

            if ($this->option('sin-historicos')) {
                $this->line('Conversión de datos históricos omitida.');
            } else {
                $this->info("3/4 Convirtiendo calificaciones a competencias (año {$anio})...");
                $this->call('competencias:derivar', ['--anio' => $anio]);

                $this->info("4/4 Convirtiendo asistencias en sesiones (año {$anio})...");
                $this->call('asistencias:migrar-sesiones', ['--anio' => $anio]);
            }
        }

        $this->newLine();
        $this->info('Sistema listo.');

        $this->table(['Dato', 'Cantidad'], [
            ['Competencias', Competencia::count()],
            ['Calificaciones por competencia', CalificacionCompetencia::count()],
            ['Sesiones de clase', SesionClase::count()],
            ['Asistencias por sesión', AsistenciaSesion::count()],
        ]);

        return self::SUCCESS;
    }
}
