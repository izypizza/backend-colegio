<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'tipo',
        'autor',
        'isbn',
        'editorial',
        'anio_publicacion',
        'cantidad_total',
        'url_digital',
        'formato_digital',
        'categoria_id',
        'disponible',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'anio_publicacion' => 'integer',
        'cantidad_total' => 'integer',
    ];

    protected $appends = ['cantidad_disponible'];

    /**
     * Calcular la cantidad disponible según ejemplares reales.
     */
    public function getCantidadDisponibleAttribute()
    {
        $cantidadEjemplares = $this->ejemplares()->count();

        if ($cantidadEjemplares > 0) {
            return $this->ejemplares()->where('estado', 'disponible')->count();
        }

        $cantidadTotal = $this->cantidad_total ?? 1;
        $prestamosActivos = $this->prestamos()
            ->where('estado', 'aprobado')
            ->where('devuelto', false)
            ->count();

        return max(0, $cantidadTotal - $prestamosActivos);
    }

    public function ejemplares()
    {
        return $this->hasMany(EjemplarLibro::class, 'libro_id');
    }

    /**
     * Relación: Un libro pertenece a una categoría
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaLibro::class, 'categoria_id');
    }

    /**
     * Relación: Un libro tiene muchos préstamos
     */
    public function prestamos()
    {
        return $this->hasMany(PrestamoLibro::class, 'libro_id');
    }

    /**
     * Obtener el préstamo activo del libro
     */
    public function prestamoActivo()
    {
        return $this->hasOne(PrestamoLibro::class, 'libro_id')
                    ->where('devuelto', false)
                    ->where('estado', 'aprobado');
    }
}
