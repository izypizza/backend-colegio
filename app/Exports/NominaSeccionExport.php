<?php

namespace App\Exports;

use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NominaSeccionExport implements FromQuery, WithHeadings, WithMapping
{
    private int $numero = 0;

    public function __construct(private int $seccionId)
    {
    }

    public function query(): Builder
    {
        return Estudiante::query()
            ->where('seccion_id', $this->seccionId)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres');
    }

    public function map($estudiante): array
    {
        $this->numero++;

        return [
            'numero' => $this->numero,
            'estudiante' => $estudiante->nombre_completo,
            'dni' => $estudiante->dni,
            'fecha_nacimiento' => optional($estudiante->fecha_nacimiento)->format('d/m/Y'),
            'estado' => $estudiante->estado,
            'telefono' => $estudiante->telefono,
        ];
    }

    public function headings(): array
    {
        return [
            'N',
            'Apellidos y nombres',
            'DNI',
            'Fecha de nacimiento',
            'Estado',
            'Telefono',
        ];
    }
}
