<?php

namespace App\Http\Controllers;

use App\Exports\CalificacionesExport;
use App\Exports\EstudiantesExport;
use App\Exports\NominaSeccionExport;
use App\Exports\SeccionesExport;
use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\Seccion;
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

    /**
     * Consolidado de secciones con capacidad, matriculados y vacantes.
     */
    public function secciones(Request $request)
    {
        return response()->json($this->datosSecciones($request));
    }

    public function seccionesPdf(Request $request)
    {
        $datos = $this->datosSecciones($request);

        $pdf = Pdf::loadView('reportes.secciones', $datos)->setPaper('a4', 'portrait');

        return $pdf->download('reporte-secciones.pdf');
    }

    public function seccionesExcel(Request $request)
    {
        $archivo = 'reporte-secciones.xlsx';

        return Excel::download(
            new SeccionesExport($request->input('nivel'), $request->input('turno')),
            $archivo
        );
    }

    /**
     * Nómina de estudiantes de una sección.
     */
    public function nominaSeccion(int $seccion_id)
    {
        $datos = $this->datosNomina($seccion_id);

        return response()->json([
            'seccion' => [
                'id' => $datos['seccion']->id,
                'nombre' => $datos['seccion']->nombre,
                'turno' => $datos['seccion']->turno,
                'grado' => $datos['seccion']->grado?->nombre,
                'nivel' => $datos['seccion']->grado?->nivel,
                'capacidad_maxima' => $datos['seccion']->capacidad_maxima,
            ],
            'total' => $datos['estudiantes']->count(),
            'estudiantes' => $datos['estudiantes']->map(fn (Estudiante $estudiante, int $indice) => [
                'numero' => $indice + 1,
                'id' => $estudiante->id,
                'nombre_completo' => $estudiante->nombre_completo,
                'dni' => $estudiante->dni,
                'fecha_nacimiento' => optional($estudiante->fecha_nacimiento)->format('d/m/Y'),
                'estado' => $estudiante->estado,
                'telefono' => $estudiante->telefono,
            ])->values()->all(),
        ]);
    }

    public function nominaSeccionPdf(int $seccion_id)
    {
        $datos = $this->datosNomina($seccion_id);

        $pdf = Pdf::loadView('reportes.nomina-seccion', $datos)->setPaper('a4', 'portrait');

        return $pdf->download('nomina-'.$datos['seccion']->nombre.'.pdf');
    }

    public function nominaSeccionExcel(int $seccion_id)
    {
        $seccion = Seccion::findOrFail($seccion_id);

        return Excel::download(
            new NominaSeccionExport($seccion->id),
            'nomina-'.$seccion->nombre.'.xlsx'
        );
    }

    private function datosNomina(int $seccion_id): array
    {
        $seccion = Seccion::with('grado')->findOrFail($seccion_id);

        $estudiantes = Estudiante::where('seccion_id', $seccion->id)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get();

        return [
            'seccion' => $seccion,
            'estudiantes' => $estudiantes,
            'generado_en' => now()->format('d/m/Y H:i'),
        ];
    }

    private function datosSecciones(Request $request): array
    {
        $nivel = $request->input('nivel');
        $turno = $request->input('turno');
        $buscar = trim((string) $request->input('buscar'));

        $secciones = Seccion::with('grado')
            ->withCount('estudiantes')
            ->when($nivel, fn ($consulta) => $consulta->whereHas('grado', fn ($grado) => $grado->where('nivel', $nivel)))
            ->when($turno, fn ($consulta) => $consulta->where('turno', $turno))
            ->get()
            ->map(function (Seccion $seccion) {
                $capacidad = (int) ($seccion->capacidad_maxima ?? 0);
                $matriculados = (int) $seccion->estudiantes_count;

                return [
                    'id' => $seccion->id,
                    'grado' => $seccion->grado?->nombre,
                    'nivel' => $seccion->grado?->nivel,
                    'nombre' => $seccion->nombre,
                    'turno' => $seccion->turno,
                    'capacidad' => $capacidad,
                    'matriculados' => $matriculados,
                    'vacantes' => max($capacidad - $matriculados, 0),
                    'ocupacion' => $capacidad > 0
                        ? round($matriculados / $capacidad * 100, 1)
                        : 0.0,
                ];
            });

        if ($buscar !== '') {
            $termino = mb_strtolower($buscar);
            $secciones = $secciones->filter(function (array $fila) use ($termino) {
                $texto = mb_strtolower(($fila['grado'] ?? '').' '.$fila['nombre'].' '.($fila['turno'] ?? ''));

                return str_contains($texto, $termino);
            });
        }

        $secciones = $secciones
            ->sortBy(fn (array $fila) => ($fila['nivel'] ?? '').'|'.($fila['grado'] ?? '').'|'.$fila['nombre'])
            ->values();

        $totales = [
            'secciones' => $secciones->count(),
            'capacidad' => (int) $secciones->sum('capacidad'),
            'matriculados' => (int) $secciones->sum('matriculados'),
        ];
        $totales['vacantes'] = max($totales['capacidad'] - $totales['matriculados'], 0);
        $totales['ocupacion'] = $totales['capacidad'] > 0
            ? round($totales['matriculados'] / $totales['capacidad'] * 100, 1)
            : 0.0;

        $niveles = $secciones->groupBy('nivel')->map(function ($grupo, $nombreNivel) {
            $capacidad = (int) $grupo->sum('capacidad');
            $matriculados = (int) $grupo->sum('matriculados');

            return [
                'nivel' => $nombreNivel ?: 'sin nivel',
                'secciones' => $grupo->count(),
                'capacidad' => $capacidad,
                'matriculados' => $matriculados,
                'ocupacion' => $capacidad > 0 ? round($matriculados / $capacidad * 100, 1) : 0.0,
            ];
        })->values()->all();

        $partesFiltro = [];
        if ($nivel) {
            $partesFiltro[] = 'Nivel: '.ucfirst($nivel);
        }
        if ($turno) {
            $partesFiltro[] = 'Turno: '.$turno;
        }
        if ($buscar !== '') {
            $partesFiltro[] = 'Búsqueda: '.$buscar;
        }

        return [
            'generado_en' => now()->format('d/m/Y H:i'),
            'filtros' => $partesFiltro ? implode(' · ', $partesFiltro) : null,
            'totales' => $totales,
            'niveles' => $niveles,
            'secciones' => $secciones->all(),
        ];
    }
}
