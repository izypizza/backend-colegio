<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
    // Relaciones
    public function docente()
    {
        return $this->hasOne(Docente::class);
    }

    public function padre()
    {
        return $this->hasOne(Padre::class);
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class);
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class);
    }

    public function auditorias()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function chatMensajes()
    {
        return $this->hasMany(ChatMensaje::class);
    }

    /**
     * Helpers de roles (compatibilidad con código existente)
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAuxiliar(): bool
    {
        return $this->hasRole('auxiliar');
    }

    public function isDocente(): bool
    {
        return $this->hasRole('docente');
    }

    public function isPadre(): bool
    {
        return $this->hasRole('padre');
    }

    public function isEstudiante(): bool
    {
        return $this->hasRole('estudiante');
    }

    public function hasAdminAccess(): bool
    {
        return $this->hasAnyRole(['admin', 'auxiliar']);
    }
}
