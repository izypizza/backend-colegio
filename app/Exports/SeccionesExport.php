<?php

namespace App\Exports;

use App\Models\Seccion;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SeccionesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private ?string $nivel = null,
        private ?string $turno = null
    ) {
    }

    public function query(): Builder
    {
        return Seccion::query()
            ->leftJoin('grados', 'secciones.grado_id', '=', 'grados.id')
            ->withCount('estudiantes')
            ->when($this->nivel, fn (Builder $consulta) => $consulta->where('grados.nivel', $this->nivel))
            ->when($this->turno, fn (Builder $consulta) => $consulta->where('secciones.turno', $this->turno))
            ->orderBy('grados.nivel')
            ->orderBy('grados.nombre')
            ->orderBy('secciones.nombre')
            ->select([
                'secciones.id as id',
                'secciones.nombre as nombre',
                'secciones.turno as turno',
                'secciones.capacidad_maxima as capacidad_maxima',
                'grados.nombre as grado',
                'grados.nivel as nivel',
            ]);
    }

    public function map($fila): array
    {
        $capacidad = (int) ($fila->capacidad_maxima ?? 0);
        $matriculados = (int) $fila->estudiantes_count;
        $vacantes = max($capacidad - $matriculados, 0);
        $ocupacion = $capacidad > 0
            ? round($matriculados / $capacidad * 100, 1)
            : 0;

        return [
            'nivel' => ucfirst((string) $fila->nivel),
            'grado' => $fila->grado,
            'seccion' => $fila->nombre,
            'turno' => $fila->turno ?? '',
            'capacidad' => $capacidad,
            'matriculados' => $matriculados,
            'vacantes' => $vacantes,
            'ocupacion' => $ocupacion.'%',
        ];
    }

    public function headings(): array
    {
        return [
            'Nivel',
            'Grado',
            'Seccion',
            'Turno',
            'Capacidad',
            'Matriculados',
            'Vacantes',
            'Ocupacion',
        ];
    }
}
