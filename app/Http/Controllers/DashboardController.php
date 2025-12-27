<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Padre;
use App\Models\Materia;
use App\Models\Seccion;
use App\Models\Grado;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Obtener estadísticas del dashboard según el rol del usuario
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        // Estadísticas básicas para todos los roles
        $stats = [
            'estudiantes' => 0,
            'docentes' => 0,
            'padres' => 0,
            'materias' => 0,
            'secciones' => 0,
            'grados' => 0,
        ];

        // Admin y Auxiliar tienen acceso a todas las estadísticas
        if (in_array($role, ['admin', 'auxiliar'])) {
            $stats = [
                'estudiantes' => Estudiante::count(),
                'docentes' => Docente::count(),
                'padres' => Padre::count(),
                'materias' => Materia::count(),
                'secciones' => Seccion::count(),
                'grados' => Grado::count(),
            ];
        }
        // Docente: ver estadísticas de sus materias/secciones
        elseif ($role === 'docente') {
            $docente = Docente::where('user_id', $user->id)->first();
            
            if ($docente) {
                $seccionesIds = $docente->asignaciones()->pluck('seccion_id')->unique();
                
                $stats = [
                    'estudiantes' => Estudiante::whereIn('seccion_id', $seccionesIds)->count(),
                    'docentes' => 1, // Solo él mismo
                    'padres' => 0,
                    'materias' => $docente->asignaciones()->distinct('materia_id')->count(),
                    'secciones' => $seccionesIds->count(),
                    'grados' => Seccion::whereIn('id', $seccionesIds)->distinct('grado_id')->count(),
                ];
            }
        }
        // Padre: ver información de sus hijos
        elseif ($role === 'padre') {
            $padre = Padre::where('user_id', $user->id)->first();
            
            if ($padre) {
                $hijos = $padre->estudiantes;
                
                $stats = [
                    'estudiantes' => $hijos->count(),
                    'docentes' => 0,
                    'padres' => 1, // Solo él mismo
                    'materias' => 0,
                    'secciones' => $hijos->pluck('seccion_id')->unique()->count(),
                    'grados' => 0,
                ];
            }
        }
        // Estudiante: ver solo su información
        elseif ($role === 'estudiante') {
            $estudiante = Estudiante::where('user_id', $user->id)->first();
            
            if ($estudiante) {
                $stats = [
                    'estudiantes' => 1, // Solo él mismo
                    'docentes' => 0,
                    'padres' => $estudiante->padres()->count(),
                    'materias' => 0,
                    'secciones' => 1,
                    'grados' => 1,
                ];
            }
        }

        return response()->json($stats);
    }
}
