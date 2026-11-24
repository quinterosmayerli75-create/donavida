<?php

namespace App\Models;

use Carbon\CarbonImmutable;
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
            'cupos' => 'integer',
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

    public function inicio(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->fecha->format('Y-m-d').' '.$this->hora_inicio, 'UTC');
    }

    public function fin(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->fecha->format('Y-m-d').' '.$this->hora_fin, 'UTC');
    }
}
