<?php

namespace App\Exports;

use App\Models\Estudiante;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EstudiantesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Estudiante::with('seccion.grado')->get()->map(function ($e) {
            return [
                'id' => $e->id,
                'nombres' => $e->nombres,
                'apellido_paterno' => $e->apellido_paterno,
                'apellido_materno' => $e->apellido_materno,
                'dni' => $e->dni,
                'seccion' => $e->seccion?->nombre,
                'grado' => $e->seccion?->grado?->nombre,
                'estado' => $e->estado,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombres',
            'Apellido Paterno',
            'Apellido Materno',
            'DNI',
            'Seccion',
            'Grado',
            'Estado',
        ];
    }
}
