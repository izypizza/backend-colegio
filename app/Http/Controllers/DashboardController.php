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
        $periodoActual = PeriodoAcademico::where('estado', 'activo')->first();
        $hoy = Carbon::today();

        // Usar queries optimizadas con selectRaw para reducir carga
        $stats = [
            'estudiantes' => DB::table('estudiantes')->count(),
            'docentes' => DB::table('docentes')->count(),
            'padres' => DB::table('padres')->count(),
            'materias' => DB::table('materias')->count(),
            'secciones' => DB::table('secciones')->count(),
            'grados' => DB::table('grados')->count(),
        ];

        // Asistencias de hoy con query optimizada
        $asistenciasHoyStats = DB::table('asistencias')
            ->whereDate('fecha', $hoy)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN estado = "presente" THEN 1 ELSE 0 END) as presentes,
                SUM(CASE WHEN estado = "tarde" THEN 1 ELSE 0 END) as tardes,
                SUM(CASE WHEN estado = "ausente" THEN 1 ELSE 0 END) as ausentes
            ')
            ->first();
        
        $totalAsistencias = $asistenciasHoyStats->total ?? 0;
        $presentes = $asistenciasHoyStats->presentes ?? 0;
        $tardes = $asistenciasHoyStats->tardes ?? 0;
        
        $stats['asistencias_hoy'] = [
            'total' => $totalAsistencias,
            'presentes' => $presentes,
            'tardes' => $tardes,
            'ausentes' => $asistenciasHoyStats->ausentes ?? 0,
            'porcentaje_asistencia' => $totalAsistencias > 0 
                ? round((($presentes + $tardes) / $totalAsistencias) * 100, 1) 
                : 0
        ];

        // Calificaciones por periodo actual - OPTIMIZADO
        if ($periodoActual) {
            $calificacionesStats = DB::table('calificaciones')
                ->where('periodo_academico_id', $periodoActual->id)
                ->selectRaw('
                    COUNT(*) as total,
                    ROUND(AVG(nota), 2) as promedio,
                    SUM(CASE WHEN nota >= 11 THEN 1 ELSE 0 END) as aprobados,
                    SUM(CASE WHEN nota < 11 THEN 1 ELSE 0 END) as desaprobados
                ')
                ->first();
            
            $stats['calificaciones'] = [
                'promedio_general' => $calificacionesStats->promedio ?? 0,
                'total_calificaciones' => $calificacionesStats->total ?? 0,
                'aprobados' => $calificacionesStats->aprobados ?? 0,
                'desaprobados' => $calificacionesStats->desaprobados ?? 0,
            ];
        }

        // Biblioteca - OPTIMIZADO
        $fechaLimite = Carbon::today()->subDays(15);
        $bibliotecaStats = DB::table('prestamos_libros')
            ->selectRaw('
                SUM(CASE WHEN fecha_devolucion IS NULL THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN fecha_devolucion IS NULL AND fecha_prestamo < ? THEN 1 ELSE 0 END) as vencidos,
                SUM(CASE WHEN MONTH(fecha_prestamo) = ? AND YEAR(fecha_prestamo) = ? THEN 1 ELSE 0 END) as mes_actual
            ', [$fechaLimite, $hoy->month, $hoy->year])
            ->first();
        
        $stats['biblioteca'] = [
            'prestamos_activos' => $bibliotecaStats->activos ?? 0,
            'prestamos_vencidos' => $bibliotecaStats->vencidos ?? 0,
            'total_prestamos_mes' => $bibliotecaStats->mes_actual ?? 0,
        ];

        // Elecciones activas - OPTIMIZADO
        $stats['elecciones'] = [
            'activas' => DB::table('elecciones')->where('estado', 'activa')->count(),
            'proximas' => DB::table('elecciones')
                ->where('estado', 'pendiente')
                ->whereDate('fecha_inicio', '>', $hoy)
                ->count(),
        ];

        // Distribución por grado - OPTIMIZADO con joins
        $stats['distribucion_grados'] = DB::table('grados')
            ->leftJoin('secciones', 'grados.id', '=', 'secciones.grado_id')
            ->leftJoin('estudiantes', 'secciones.id', '=', 'estudiantes.seccion_id')
            ->select('grados.nombre as grado')
            ->selectRaw('COUNT(DISTINCT secciones.id) as secciones')
            ->selectRaw('COUNT(DISTINCT estudiantes.id) as estudiantes')
            ->groupBy('grados.id', 'grados.nombre')
            ->orderBy('grados.nombre')
            ->get();

        return response()->json($stats);
    }

    /**
     * Estadísticas para docentes
     */
    private function getDocenteStats($user)
    {
        $docente = Docente::where('user_id', $user->id)->first();
        
        if (!$docente) {
            return response()->json(['error' => 'Docente no encontrado'], 404);
        }

        $periodoActual = PeriodoAcademico::where('estado', 'activo')->first();
        $hoy = Carbon::today();

        // Obtener IDs de secciones y materias de manera eficiente
        $asignaciones = DB::table('asignacion_docente_materia')
            ->where('docente_id', $docente->id)
            ->when($periodoActual, function($q) use ($periodoActual) {
                return $q->where('periodo_academico_id', $periodoActual->id);
            })
            ->get();

        $seccionesIds = $asignaciones->pluck('seccion_id')->unique();
        $materiasIds = $asignaciones->pluck('materia_id')->unique();

        $stats = [
            'mis_clases' => $asignaciones->count(),
            'mis_estudiantes' => DB::table('estudiantes')
                ->whereIn('seccion_id', $seccionesIds)
                ->count(),
            'mis_materias' => $materiasIds->count(),
            'mis_secciones' => $seccionesIds->count(),
        ];

        // Clases detalladas - OPTIMIZADO con joins
        $stats['clases_detalle'] = DB::table('asignacion_docente_materia as adm')
            ->join('materias as m', 'adm.materia_id', '=', 'm.id')
            ->join('secciones as s', 'adm.seccion_id', '=', 's.id')
            ->join('grados as g', 's.grado_id', '=', 'g.id')
            ->leftJoin('estudiantes as e', 's.id', '=', 'e.seccion_id')
            ->where('adm.docente_id', $docente->id)
            ->when($periodoActual, function($q) use ($periodoActual) {
                return $q->where('adm.periodo_academico_id', $periodoActual->id);
            })
            ->select('m.nombre as materia', 's.nombre as seccion', 'g.nombre as grado')
            ->selectRaw('COUNT(DISTINCT e.id) as estudiantes')
            ->groupBy('m.id', 'm.nombre', 's.id', 's.nombre', 'g.nombre')
            ->get();

        // Asistencias registradas hoy
        $stats['asistencias_hoy'] = DB::table('asistencias')
            ->whereDate('fecha', $hoy)
            ->whereIn('materia_id', $materiasIds)
            ->whereIn('estudiante_id', function($query) use ($seccionesIds) {
                $query->select('id')
                    ->from('estudiantes')
                    ->whereIn('seccion_id', $seccionesIds);
            })
            ->count();

        // Calificaciones pendientes
        if ($periodoActual && $materiasIds->count() > 0 && $seccionesIds->count() > 0) {
            $totalEstudiantes = DB::table('estudiantes')
                ->whereIn('seccion_id', $seccionesIds)
                ->count();
                
            $calificacionesRegistradas = DB::table('calificaciones')
                ->where('periodo_academico_id', $periodoActual->id)
                ->whereIn('materia_id', $materiasIds)
                ->whereIn('estudiante_id', function($query) use ($seccionesIds) {
                    $query->select('id')
                        ->from('estudiantes')
                        ->whereIn('seccion_id', $seccionesIds);
                })
                ->count();

            $stats['calificaciones'] = [
                'registradas' => $calificacionesRegistradas,
                'pendientes' => max(0, ($totalEstudiantes * $materiasIds->count()) - $calificacionesRegistradas),
            ];
        }

        // Próximas tareas
        $stats['tareas_pendientes'] = [
            [
                'tipo' => 'Asistencias',
                'descripcion' => 'Registrar asistencias diarias',
                'prioridad' => ($stats['asistencias_hoy'] ?? 0) === 0 ? 'alta' : 'normal'
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
                'presentes' => $asistenciasMes->where('estado', 'presente')->count(),
                'tardanzas' => $asistenciasMes->where('estado', 'tarde')->count(),
                'faltas' => $asistenciasMes->where('estado', 'ausente')->count(),
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
            
            $faltas = $asistenciasMes->where('estado', 'ausente')->count();
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
            'presentes' => $asistenciasMes->where('estado', 'presente')->count(),
            'tardanzas' => $asistenciasMes->where('estado', 'tarde')->count(),
            'faltas' => $asistenciasMes->where('estado', 'ausente')->count(),
            'porcentaje' => $asistenciasMes->count() > 0 
                ? round(($asistenciasMes->where('estado', 'presente')->count() / $asistenciasMes->count()) * 100, 1)
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

        // Recordatorios con frases motivacionales variadas
        $stats['recordatorios'] = [];
        
        // Mensaje de asistencia
        $faltas = $asistenciasMes->where('estado', 'ausente')->count();
        if ($faltas >= 5) {
            $mensajes = [
                'Has faltado muchas veces este mes. Tu presencia es importante, ¡esperamos verte en clases!',
                'Tus faltas son preocupantes. Recuerda que cada día cuenta para tu aprendizaje.',
                'Has acumulado varias inasistencias. No dejes que se conviertan en obstáculo para tu éxito.',
            ];
            $stats['recordatorios'][] = [
                'titulo' => 'Asistencia - Necesita Atención',
                'mensaje' => $mensajes[array_rand($mensajes)],
                'tipo' => 'warning'
            ];
        } elseif ($faltas >= 3) {
            $mensajes = [
                'Tienes algunas faltas este mes. Intenta no faltar más, ¡te necesitamos en clase!',
                'Cuidado con las inasistencias. Cada clase es una oportunidad de aprender algo nuevo.',
                'Has faltado un par de veces. Recuerda que la constancia es clave para el éxito.',
            ];
            $stats['recordatorios'][] = [
                'titulo' => 'Asistencia - Ten Cuidado',
                'mensaje' => $mensajes[array_rand($mensajes)],
                'tipo' => 'info'
            ];
        } else {
            $mensajes = [
                '¡Excelente asistencia! Tu compromiso es admirable. ¡Sigue así!',
                'Tu puntualidad y asistencia son ejemplares. ¡Eres un gran ejemplo!',
                '¡Perfecto! Tu presencia constante demuestra tu dedicación. ¡Continúa con ese entusiasmo!',
                '¡Maravilloso! No has faltado mucho. Tu perseverancia te llevará lejos.',
            ];
            $stats['recordatorios'][] = [
                'titulo' => 'Asistencia - ¡Fantástico!',
                'mensaje' => $mensajes[array_rand($mensajes)],
                'tipo' => 'success'
            ];
        }

        if ($periodoActual && isset($stats['calificaciones'])) {
            $promedio = $stats['calificaciones']['promedio'];
            
            if ($promedio >= 17) {
                $mensajes = [
                    '¡Eres una estrella brillante! Tu rendimiento es sobresaliente. ¡Sigue iluminando con tu conocimiento!',
                    '¡Increíble! Estás en el nivel más alto. Tu esfuerzo y dedicación son inspiradores.',
                    '¡Extraordinario! Tus calificaciones son excepcionales. ¡Estás demostrando tu verdadero potencial!',
                    '¡Wow! Eres un ejemplo a seguir. Tu excelencia académica es admirable.',
                ];
                $stats['recordatorios'][] = [
                    'titulo' => 'Promedio - ¡Excelencia Académica!',
                    'mensaje' => $mensajes[array_rand($mensajes)],
                    'tipo' => 'success'
                ];
            } elseif ($promedio >= 14) {
                $mensajes = [
                    '¡Muy buen trabajo! Tu promedio es destacable. Con un poco más de esfuerzo alcanzarás la excelencia.',
                    '¡Genial! Estás en buen camino. Tu dedicación está dando frutos. ¡Sigue adelante!',
                    '¡Excelente desempeño! Tus notas reflejan tu compromiso. ¡Estás cerca de la cima!',
                    '¡Felicitaciones! Tu rendimiento es muy bueno. ¡Un pequeño esfuerzo más y serás imparable!',
                ];
                $stats['recordatorios'][] = [
                    'titulo' => 'Promedio - ¡Muy Bien!',
                    'mensaje' => $mensajes[array_rand($mensajes)],
                    'tipo' => 'success'
                ];
            } elseif ($promedio >= 11) {
                $mensajes = [
                    'Vas aprobando, ¡eso es positivo! Pero puedes dar mucho más. ¡Esfuérzate un poco más!',
                    'Tu promedio es aprobatorio, pero sabemos que puedes mejorar. ¡Tú puedes lograrlo!',
                    'Estás en el camino correcto. Con más dedicación, verás mejores resultados. ¡Ánimo!',
                    'Promedio aprobado, pero no te conformes. ¡Tienes potencial para brillar aún más!',
                ];
                $stats['recordatorios'][] = [
                    'titulo' => 'Promedio - Puedes Mejorar',
                    'mensaje' => $mensajes[array_rand($mensajes)],
                    'tipo' => 'info'
                ];
            } else {
                $mensajes = [
                    'Tu promedio necesita atención urgente. No te rindas, busca apoyo y ponte al día. ¡Tú puedes salir adelante!',
                    'Es momento de actuar. Pide ayuda a tus profesores y compañeros. Cada día es una nueva oportunidad.',
                    'Sabemos que puedes mejorar. No estás solo, estamos aquí para apoyarte. ¡Juntos lo lograremos!',
                    'Tu promedio está bajo, pero aún hay tiempo. Con esfuerzo y dedicación, puedes recuperarte. ¡No te des por vencido!',
                ];
                $stats['recordatorios'][] = [
                    'titulo' => 'Promedio - Necesitas Ayuda',
                    'mensaje' => $mensajes[array_rand($mensajes)],
                    'tipo' => 'warning'
                ];
            }
            
            // Mensaje adicional motivacional general (aleatorio)
            $mensajesGenerales = [
                'Recuerda: "El éxito es la suma de pequeños esfuerzos repetidos día tras día."',
                '"La educación es el arma más poderosa que puedes usar para cambiar el mundo." - Nelson Mandela',
                'Cada día es una oportunidad para aprender algo nuevo. ¡Aprovéchalo al máximo!',
                '"El aprendizaje es un tesoro que seguirá contigo toda la vida." - Proverbio chino',
                'No te compares con otros, compite contigo mismo y supera tus propios récords.',
                '"La única forma de hacer un gran trabajo es amar lo que haces." - Steve Jobs',
                'Los errores son pruebas de que lo estás intentando. ¡Sigue aprendiendo!',
                '"El éxito no es definitivo, el fracaso no es fatal: lo que cuenta es el coraje para continuar."',
            ];
            
            if (rand(1, 2) === 1) { // 50% de probabilidad de mostrar mensaje general
                $stats['recordatorios'][] = [
                    'titulo' => 'Pensamiento del Día',
                    'mensaje' => $mensajesGenerales[array_rand($mensajesGenerales)],
                    'tipo' => 'info'
                ];
            }
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

    /**
     * Obtener información del año académico actual
     */
    public function anioAcademico(Request $request)
    {
        $periodoActivo = PeriodoAcademico::activo();
        $anioActual = PeriodoAcademico::anioActual();
        
        // Obtener todos los periodos del año actual
        $periodosDelAnio = PeriodoAcademico::delAnioActual();
        
        return response()->json([
            'anio_actual' => $anioActual,
            'periodo_activo' => $periodoActivo ? [
                'id' => $periodoActivo->id,
                'nombre' => $periodoActivo->nombre,
                'anio' => $periodoActivo->anio,
                'estado' => $periodoActivo->estado,
            ] : null,
            'periodos_disponibles' => $periodosDelAnio->map(function($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre,
                    'anio' => $periodo->anio,
                    'estado' => $periodo->estado,
                ];
            }),
            'total_periodos' => $periodosDelAnio->count(),
        ]);
    }}