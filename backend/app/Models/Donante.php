<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donante extends Model
{
    // Reglas de negocio del proyecto
    public const MESES_ENTRE_DONACIONES = 3;
    public const EDAD_MINIMA = 18;   // Ley 1687, art. 19
    public const EDAD_MAXIMA = 60;   // Ley 1687, art. 19

    protected $table = 'donantes';

    protected $fillable = [
        'usuario_id',
        'tipo_sangre_id',
        'apellidos',
        'ci',
        'fecha_nacimiento',
        'sexo',
        'peso_kg',
        'direccion',
        'zona',
        'latitud',
        'longitud',
        'disponible',
        'fecha_ultima_donacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_ultima_donacion' => 'date',
            'disponible' => 'boolean',
            'peso_kg' => 'decimal:2',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function tipoSangre(): BelongsTo
    {
        return $this->belongsTo(TipoSangre::class, 'tipo_sangre_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'donante_id');
    }

    public function donaciones(): HasMany
    {
        return $this->hasMany(Donacion::class, 'donante_id');
    }

    /**
     * Regla clave: puede donar si nunca donó o si ya pasaron 3 meses desde la última donación.
     */
    public function puedeDonarHoy(): bool
    {
        if ($this->fecha_ultima_donacion === null) {
            return true;
        }

        $fechaPermitida = $this->fecha_ultima_donacion->copy()->addMonths(self::MESES_ENTRE_DONACIONES);

        return Carbon::today()->greaterThanOrEqualTo($fechaPermitida);
    }

    public function edad(): int
    {
        return (int) $this->fecha_nacimiento->diffInYears(Carbon::today());
    }
}
