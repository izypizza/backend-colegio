<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'accion',
        'entidad',
        'entidad_id',
        'descripcion',
        'cambios_antes',
        'cambios_despues',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'cambios_antes' => 'array',
        'cambios_despues' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
