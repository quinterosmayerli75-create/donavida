<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoSangre extends Model
{
    protected $table = 'tipos_sangre';

    protected $fillable = [
        'codigo',
        'grupo',
        'factor_rh',
        'puede_recibir_de',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'puede_recibir_de' => 'array',   // el JSON se convierte en arreglo de PHP
        ];
    }

    public function donantes(): HasMany
    {
        return $this->hasMany(Donante::class, 'tipo_sangre_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'tipo_sangre_id');
    }
}
