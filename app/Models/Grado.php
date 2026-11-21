<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    use HasFactory;

    protected $table = 'grados';

    protected $fillable = [
        'nombre',
        'nivel',
        'ciclo'
    ];

    /**
     * El ciclo del Currículo Nacional se deriva del grado cuando no se indica.
     */
    protected static function booted(): void
    {
        static::saving(function (self $grado) {
            if (! $grado->ciclo) {
                $grado->ciclo = self::calcularCiclo($grado->nombre, $grado->nivel);
            }
        });
    }

    public static function calcularCiclo(?string $nombre, ?string $nivel): ?string
    {
        if ($nombre === null || preg_match('/^\s*(\d)/', $nombre, $coincidencia) !== 1) {
            return null;
        }

        $numero = (int) $coincidencia[1];

        if ($nivel === 'primaria') {
            return match (true) {
                $numero <= 2 => 'III',
                $numero <= 4 => 'IV',
                default => 'V',
            };
        }

        if ($nivel === 'secundaria') {
            return $numero <= 2 ? 'VI' : 'VII';
        }

        return null;
    }

    public function competencias()
    {
        return $this->hasMany(Competencia::class, 'ciclo', 'ciclo');
    }

    public function secciones()
    {
        return $this->hasMany(Seccion::class);
    }

    public function estudiantes()
    {
        return $this->hasManyThrough(Estudiante::class, Seccion::class);
    }
}
