<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eleccion extends Model
{
    use HasFactory;

    protected $table = 'elecciones';

    protected $fillable = [
        'titulo',
        'fecha',
        'fecha_inicio',
        'fecha_cierre',
        'estado',
        'resultados_publicados'
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_inicio' => 'datetime',
        'fecha_cierre' => 'datetime',
        'resultados_publicados' => 'boolean',
    ];

    /**
     * Relación: Una elección tiene muchos candidatos
     */
    public function candidatos()
    {
        return $this->hasMany(Candidato::class, 'eleccion_id');
    }

    /**
     * Relación: Una elección tiene muchos partidos
     */
    public function partidos()
    {
        return $this->hasMany(Partido::class);
    }

    /**
     * Relación: Una elección tiene muchos votos
     */
    public function votos()
    {
        return $this->hasMany(Voto::class, 'eleccion_id');
    }

    /**
     * Verificar si un usuario ya votó
     */
    public function usuarioYaVoto(User $user)
    {
        return $this->votos()->where('user_id', $user->id)->exists();
    }

    /**
     * Obtener resultados de la elección
     */
    public function resultados()
    {
        return $this->candidatos()
            ->withCount('votos')
            ->orderBy('votos_count', 'desc')
            ->get();
    }
}
