<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaLibro extends Model
{
    use HasFactory;

    protected $table = 'categorias_libros';

    protected $fillable = [
        'nombre',
    ];

    /**
     * Relación: Una categoría tiene muchos libros
     */
    public function libros()
    {
        return $this->hasMany(Libro::class, 'categoria_id');
    }
}
