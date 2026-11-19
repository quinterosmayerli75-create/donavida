<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campana extends Model
{
    protected $table = 'campanas';

    protected $fillable = [
        'hospital_id',
        'nombre',
        'descripcion',
        'lugar',
        'latitud',
        'longitud',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'cupos',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'campana_id');
    }
}
