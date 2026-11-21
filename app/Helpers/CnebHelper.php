<?php

namespace App\Helpers;

class CnebHelper
{
    public const NIVELES = ['AD', 'A', 'B', 'C'];

    public const ESCALAS = ['literal', 'numerica'];

    public const CICLOS = ['III', 'IV', 'V', 'VI', 'VII'];

    /**
     * Convertir una nota vigesimal al nivel de logro del Currículo Nacional.
     */
    public static function nivelDesdeNota(float $nota): string
    {
        return match (true) {
            $nota >= 18 => 'AD',
            $nota >= 14 => 'A',
            $nota >= 11 => 'B',
            default => 'C',
        };
    }

    /**
     * Nota de referencia para un nivel. Sirve para reportes que exigen un
     * valor numérico: no debe usarse para promediar competencias.
     */
    public static function notaReferencial(?string $nivel): ?float
    {
        return match ($nivel) {
            'AD' => 19.0,
            'A' => 16.0,
            'B' => 12.0,
            'C' => 8.0,
            default => null,
        };
    }

    public static function normalizarNivel(?string $nivel): ?string
    {
        if ($nivel === null) {
            return null;
        }

        $nivel = strtoupper(trim($nivel));

        return in_array($nivel, self::NIVELES, true) ? $nivel : null;
    }

    /**
     * Los niveles B y C exigen conclusión descriptiva en el periodo.
     */
    public static function requiereConclusionDescriptiva(?string $nivel): bool
    {
        return in_array(self::normalizarNivel($nivel), ['B', 'C'], true);
    }

    public static function esCicloValido(?string $ciclo): bool
    {
        return in_array($ciclo, self::CICLOS, true);
    }
}
