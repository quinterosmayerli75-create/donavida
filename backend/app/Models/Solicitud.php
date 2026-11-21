<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solicitud extends Model
{
    public const ABIERTA = 'abierta';

    public const CUBIERTA = 'cubierta';

    public const CANCELADA = 'cancelada';

    protected $table = 'solicitudes';

    protected $fillable = [
        'hospital_id',
        'tipo_sangre_id',
        'unidades_requeridas',
        'unidades_confirmadas',
        'prioridad',
        'motivo',
        'fecha_limite',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'tipo_sangre_id' => 'integer',
            'unidades_requeridas' => 'integer',
            'unidades_confirmadas' => 'integer',
            'fecha_limite' => 'datetime',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function tipoSangre(): BelongsTo
    {
        return $this->belongsTo(TipoSangre::class, 'tipo_sangre_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'solicitud_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'solicitud_id');
    }
}
