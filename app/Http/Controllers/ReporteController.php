<?php

namespace App\Http\Controllers;

use App\Exports\CalificacionesExport;
use App\Exports\EstudiantesExport;
use App\Models\Calificacion;
use App\Models\Estudiante;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facade\Excel;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function estudiantesExcel()
    {
        return Excel::download(new EstudiantesExport(), 'estudiantes.xlsx');
    }

    public function estudiantesPdf()
    {
        $estudiantes = Estudiante::with('seccion.grado')->get();
        $pdf = Pdf::loadView('reportes.estudiantes', compact('estudiantes'));
        return $pdf->download('estudiantes.pdf');
    }

    public function calificacionesExcel(Request $request)
    {
        $periodoId = $request->input('periodo_academico_id');
        return Excel::download(new CalificacionesExport($periodoId), 'calificaciones.xlsx');
    }

    public function calificacionesPdf(Request $request)
    {
        $query = Calificacion::with(['estudiante.seccion.grado', 'materia', 'periodoAcademico']);
        if ($request->filled('periodo_academico_id')) {
            $query->where('periodo_academico_id', $request->input('periodo_academico_id'));
        }
        $calificaciones = $query->get();
        $pdf = Pdf::loadView('reportes.calificaciones', compact('calificaciones'));
        return $pdf->download('calificaciones.pdf');
    }
}
