<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    // Nombres de los roles, para no escribir textos sueltos en el código.
    public const ADMINISTRADOR = 'administrador';
    public const HOSPITAL = 'hospital';
    public const DONANTE = 'donante';

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /** Un rol tiene muchos usuarios. */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }
}
