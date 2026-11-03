<?php

namespace App\Exports;

use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EstudiantesExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Estudiante::query()
            ->leftJoin('secciones', 'estudiantes.seccion_id', '=', 'secciones.id')
            ->leftJoin('grados', 'secciones.grado_id', '=', 'grados.id')
            ->orderBy('estudiantes.id')
            ->select([
                'estudiantes.id as id',
                'estudiantes.nombres as nombres',
                'estudiantes.apellido_paterno as apellido_paterno',
                'estudiantes.apellido_materno as apellido_materno',
                'estudiantes.dni as dni',
                'estudiantes.estado as estado',
                'secciones.nombre as seccion',
                'grados.nombre as grado',
            ]);
    }

    public function map($fila): array
    {
        return [
            'id' => $fila->id,
            'nombres' => $fila->nombres,
            'apellido_paterno' => $fila->apellido_paterno,
            'apellido_materno' => $fila->apellido_materno,
            'dni' => $fila->dni,
            'seccion' => $fila->seccion,
            'grado' => $fila->grado,
            'estado' => $fila->estado,
        ];
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

    public function chunkSize(): int
    {
        return 1000;
    }
}
