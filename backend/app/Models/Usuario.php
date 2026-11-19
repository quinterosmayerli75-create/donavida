<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'rol_id',
        'nombre',
        'email',
        'telefono',
        'password',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',   // Laravel cifra la contraseña automáticamente
        ];
    }

    /** Un usuario pertenece a un rol. */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /** Si el usuario es donante, aquí está su perfil. */
    public function donante(): HasOne
    {
        return $this->hasOne(Donante::class, 'usuario_id');
    }

    /** Si el usuario es hospital, aquí están sus datos. */
    public function hospital(): HasOne
    {
        return $this->hasOne(Hospital::class, 'usuario_id');
    }

    /** Avisos que recibió este usuario. */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    public function tieneRol(string $nombreRol): bool
    {
        return $this->rol !== null && $this->rol->nombre === $nombreRol;
    }
}
