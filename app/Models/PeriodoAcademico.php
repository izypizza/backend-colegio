<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    use HasFactory;

    protected $table = 'periodos_academicos';

    protected $fillable = [
        'nombre',
        'anio',
        'estado'
    ];

    /**
     * Obtener el periodo académico activo actual
     */
    public static function activo()
    {
        return static::where('estado', 'activo')->first();
    }

    /**
     * Obtener el año académico actual basado en el periodo activo
     */
    public static function anioActual(): int
    {
        $periodoActivo = static::activo();
        return $periodoActivo ? $periodoActivo->anio : (int) date('Y');
    }

    /**
     * Obtener todos los periodos del año actual
     */
    public static function delAnioActual()
    {
        $anio = static::anioActual();
        return static::where('anio', $anio)->get();
    }

    /**
     * Scope para filtrar por año
     */
    public function scopeDelAnio($query, int $anio)
    {
        return $query->where('anio', $anio);
    }

    /**
     * Scope para periodos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocenteMateria::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }
}
