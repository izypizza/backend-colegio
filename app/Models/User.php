<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
            'email_verified_at' => 'datetime',
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

    // Helpers de roles
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isAuxiliar()
    {
        return $this->role === 'auxiliar';
    }

    public function isDocente()
    {
        return $this->role === 'docente';
    }

    public function isPadre()
    {
        return $this->role === 'padre';
    }

    public function isEstudiante()
    {
        return $this->role === 'estudiante';
    }

    /**
     * Verificar si el usuario tiene uno o varios roles
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    /**
     * Verificar si el usuario tiene permisos administrativos (admin o auxiliar)
     */
    public function hasAdminAccess(): bool
    {
        return in_array($this->role, ['admin', 'auxiliar']);
    }
}
