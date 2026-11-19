<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'solicitud_id',
        'tipo',
        'titulo',
        'mensaje',
        'leida',
        'fecha_lectura',
    ];

    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
            'fecha_lectura' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }
}
