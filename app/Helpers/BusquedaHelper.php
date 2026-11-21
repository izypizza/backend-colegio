<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class BusquedaHelper
{
    private const ACENTOS = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
    ];

    /**
     * Minúsculas y sin tildes, para comparar de forma tolerante.
     */
    public static function normalizar(?string $texto): string
    {
        return strtr(mb_strtolower(trim((string) $texto)), self::ACENTOS);
    }

    /**
     * Separa el término de búsqueda en palabras significativas.
     *
     * @return array<int, string>
     */
    public static function palabras(?string $termino): array
    {
        $normalizado = self::normalizar($termino);

        if ($normalizado === '') {
            return [];
        }

        return array_values(array_filter(
            preg_split('/\s+/', $normalizado) ?: [],
            fn (string $palabra) => $palabra !== ''
        ));
    }

    /**
     * Exige que cada palabra aparezca en al menos una de las columnas.
     * Así "juan perez" encuentra a Juan Pérez aunque el nombre y el apellido
     * estén en columnas distintas.
     *
     * @param  array<int, string>  $columnas
     */
    public static function aplicar(Builder $query, ?string $termino, array $columnas): Builder
    {
        foreach (self::palabras($termino) as $palabra) {
            $like = '%' . $palabra . '%';

            $query->where(function (Builder $condicion) use ($columnas, $like) {
                foreach ($columnas as $columna) {
                    $condicion->orWhereRaw(self::expresionSinTildes($columna) . ' LIKE ?', [$like]);
                }
            });
        }

        return $query;
    }

    /**
     * Expresión SQL que ignora tildes y mayúsculas, válida en MySQL y SQLite.
     */
    public static function expresionSinTildes(string $columna): string
    {
        $expresion = "LOWER({$columna})";

        foreach (['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'] as $conTilde => $sinTilde) {
            $expresion = "REPLACE({$expresion}, '{$conTilde}', '{$sinTilde}')";
        }

        return $expresion;
    }
}
