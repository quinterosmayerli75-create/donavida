<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donacion extends Model
{
    protected $table = 'donaciones';

    protected $fillable = [
        'cita_id',
        'donante_id',
        'fecha_donacion',
        'volumen_ml',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_donacion' => 'datetime',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function donante(): BelongsTo
    {
        return $this->belongsTo(Donante::class, 'donante_id');
    }
}
