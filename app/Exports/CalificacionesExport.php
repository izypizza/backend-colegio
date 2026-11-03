<?php

namespace App\Exports;

use App\Models\Calificacion;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CalificacionesExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    private ?int $periodoId;

    public function __construct(?int $periodoId = null)
    {
        $this->periodoId = $periodoId;
    }

    public function query(): Builder
    {
        return Calificacion::query()
            ->leftJoin('estudiantes', 'calificaciones.estudiante_id', '=', 'estudiantes.id')
            ->leftJoin('secciones', 'estudiantes.seccion_id', '=', 'secciones.id')
            ->leftJoin('grados', 'secciones.grado_id', '=', 'grados.id')
            ->leftJoin('materias', 'calificaciones.materia_id', '=', 'materias.id')
            ->leftJoin('periodos_academicos', 'calificaciones.periodo_academico_id', '=', 'periodos_academicos.id')
            ->when($this->periodoId, fn (Builder $query) => $query->where('calificaciones.periodo_academico_id', $this->periodoId))
            ->orderBy('calificaciones.id')
            ->select([
                'calificaciones.id as id',
                'calificaciones.nota as nota',
                'estudiantes.nombres as nombres',
                'estudiantes.apellido_paterno as apellido_paterno',
                'estudiantes.apellido_materno as apellido_materno',
                'estudiantes.dni as dni',
                'grados.nombre as grado',
                'secciones.nombre as seccion',
                'materias.nombre as materia',
                'periodos_academicos.nombre as periodo_nombre',
            ]);
    }

    public function map($fila): array
    {
        $nombreCompleto = trim(implode(' ', array_filter([
            $fila->nombres,
            $fila->apellido_paterno,
            $fila->apellido_materno,
        ])));

        return [
            'id' => $fila->id,
            'estudiante' => $nombreCompleto !== '' ? $nombreCompleto : null,
            'dni' => $fila->dni,
            'grado' => $fila->grado,
            'seccion' => $fila->seccion,
            'materia' => $fila->materia,
            'periodo' => $fila->periodo_nombre,
            'nota' => $fila->nota,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Estudiante',
            'DNI',
            'Grado',
            'Seccion',
            'Materia',
            'Periodo',
            'Nota',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
