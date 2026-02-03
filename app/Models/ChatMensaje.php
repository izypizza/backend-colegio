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
        'leido_at',
    ];

    protected $casts = [
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
}
