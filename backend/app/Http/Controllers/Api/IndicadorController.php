<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Donacion;
use App\Models\Solicitud;
use App\Models\TipoSangre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndicadorController extends Controller
{
    public function hospital(Request $request): JsonResponse
    {
        return $this->indicadores($request->user()->hospital->id);
    }

    public function administrador(): JsonResponse
    {
        return $this->indicadores(null);
    }

    private function indicadores(?int $hospitalId): JsonResponse
    {
        $donaciones = Donacion::where('donaciones.estado', 'valida');
        $solicitudes = Solicitud::query();
        $citas = Cita::query();
        if ($hospitalId !== null) {
            $donaciones->whereHas('cita', fn ($query) => $query->delHospital($hospitalId));
            $solicitudes->where('hospital_id', $hospitalId);
            $citas->delHospital($hospitalId);
        }

        $porTipo = (clone $donaciones)->join('donantes', 'donantes.id', '=', 'donaciones.donante_id')
            ->selectRaw('donantes.tipo_sangre_id, COUNT(*) as total')->groupBy('donantes.tipo_sangre_id')
            ->pluck('total', 'tipo_sangre_id');
        $porEstado = (clone $solicitudes)->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $asistencia = (clone $citas)->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');

        return response()->json(['indicadores' => [
            'donaciones_validas' => (clone $donaciones)->count(),
            'volumen_total_ml' => (int) (clone $donaciones)->sum('volumen_ml'),
            'donaciones_por_tipo_sangre' => TipoSangre::orderBy('id')->get(['id', 'codigo'])->map(fn ($tipo) => [
                'tipo_sangre' => $tipo->codigo, 'total' => (int) ($porTipo[$tipo->id] ?? 0),
            ]),
            'solicitudes_por_estado' => collect(['abierta', 'cubierta', 'cerrada', 'cancelada'])
                ->mapWithKeys(fn ($estado) => [$estado => (int) ($porEstado[$estado] ?? 0)]),
            'citas_por_estado' => collect(['pendiente', 'confirmada', 'asistio', 'no_asistio', 'cancelada'])
                ->mapWithKeys(fn ($estado) => [$estado => (int) ($asistencia[$estado] ?? 0)]),
            'unidades_confirmadas' => (int) (clone $solicitudes)->sum('unidades_confirmadas'),
        ]]);
    }
}
