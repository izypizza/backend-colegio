<?php

namespace App\Http\Controllers;

use App\Exports\CalificacionesExport;
use App\Exports\EstudiantesExport;
use App\Models\Calificacion;
use App\Models\Estudiante;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
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
        // El PDF masivo agota memoria/tiempo de Dompdf: exigir acotar por período
        $request->validate([
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'seccion_id' => 'nullable|exists:secciones,id',
            'grado_id' => 'nullable|exists:grados,id',
        ], [
            'periodo_academico_id.required' => 'Seleccione un período académico para generar el reporte.',
            'periodo_academico_id.exists' => 'El período académico seleccionado no es válido.',
        ]);

        $query = Calificacion::with(['estudiante.seccion.grado', 'materia', 'periodoAcademico'])
            ->where('periodo_academico_id', $request->input('periodo_academico_id'));

        if ($request->filled('seccion_id')) {
            $query->whereHas('estudiante', function ($estudiantes) use ($request) {
                $estudiantes->where('seccion_id', $request->input('seccion_id'));
            });
        }

        if ($request->filled('grado_id')) {
            $query->whereHas('estudiante.seccion', function ($secciones) use ($request) {
                $secciones->where('grado_id', $request->input('grado_id'));
            });
        }

        // Límite de seguridad: evita el error 500 por agotamiento de recursos en Dompdf
        $limitePdf = 400;
        $totalRegistros = (clone $query)->count();

        if ($totalRegistros > $limitePdf) {
            return response()->json([
                'message' => "El reporte contiene {$totalRegistros} registros y supera el máximo de {$limitePdf} para PDF. "
                    . 'Acote por sección o grado, o use la exportación a Excel.',
                'total' => $totalRegistros,
                'limite' => $limitePdf,
            ], 422);
        }

        $calificaciones = $query->get();
        $pdf = Pdf::loadView('reportes.calificaciones', compact('calificaciones'));
        return $pdf->download('calificaciones.pdf');
    }
}
