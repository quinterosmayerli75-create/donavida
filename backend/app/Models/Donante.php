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
     * Fecha desde la que el donante puede volver a donar (última donación + 3 meses).
     * Devuelve null si nunca donó (puede donar ya).
     *
     * Se usa addMonthsNoOverflow: si donó el 30 de noviembre, puede volver el 28 de febrero
     * (y no el 2 de marzo, que es lo que pasaría si el mes "se desbordara").
     */
    public function proximaFechaParaDonar(): ?Carbon
    {
        if ($this->fecha_ultima_donacion === null) {
            return null;
        }

        return $this->fecha_ultima_donacion->copy()->addMonthsNoOverflow(self::MESES_ENTRE_DONACIONES);
    }

    /**
     * Regla clave: puede donar si nunca donó o si ya pasaron 3 meses desde la última donación.
     */
    public function puedeDonarHoy(): bool
    {
        $fechaPermitida = $this->proximaFechaParaDonar();

        if ($fechaPermitida === null) {
            return true;
        }

        return Carbon::today()->greaterThanOrEqualTo($fechaPermitida);
    }

    /** Días que le faltan para poder donar (0 si ya puede). */
    public function diasParaPoderDonar(): int
    {
        if ($this->puedeDonarHoy()) {
            return 0;
        }

        return (int) Carbon::today()->diffInDays($this->proximaFechaParaDonar());
    }

    public function edad(): int
    {
        return (int) $this->fecha_nacimiento->diffInYears(Carbon::today());
    }

    /**
     * Niveles de insignia según la cantidad de donaciones válidas.
     * Cada fila: [donaciones mínimas, nivel, nombre].
     */
    public const NIVELES_INSIGNIA = [
        [1, 1, 'Primera gota'],
        [3, 2, 'Corazón solidario'],
        [5, 3, 'Salvavidas'],
        [10, 4, 'Leyenda DonaVida'],
    ];

    /**
     * Calcula la insignia "Héroe nivel X" a partir del número de donaciones válidas.
     * También indica cuántas donaciones faltan para el siguiente nivel.
     *
     * @return array{nivel: int, titulo: string, nombre: string|null, donaciones_validas: int, siguiente_nivel_en: int|null}
     */
    public static function calcularInsignia(int $donacionesValidas): array
    {
        $nivel = 0;
        $nombre = null;
        $siguienteNivelEn = null;

        foreach (self::NIVELES_INSIGNIA as [$minimo, $numeroNivel, $nombreNivel]) {
            if ($donacionesValidas >= $minimo) {
                $nivel = $numeroNivel;
                $nombre = $nombreNivel;
            } else {
                $siguienteNivelEn = $minimo - $donacionesValidas;
                break;
            }
        }

        return [
            'nivel' => $nivel,
            'titulo' => $nivel === 0 ? 'Aún sin insignia' : 'Héroe nivel ' . $nivel,
            'nombre' => $nombre,
            'donaciones_validas' => $donacionesValidas,
            'siguiente_nivel_en' => $siguienteNivelEn,
        ];
    }
}
