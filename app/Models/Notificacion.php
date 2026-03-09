<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'user_id',
        'titulo',
        'mensaje',
        'tipo',
        'prioridad',
        'icono',
        'data',
        'accion_url',
        'leido_at',
    ];

    protected $casts = [
        'data' => 'array',
        'leido_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar como leída
     */
    public function marcarLeida(): void
    {
        if (!$this->leido_at) {
            $this->update(['leido_at' => now()]);
        }
    }

    /**
     * Verificar si está leída
     */
    public function estaLeida(): bool
    {
        return !is_null($this->leido_at);
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->whereNull('leido_at');
    }

    /**
     * Scope para notificaciones por tipo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para notificaciones de alta prioridad
     */
    public function scopeAltaPrioridad($query)
    {
        return $query->where('prioridad', 'alta');
    }
}
