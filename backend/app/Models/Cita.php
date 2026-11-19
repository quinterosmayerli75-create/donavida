<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'donante_id',
        'solicitud_id',
        'campana_id',
        'fecha_hora',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function donante(): BelongsTo
    {
        return $this->belongsTo(Donante::class, 'donante_id');
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class, 'campana_id');
    }

    public function donacion(): HasOne
    {
        return $this->hasOne(Donacion::class, 'cita_id');
    }
}
