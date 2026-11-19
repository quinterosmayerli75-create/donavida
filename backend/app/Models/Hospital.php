<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    protected $table = 'hospitales';

    protected $fillable = [
        'usuario_id',
        'razon_social',
        'nit',
        'direccion',
        'zona',
        'latitud',
        'longitud',
        'telefono_contacto',
        'whatsapp',
        'verificado',
    ];

    protected function casts(): array
    {
        return [
            'verificado' => 'boolean',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'hospital_id');
    }

    public function campanas(): HasMany
    {
        return $this->hasMany(Campana::class, 'hospital_id');
    }
}
