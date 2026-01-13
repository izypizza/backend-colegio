<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'autor',
        'isbn',
        'editorial',
        'anio_publicacion',
        'cantidad_total',
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
     * Calcular la cantidad disponible basado en préstamos activos APROBADOS
     */
    public function getCantidadDisponibleAttribute()
    {
        $cantidadTotal = $this->cantidad_total ?? 1;
        // Solo contar préstamos aprobados y no devueltos
        $prestamosActivos = $this->prestamos()
            ->where('estado', 'aprobado')
            ->where('devuelto', false)
            ->count();
        return max(0, $cantidadTotal - $prestamosActivos);
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
