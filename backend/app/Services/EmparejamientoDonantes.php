<?php

namespace App\Services;

use App\Models\Donante;
use App\Models\Hospital;
use App\Models\TipoSangre;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class EmparejamientoDonantes
{
    /** @return EloquentCollection<int, Donante> */
    public function elegibles(TipoSangre $tipoReceptor): EloquentCollection
    {
        $tiposCompatibles = TipoSangre::query()
            ->select('id')
            ->whereIn('codigo', $tipoReceptor->puede_recibir_de);

        return Donante::query()
            ->whereIn('tipo_sangre_id', $tiposCompatibles)
            ->where('disponible', true)
            ->whereRelation('usuario', 'estado', 'activo')
            ->with('tipoSangre:id,codigo')
            ->orderBy('id')
            ->get()
            ->filter(fn (Donante $donante) => $donante->puedeDonarHoy());
    }

    public function ordenarPorDistancia(TipoSangre $tipoReceptor, Hospital $hospital): Collection
    {
        return $this->elegibles($tipoReceptor)
            ->toBase()
            ->map(fn (Donante $donante) => [
                'id' => $donante->id,
                'tipo_sangre' => $donante->tipoSangre->codigo,
                'latitud' => $donante->latitud === null ? null : (float) $donante->latitud,
                'longitud' => $donante->longitud === null ? null : (float) $donante->longitud,
                'distancia_km' => $this->distanciaKm($hospital, $donante),
            ])
            ->sortBy(fn (array $donante) => $donante['distancia_km'] ?? INF)
            ->values()
            ->map(function (array $donante) {
                if ($donante['distancia_km'] !== null) {
                    $donante['distancia_km'] = round($donante['distancia_km'], 3);
                }

                return $donante;
            });
    }

    private function distanciaKm(Hospital $hospital, Donante $donante): ?float
    {
        if ($donante->latitud === null || $donante->longitud === null) {
            return null;
        }

        $latitudHospital = deg2rad((float) $hospital->latitud);
        $latitudDonante = deg2rad((float) $donante->latitud);
        $diferenciaLatitud = $latitudDonante - $latitudHospital;
        $diferenciaLongitud = deg2rad((float) $donante->longitud - (float) $hospital->longitud);

        $haversine = sin($diferenciaLatitud / 2) ** 2
            + cos($latitudHospital) * cos($latitudDonante) * sin($diferenciaLongitud / 2) ** 2;

        return 2 * 6371 * asin(sqrt(min(1, $haversine)));
    }
}
