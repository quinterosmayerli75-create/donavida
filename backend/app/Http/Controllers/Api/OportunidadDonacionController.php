<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OportunidadDonacionController extends Controller
{
    public function solicitudes(Request $request): JsonResponse
    {
        abort_unless($request->user()->estado === 'activo', 403, 'La cuenta debe estar activa.');
        $donante = $request->user()->donante()->firstOrFail();
        $codigo = $donante->tipoSangre->codigo;
        $solicitudes = Solicitud::where('estado', Solicitud::ABIERTA)->where('fecha_limite', '>', now())
            ->whereHas('hospital', fn ($query) => $query->where('verificado', true)->whereRelation('usuario', 'estado', 'activo'))
            ->with(['tipoSangre', 'hospital:id,razon_social,direccion,latitud,longitud,whatsapp'])
            ->orderBy('fecha_limite')->get()
            ->filter(fn (Solicitud $solicitud) => in_array($codigo, $solicitud->tipoSangre->puede_recibir_de, true))
            ->values();

        return response()->json(['solicitudes' => $solicitudes]);
    }

    public function campanas(Request $request): JsonResponse
    {
        abort_unless($request->user()->estado === 'activo', 403, 'La cuenta debe estar activa.');
        $campanas = Campana::whereIn('estado', ['programada', 'en_curso'])->whereDate('fecha', '>=', now()->toDateString())
            ->whereHas('hospital', fn ($query) => $query->where('verificado', true)->whereRelation('usuario', 'estado', 'activo'))
            ->with('hospital:id,razon_social,direccion,latitud,longitud,whatsapp')
            ->withCount(['citas as cupos_ocupados' => fn ($query) => $query->where('estado', '!=', 'cancelada')])
            ->orderBy('fecha')->orderBy('hora_inicio')->get()
            ->filter(fn (Campana $campana) => $campana->fin()->isFuture() && $campana->cupos_ocupados < $campana->cupos)
            ->map(fn (Campana $campana) => CampanaController::presentar($campana))->values();

        return response()->json(['campanas' => $campanas]);
    }
}
