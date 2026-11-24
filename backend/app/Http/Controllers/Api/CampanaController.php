<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\Cita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampanaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['campanas' => $request->user()->hospital->campanas()
            ->withCount(['citas as cupos_ocupados' => fn ($query) => $query->where('estado', '!=', 'cancelada')])
            ->orderBy('fecha')->get()->map(fn (Campana $campana) => self::presentar($campana))]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $this->validar($request, new Campana);
        $campana = $request->user()->hospital->campanas()->create([...$datos, 'estado' => 'programada']);

        return response()->json(['campana' => self::presentar($campana->refresh())], 201);
    }

    public function show(Request $request, string $campana): JsonResponse
    {
        $registro = $request->user()->hospital->campanas()->findOrFail($campana);

        return response()->json(['campana' => self::presentar($registro)]);
    }

    public function update(Request $request, string $campana): JsonResponse
    {
        $registro = DB::transaction(function () use ($request, $campana) {
            $registro = $request->user()->hospital->campanas()->lockForUpdate()->findOrFail($campana);
            abort_if(in_array($registro->estado, ['cancelada', 'finalizada'], true), 409, 'La campaña ya terminó o fue cancelada.');
            $datos = $this->validar($request, $registro);
            $ocupados = $registro->citas()->where('estado', '!=', 'cancelada')->count();
            if (isset($datos['cupos']) && $datos['cupos'] < $ocupados) {
                throw ValidationException::withMessages(['cupos' => 'Los cupos no pueden ser menores que las reservas existentes.']);
            }

            $candidata = (clone $registro)->fill($datos);
            if ($ocupados > 0 && ($this->cambioHorario($registro, $candidata) || $candidata->isDirty(['lugar', 'latitud', 'longitud']))) {
                throw ValidationException::withMessages(['fecha' => 'No puedes cambiar el horario o lugar de una campaña con reservas.']);
            }
            $nuevoEstado = $datos['estado'] ?? $registro->estado;
            abort_if($registro->estado === 'en_curso' && $nuevoEstado === 'programada', 409, 'Una campaña en curso no puede volver a programada.');
            abort_if($nuevoEstado === 'en_curso' && ! now()->between($candidata->inicio(), $candidata->fin()),
                409, 'La campaña solo puede iniciarse dentro de su horario.');
            abort_if($nuevoEstado === 'finalizada' && $candidata->fin()->isFuture(),
                409, 'La campaña solo puede finalizar después de su horario.');
            $registro->update($datos);

            return $registro;
        }, 3);

        return response()->json(['campana' => self::presentar($registro)]);
    }

    public function destroy(Request $request, string $campana): JsonResponse
    {
        $registro = DB::transaction(function () use ($request, $campana) {
            $registro = $request->user()->hospital->campanas()->lockForUpdate()->findOrFail($campana);
            if ($registro->estado === 'cancelada') {
                return $registro;
            }
            abort_unless($registro->estado === 'programada' && $registro->inicio()->isFuture(),
                409, 'Solo se puede cancelar una campaña programada que todavía no empezó.');
            $registro->update(['estado' => 'cancelada']);
            $registro->citas()->whereIn('estado', Cita::RESERVADAS)->update(['estado' => 'cancelada']);

            return $registro;
        }, 3);

        return response()->json(['campana' => self::presentar($registro)]);
    }

    public static function presentar(Campana $campana): array
    {
        $ocupados = $campana->cupos_ocupados ?? $campana->citas()->where('estado', '!=', 'cancelada')->count();

        return [...$campana->toArray(), 'cupos_ocupados' => $ocupados,
            'cupos_disponibles' => max(0, $campana->cupos - $ocupados),
            'inicio_utc' => $campana->inicio()->toISOString(), 'fin_utc' => $campana->fin()->toISOString()];
    }

    private function validar(Request $request, Campana $registro): array
    {
        $presencia = $registro->exists ? ['sometimes', 'required'] : ['required'];
        $datos = $request->validate([
            'nombre' => [...$presencia, 'string', 'max:150'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:16000'],
            'lugar' => [...$presencia, 'string', 'max:200'],
            'latitud' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'fecha' => [...$presencia, 'date_format:Y-m-d'],
            'hora_inicio' => [...$presencia, 'date_format:H:i,H:i:s'],
            'hora_fin' => [...$presencia, 'date_format:H:i,H:i:s'],
            'cupos' => [...$presencia, 'integer', 'between:1,65535'],
            'estado' => $registro->exists ? ['sometimes', 'required', 'in:programada,en_curso,finalizada'] : ['prohibited'],
        ]);
        foreach (['hora_inicio', 'hora_fin'] as $campo) {
            if (isset($datos[$campo]) && strlen($datos[$campo]) === 5) {
                $datos[$campo] .= ':00';
            }
        }
        $candidata = (clone $registro)->fill($datos);
        if ($candidata->fin()->lessThanOrEqualTo($candidata->inicio())) {
            throw ValidationException::withMessages(['hora_fin' => 'La hora de fin debe ser posterior al inicio, en el mismo día UTC.']);
        }
        if ((! $registro->exists || $this->cambioHorario($registro, $candidata)) && ! $candidata->inicio()->isFuture()) {
            throw ValidationException::withMessages(['fecha' => 'El inicio de la campaña debe ser futuro.']);
        }

        return $datos;
    }

    private function cambioHorario(Campana $original, Campana $candidata): bool
    {
        return ! $original->inicio()->equalTo($candidata->inicio()) || ! $original->fin()->equalTo($candidata->fin());
    }
}
