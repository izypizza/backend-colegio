<?php

namespace App\Helpers;

use App\Models\PeriodoAcademico;
use Carbon\Carbon;

class AcademicYearHelper
{
    /**
     * Obtiene el año académico actual basado en el último período registrado
     * o el año actual si no hay períodos.
     *
     * @return int El año académico actual
     */
    public static function getCurrentAcademicYear(): int
    {
        $periodoActual = PeriodoAcademico::orderBy('anio', 'desc')->first();

        return $periodoActual ? $periodoActual->anio : now()->year;
    }

    /**
     * Valida si una fecha de nacimiento es apropiada para un grado específico
     * en el año académico actual.
     *
     * @param  string  $fechaNacimiento  Fecha de nacimiento en formato Y-m-d
     * @param  string  $nombreGrado  Nombre del grado (ej: "1° Primaria", "3° Secundaria")
     * @return array ['valido' => bool, 'mensaje' => string, 'detalles' => array]
     */
    public static function validarEdadParaGrado(string $fechaNacimiento, string $nombreGrado): array
    {
        $fechaNacimiento = Carbon::parse($fechaNacimiento);
        $anioActual = self::getCurrentAcademicYear();

        // Calcular edad esperada según el grado y año académico
        $edadEsperada = 0;
        $tolerancia = 2; // Tolerancia de ±2 años

        if (str_contains($nombreGrado, 'Primaria')) {
            preg_match('/\d+/', $nombreGrado, $matches);
            $numeroGrado = intval($matches[0] ?? 1);
            // 1°P=6 años, 2°P=7 años, etc.
            $edadEsperada = 5 + $numeroGrado;
            $anioNacimientoEsperado = $anioActual - $edadEsperada;
        } elseif (str_contains($nombreGrado, 'Secundaria')) {
            preg_match('/\d+/', $nombreGrado, $matches);
            $numeroGrado = intval($matches[0] ?? 1);
            // 1°S=12 años, 2°S=13 años, etc.
            $edadEsperada = 11 + $numeroGrado;
            $anioNacimientoEsperado = $anioActual - $edadEsperada;
            $tolerancia = 3; // Mayor tolerancia en secundaria
        } else {
            $edadEsperada = 6;
            $anioNacimientoEsperado = $anioActual - $edadEsperada;
        }

        $anioNacimiento = $fechaNacimiento->year;
        $edadActual = $anioActual - $anioNacimiento;

        $edadMinima = $edadEsperada - $tolerancia;
        $edadMaxima = $edadEsperada + $tolerancia;

        if ($edadActual < $edadMinima) {
            return [
                'valido' => false,
                'mensaje' => "El estudiante es muy joven para {$nombreGrado} en el año {$anioActual}. ".
                           "Edad esperada: {$edadEsperada} años (rango: {$edadMinima}-{$edadMaxima}). ".
                           "Edad del estudiante: {$edadActual} años. ".
                           "Debería haber nacido aproximadamente en {$anioNacimientoEsperado}",
                'detalles' => [
                    'edad_actual' => $edadActual,
                    'edad_esperada' => $edadEsperada,
                    'edad_minima' => $edadMinima,
                    'edad_maxima' => $edadMaxima,
                    'anio_academico' => $anioActual,
                    'anio_nacimiento_esperado' => $anioNacimientoEsperado,
                ],
            ];
        }

        if ($edadActual > $edadMaxima) {
            return [
                'valido' => false,
                'mensaje' => "El estudiante parece mayor para {$nombreGrado} en el año {$anioActual}. ".
                           "Edad esperada: {$edadEsperada} años (rango: {$edadMinima}-{$edadMaxima}). ".
                           "Edad del estudiante: {$edadActual} años. ".
                           'Verifica la fecha de nacimiento o el grado seleccionado',
                'detalles' => [
                    'edad_actual' => $edadActual,
                    'edad_esperada' => $edadEsperada,
                    'edad_minima' => $edadMinima,
                    'edad_maxima' => $edadMaxima,
                    'anio_academico' => $anioActual,
                ],
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'La edad es apropiada para el grado',
            'detalles' => [
                'edad_actual' => $edadActual,
                'edad_esperada' => $edadEsperada,
                'edad_minima' => $edadMinima,
                'edad_maxima' => $edadMaxima,
                'anio_academico' => $anioActual,
            ],
        ];
    }

    /**
     * Calcula la edad de una persona en un año específico
     *
     * @param  string  $fechaNacimiento  Fecha de nacimiento
     * @param  int|null  $anio  Año para calcular la edad (null = año actual académico)
     * @return int Edad en el año especificado
     */
    public static function calcularEdad(string $fechaNacimiento, ?int $anio = null): int
    {
        $anio = $anio ?? self::getCurrentAcademicYear();
        $fechaNacimiento = Carbon::parse($fechaNacimiento);

        return $anio - $fechaNacimiento->year;
    }
}
