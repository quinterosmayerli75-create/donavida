<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Donante;
use App\Services\GestionCitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitaController extends Controller
{
    public function __construct(private readonly GestionCitas $gestion) {}

    public function misCitas(Request $request): JsonResponse
    {
        return $this->listar($request, Cita::where('donante_id', $this->donante($request)->id));
    }

    public function agenda(Request $request): JsonResponse
    {
        return $this->listar($request, Cita::delHospital($request->user()->hospital->id));
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'solicitud_id' => ['nullable', 'required_without:campana_id', 'prohibits:campana_id', 'integer', 'exists:solicitudes,id'],
            'campana_id' => ['nullable', 'required_without:solicitud_id', 'prohibits:solicitud_id', 'integer', 'exists:campanas,id'],
            'fecha_hora' => ['required', 'date', 'after:now'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json(['cita' => $this->gestion->reservar($this->donante($request), $datos)], 201);
    }

    public function cancelarDonante(Request $request, string $cita): JsonResponse
    {
        $registro = Cita::where('donante_id', $this->donante($request)->id)->findOrFail($cita);

        return response()->json(['cita' => $this->gestion->cancelar($registro)]);
    }

    public function cancelarHospital(Request $request, string $cita): JsonResponse
    {
        $registro = Cita::delHospital($request->user()->hospital->id)->findOrFail($cita);

        return response()->json(['cita' => $this->gestion->cancelar($registro)]);
    }

    public function asistencia(Request $request, string $cita): JsonResponse
    {
        $registro = Cita::delHospital($request->user()->hospital->id)->findOrFail($cita);
        $datos = $request->validate(['estado' => ['required', 'in:asistio,no_asistio']]);

        return response()->json(['cita' => $this->gestion->asistencia($registro, $datos['estado'])]);
    }

    private function listar(Request $request, Builder $query): JsonResponse
    {
        $filtros = $request->validate([
            'estado' => ['sometimes', 'in:pendiente,confirmada,asistio,no_asistio,cancelada'],
            'desde' => ['sometimes', 'date_format:Y-m-d'],
            'hasta' => ['sometimes', 'date_format:Y-m-d', ...($request->has('desde') ? ['after_or_equal:desde'] : [])],
        ]);
        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (isset($filtros['desde'])) {
            $query->whereDate('fecha_hora', '>=', $filtros['desde']);
        }
        if (isset($filtros['hasta'])) {
            $query->whereDate('fecha_hora', '<=', $filtros['hasta']);
        }

        return response()->json(['citas' => $query
            ->with(['donante.usuario:id,nombre', 'donante.tipoSangre', 'solicitud.tipoSangre',
                'solicitud.hospital:id,razon_social,direccion', 'campana.hospital:id,razon_social', 'donacion'])
            ->orderBy('fecha_hora')->orderBy('id')->get()]);
    }

    private function donante(Request $request): Donante
    {
        abort_unless($request->user()->estado === 'activo', 403, 'La cuenta debe estar activa.');

        return $request->user()->donante()->firstOrFail();
    }
}
