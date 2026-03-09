<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversacion extends Model
{
    protected $table = 'chat_conversaciones';

    protected $fillable = [
        'docente_id',
        'padre_id',
        'estudiante_id',
        'asunto',
        'activa',
        'ultimo_mensaje_at',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'ultimo_mensaje_at' => 'datetime',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function padre()
    {
        return $this->belongsTo(Padre::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function mensajes()
    {
        return $this->hasMany(ChatMensaje::class, 'conversacion_id');
    }

    /**
     * Obtener el último mensaje de la conversación
     */
    public function ultimoMensaje()
    {
        return $this->hasOne(ChatMensaje::class, 'conversacion_id')->latestOfMany();
    }

    /**
     * Contar mensajes no leídos para un usuario
     */
    public function mensajesNoLeidosPara(int $userId): int
    {
        return $this->mensajes()
            ->where('user_id', '!=', $userId)
            ->whereNull('leido_at')
            ->count();
    }

    /**
     * Marcar todos los mensajes como leídos para un usuario
     */
    public function marcarMensajesLeidosPara(int $userId): void
    {
        $this->mensajes()
            ->where('user_id', '!=', $userId)
            ->whereNull('leido_at')
            ->update(['leido_at' => now()]);
    }

    /**
     * Archivar conversación
     */
    public function archivar(): void
    {
        $this->update(['activa' => false]);
    }

    /**
     * Reactivar conversación
     */
    public function reactivar(): void
    {
        $this->update(['activa' => true]);
    }

    /**
     * Scope para conversaciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Obtener participantes de la conversación
     */
    public function getParticipantesAttribute(): array
    {
        $participantes = [];
        
        if ($this->docente && $this->docente->user) {
            $participantes[] = [
                'user_id' => $this->docente->user->id,
                'nombre' => $this->docente->nombre_completo,
                'rol' => 'docente',
            ];
        }

        if ($this->padre && $this->padre->user) {
            $participantes[] = [
                'user_id' => $this->padre->user->id,
                'nombre' => $this->padre->nombre_completo,
                'rol' => 'padre',
            ];
        }

        return $participantes;
    }
}
