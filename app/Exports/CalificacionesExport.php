<?php

namespace App\Exports;

use App\Models\Calificacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CalificacionesExport implements FromCollection, WithHeadings
{
    private ?int $periodoId;

    public function __construct(?int $periodoId = null)
    {
        $this->periodoId = $periodoId;
    }

    public function collection()
    {
        $query = Calificacion::with(['estudiante.seccion.grado', 'materia', 'periodoAcademico']);
        if ($this->periodoId) {
            $query->where('periodo_academico_id', $this->periodoId);
        }

        return $query->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'estudiante' => $c->estudiante?->nombre_completo,
                'dni' => $c->estudiante?->dni,
                'grado' => $c->estudiante?->seccion?->grado?->nombre,
                'seccion' => $c->estudiante?->seccion?->nombre,
                'materia' => $c->materia?->nombre,
                'periodo' => $c->periodoAcademico?->nombre,
                'nota' => $c->nota,
            ];
        });
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
}
