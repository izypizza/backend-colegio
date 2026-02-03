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
        'ultimo_mensaje_at',
    ];

    protected $casts = [
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
}
