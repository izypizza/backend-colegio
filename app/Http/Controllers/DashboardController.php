<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Padre;
use App\Models\Materia;
use App\Models\Seccion;
use App\Models\Grado;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\PrestamoLibro;
use App\Models\Eleccion;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Obtener estadísticas del dashboard según el rol del usuario
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        // Admin y Auxiliar tienen acceso a todas las estadísticas
        if (in_array($role, ['admin', 'auxiliar', 'bibliotecario'])) {
            return $this->getAdminStats();
        }
        // Docente: ver estadísticas de sus materias/secciones
        elseif ($role === 'docente') {
            return $this->getDocenteStats($user);
        }
        // Padre: ver información de sus hijos
        elseif ($role === 'padre') {
            return $this->getPadreStats($user);
        }
        // Estudiante: ver solo su información
        elseif ($role === 'estudiante') {
            return $this->getEstudianteStats($user);
        }

        return response()->json(['error' => 'Rol no reconocido'], 400);
    }

    /**
     * Estadísticas para admin/auxiliar/bibliotecario
     */
    private function getAdminStats()
    {
        $periodoActual = PeriodoAcademico::orderBy('anio', 'desc')->first();
        $hoy = Carbon::today();

        // Estadísticas básicas
        $stats = [
            'estudiantes' => Estudiante::count(),
            'docentes' => Docente::count(),
            'padres' => Padre::count(),
            'materias' => Materia::count(),
            'secciones' => Seccion::count(),
            'grados' => Grado::count(),
        ];

        // Asistencias de hoy
        $asistenciasHoy = Asistencia::whereDate('fecha', $hoy)->count();
        $estudiantesPresentes = Asistencia::whereDate('fecha', $hoy)
            ->where('presente', true)
            ->count();
        
        $stats['asistencias_hoy'] = [
            'total' => $asistenciasHoy,
            'presentes' => $estudiantesPresentes,
            'ausentes' => $asistenciasHoy - $estudiantesPresentes,
            'porcentaje_asistencia' => $asistenciasHoy > 0 
                ? round(($estudiantesPresentes / $asistenciasHoy) * 100, 1) 
                : 0
        ];

        // Calificaciones por periodo actual
        if ($periodoActual) {
            $promedioGeneral = Calificacion::where('periodo_academico_id', $periodoActual->id)
                ->avg('nota');
            
            $stats['calificaciones'] = [
                'promedio_general' => $promedioGeneral ? round($promedioGeneral, 2) : 0,
                'total_calificaciones' => Calificacion::where('periodo_academico_id', $periodoActual->id)->count(),
                'aprobados' => Calificacion::where('periodo_academico_id', $periodoActual->id)
                    ->where('nota', '>=', 11)->count(),
                'desaprobados' => Calificacion::where('periodo_academico_id', $periodoActual->id)
                    ->where('nota', '<', 11)->count(),
            ];
        }

        // Biblioteca
        $fechaLimite = Carbon::today()->subDays(15); // Préstamos de hace más de 15 días
        $stats['biblioteca'] = [
            'prestamos_activos' => PrestamoLibro::whereNull('fecha_devolucion')->count(),
            'prestamos_vencidos' => PrestamoLibro::whereNull('fecha_devolucion')
                ->whereDate('fecha_prestamo', '<', $fechaLimite)
                ->count(),
            'total_prestamos_mes' => PrestamoLibro::whereMonth('fecha_prestamo', $hoy->month)
                ->whereYear('fecha_prestamo', $hoy->year)
                ->count(),
        ];

        // Elecciones activas
        $stats['elecciones'] = [
            'activas' => Eleccion::where('estado', 'activa')->count(),
            'proximas' => Eleccion::where('estado', 'pendiente')
                ->whereDate('fecha_inicio', '>', $hoy)
                ->count(),
        ];

        // Distribución por grado
        $stats['distribucion_grados'] = Grado::withCount('secciones', 'estudiantes')
            ->orderBy('nombre')
            ->get()
            ->map(function($grado) {
                return [
                    'grado' => $grado->nombre,
                    'secciones' => $grado->secciones_count,
                    'estudiantes' => $grado->estudiantes_count ?? 0
                ];
            });

        // Actividades recientes
        $stats['actividad_reciente'] = $this->getActividadReciente();

        return response()->json($stats);
    }

    /**
     * Estadísticas para docentes
     */
    private function getDocenteStats($user)
    {
        $docente = Docente::where('user_id', $user->id)->with(['asignaciones.materia', 'asignaciones.seccion.grado'])->first();
        
        if (!$docente) {
            return response()->json(['error' => 'Docente no encontrado'], 404);
        }

        $periodoActual = PeriodoAcademico::orderBy('anio', 'desc')->first();
        $hoy = Carbon::today();

        $seccionesIds = $docente->asignaciones->pluck('seccion_id')->unique();
        $materiasIds = $docente->asignaciones->pluck('materia_id')->unique();

        $stats = [
            'mis_clases' => $docente->asignaciones->count(),
            'mis_estudiantes' => Estudiante::whereIn('seccion_id', $seccionesIds)->count(),
            'mis_materias' => $materiasIds->count(),
            'mis_secciones' => $seccionesIds->count(),
        ];

        // Clases detalladas
        $stats['clases_detalle'] = $docente->asignaciones->map(function($asignacion) {
            return [
                'materia' => $asignacion->materia->nombre,
                'seccion' => $asignacion->seccion->nombre,
                'grado' => $asignacion->seccion->grado->nombre,
                'estudiantes' => $asignacion->seccion->estudiantes_count ?? 0
            ];
        });

        // Asistencias registradas hoy en las materias del docente
        $asistenciasHoy = Asistencia::whereDate('fecha', $hoy)
            ->whereIn('materia_id', $materiasIds)
            ->whereHas('estudiante', function($q) use ($seccionesIds) {
                $q->whereIn('seccion_id', $seccionesIds);
            })
            ->count();

        $stats['asistencias_hoy'] = $asistenciasHoy;

        // Calificaciones pendientes (estudiantes sin nota en el periodo actual)
        if ($periodoActual) {
            $totalEstudiantes = Estudiante::whereIn('seccion_id', $seccionesIds)->count();
            $calificacionesRegistradas = Calificacion::where('periodo_academico_id', $periodoActual->id)
                ->whereIn('materia_id', $materiasIds)
                ->whereHas('estudiante', function($q) use ($seccionesIds) {
                    $q->whereIn('seccion_id', $seccionesIds);
                })
                ->count();

            $stats['calificaciones'] = [
                'registradas' => $calificacionesRegistradas,
                'pendientes' => ($totalEstudiantes * $materiasIds->count()) - $calificacionesRegistradas,
            ];
        }

        // Próximas tareas
        $stats['tareas_pendientes'] = [
            [
                'tipo' => 'Asistencias',
                'descripcion' => 'Registrar asistencias diarias',
                'prioridad' => $asistenciasHoy === 0 ? 'alta' : 'normal'
            ],
            [
                'tipo' => 'Calificaciones',
                'descripcion' => 'Registrar calificaciones del periodo',
                'prioridad' => isset($stats['calificaciones']) && $stats['calificaciones']['pendientes'] > 10 ? 'alta' : 'normal'
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Estadísticas para padres
     */
    private function getPadreStats($user)
    {
        $padre = Padre::where('user_id', $user->id)->with('estudiantes.seccion.grado')->first();
        
        if (!$padre) {
            return response()->json(['error' => 'Padre no encontrado'], 404);
        }

        $hijos = $padre->estudiantes;
        $periodoActual = PeriodoAcademico::orderBy('anio', 'desc')->first();
        $hoy = Carbon::today();

        $stats = [
            'mis_hijos' => $hijos->count(),
            'hijos_detalle' => []
        ];

        foreach ($hijos as $hijo) {
            $datosHijo = [
                'id' => $hijo->id,
                'nombre' => "{$hijo->nombres} {$hijo->apellido_paterno} {$hijo->apellido_materno}",
                'seccion' => $hijo->seccion->nombre,
                'grado' => $hijo->seccion->grado->nombre,
            ];

            // Asistencia del mes actual
            $asistenciasMes = Asistencia::where('estudiante_id', $hijo->id)
                ->whereMonth('fecha', $hoy->month)
                ->whereYear('fecha', $hoy->year)
                ->get();

            $datosHijo['asistencia'] = [
                'total' => $asistenciasMes->count(),
                'presentes' => $asistenciasMes->where('presente', true)->count(),
                'tardanzas' => 0, // No hay distinción de tardanzas en la tabla actual
                'faltas' => $asistenciasMes->where('presente', false)->count(),
            ];

            // Promedio de calificaciones
            if ($periodoActual) {
                $promedio = Calificacion::where('estudiante_id', $hijo->id)
                    ->where('periodo_academico_id', $periodoActual->id)
                    ->avg('nota');

                $datosHijo['promedio'] = $promedio ? round($promedio, 2) : 0;
            }

            $stats['hijos_detalle'][] = $datosHijo;
        }

        // Resumen general
        if ($periodoActual && $hijos->count() > 0) {
            $promedioGeneral = Calificacion::whereIn('estudiante_id', $hijos->pluck('id'))
                ->where('periodo_academico_id', $periodoActual->id)
                ->avg('nota');

            $stats['resumen'] = [
                'promedio_general' => $promedioGeneral ? round($promedioGeneral, 2) : 0,
                'periodo_actual' => $periodoActual->nombre,
            ];
        }

        // Alertas importantes
        $stats['alertas'] = [];
        foreach ($hijos as $hijo) {
            $asistenciasMes = Asistencia::where('estudiante_id', $hijo->id)
                ->whereMonth('fecha', $hoy->month)
                ->get();
            
            $faltas = $asistenciasMes->where('presente', false)->count();
            if ($faltas >= 3) {
                $stats['alertas'][] = [
                    'tipo' => 'asistencia',
                    'estudiante' => "{$hijo->nombres} {$hijo->apellido_paterno}",
                    'mensaje' => "Ha faltado $faltas veces este mes",
                    'severidad' => $faltas >= 5 ? 'alta' : 'media'
                ];
            }
        }

        return response()->json($stats);
    }

    /**
     * Estadísticas para estudiantes
     */
    private function getEstudianteStats($user)
    {
        $estudiante = Estudiante::where('user_id', $user->id)
            ->with(['seccion.grado', 'padres'])
            ->first();
        
        if (!$estudiante) {
            return response()->json(['error' => 'Estudiante no encontrado'], 404);
        }

        $periodoActual = PeriodoAcademico::orderBy('anio', 'desc')->first();
        $hoy = Carbon::today();

        $stats = [
            'info_personal' => [
                'nombre_completo' => "{$estudiante->nombres} {$estudiante->apellido_paterno} {$estudiante->apellido_materno}",
                'seccion' => $estudiante->seccion->nombre,
                'grado' => $estudiante->seccion->grado->nombre,
                'edad' => Carbon::parse($estudiante->fecha_nacimiento)->age,
            ]
        ];

        // Asistencias del mes
        $asistenciasMes = Asistencia::where('estudiante_id', $estudiante->id)
            ->whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->get();

        $stats['asistencia_mes'] = [
            'total' => $asistenciasMes->count(),
            'presentes' => $asistenciasMes->where('presente', true)->count(),
            'tardanzas' => 0, // No hay distinción de tardanzas en la tabla actual
            'faltas' => $asistenciasMes->where('presente', false)->count(),
            'porcentaje' => $asistenciasMes->count() > 0 
                ? round(($asistenciasMes->where('presente', true)->count() / $asistenciasMes->count()) * 100, 1)
                : 0
        ];

        // Calificaciones del periodo actual
        if ($periodoActual) {
            $calificaciones = Calificacion::where('estudiante_id', $estudiante->id)
                ->where('periodo_academico_id', $periodoActual->id)
                ->with('materia')
                ->get();

            $promedio = $calificaciones->avg('nota');

            $stats['calificaciones'] = [
                'promedio' => $promedio ? round($promedio, 2) : 0,
                'total_cursos' => $calificaciones->count(),
                'aprobados' => $calificaciones->where('nota', '>=', 11)->count(),
                'desaprobados' => $calificaciones->where('nota', '<', 11)->count(),
                'mejor_nota' => $calificaciones->max('nota') ?? 0,
                'curso_mejor' => $calificaciones->sortByDesc('nota')->first()?->materia->nombre ?? 'N/A',
            ];

            // Detalles por curso
            $stats['calificaciones_detalle'] = $calificaciones->map(function($cal) {
                return [
                    'materia' => $cal->materia->nombre,
                    'nota' => $cal->nota,
                    'estado' => $cal->nota >= 11 ? 'Aprobado' : 'Desaprobado'
                ];
            })->sortByDesc('nota')->values();
        }

        // Recordatorios
        $stats['recordatorios'] = [
            [
                'titulo' => 'Asistencia',
                'mensaje' => $asistenciasMes->where('presente', false)->count() >= 3 
                    ? '⚠️ Tienes varias faltas este mes. Intenta no faltar más.'
                    : '✅ Tu asistencia está bien. ¡Sigue así!',
                'tipo' => $asistenciasMes->where('presente', false)->count() >= 3 ? 'warning' : 'success'
            ],
        ];

        if ($periodoActual && isset($stats['calificaciones'])) {
            $promedio = $stats['calificaciones']['promedio'];
            $stats['recordatorios'][] = [
                'titulo' => 'Promedio',
                'mensaje' => $promedio >= 14 
                    ? '🌟 ¡Excelente promedio! Continúa con tu esfuerzo.'
                    : ($promedio >= 11 
                        ? '👍 Promedio aprobatorio. Puedes mejorar más.'
                        : '⚠️ Tu promedio está bajo. Ponte al día con tus estudios.'),
                'tipo' => $promedio >= 14 ? 'success' : ($promedio >= 11 ? 'info' : 'warning')
            ];
        }

        return response()->json($stats);
    }

    /**
     * Obtener actividad reciente del sistema
     */
    private function getActividadReciente()
    {
        $actividades = [];
        $hoy = Carbon::today();

        // Últimas calificaciones
        $ultimasCalificaciones = Calificacion::with(['estudiante', 'materia'])
            ->latest()
            ->take(3)
            ->get();

        foreach ($ultimasCalificaciones as $cal) {
            $actividades[] = [
                'tipo' => 'calificacion',
                'titulo' => 'Nueva calificación registrada',
                'descripcion' => "{$cal->materia->nombre} - {$cal->estudiante->nombres} {$cal->estudiante->apellido_paterno}",
                'fecha' => $cal->created_at->diffForHumans(),
                'icono' => 'clipboard',
                'color' => 'blue'
            ];
        }

        // Últimas asistencias
        $ultimasAsistencias = Asistencia::with('estudiante')
            ->latest()
            ->take(2)
            ->get();

        foreach ($ultimasAsistencias as $asist) {
            $actividades[] = [
                'tipo' => 'asistencia',
                'titulo' => 'Asistencia registrada',
                'descripcion' => "{$asist->estudiante->nombres} {$asist->estudiante->apellido_paterno} - " . ($asist->presente ? 'Presente' : 'Ausente'),
                'fecha' => $asist->created_at->diffForHumans(),
                'icono' => 'check',
                'color' => $asist->presente ? 'green' : 'red'
            ];
        }

        // Ordenar por fecha
        usort($actividades, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        return array_slice($actividades, 0, 5);
    }
}
