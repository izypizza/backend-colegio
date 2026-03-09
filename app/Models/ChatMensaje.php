<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMensaje extends Model
{
    protected $table = 'chat_mensajes';

    protected $fillable = [
        'conversacion_id',
        'user_id',
        'mensaje',
        'es_sistema',
        'leido_at',
    ];

    protected $casts = [
        'es_sistema' => 'boolean',
        'leido_at' => 'datetime',
    ];

    public function conversacion()
    {
        return $this->belongsTo(ChatConversacion::class, 'conversacion_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar mensaje como leído
     */
    public function marcarLeido(): void
    {
        if (!$this->leido_at) {
            $this->update(['leido_at' => now()]);
        }
    }

    /**
     * Verificar si el mensaje fue leído
     */
    public function fueLeido(): bool
    {
        return !is_null($this->leido_at);
    }

    /**
     * Scope para mensajes no leídos
     */
    public function scopeNoLeidos($query)
    {
        return $query->whereNull('leido_at');
    }

    /**
     * Scope para mensajes del sistema
     */
    public function scopeSistema($query)
    {
        return $query->where('es_sistema', true);
    }

    /**
     * Scope para mensajes de usuario
     */
    public function scopeUsuario($query)
    {
        return $query->where('es_sistema', false);
    }
}
