<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Donacion;
use App\Services\GestionCitas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['donaciones' => Donacion::whereHas('cita',
            fn ($query) => $query->delHospital($request->user()->hospital->id))
            ->with(['donante.usuario:id,nombre', 'donante.tipoSangre', 'cita'])
            ->latest('fecha_donacion')->get()]);
    }

    public function store(Request $request, string $cita, GestionCitas $gestion): JsonResponse
    {
        $registro = Cita::delHospital($request->user()->hospital->id)->findOrFail($cita);
        $datos = $request->validate([
            'volumen_ml' => ['sometimes', 'required', 'integer', 'between:1,65535'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json(['donacion' => $gestion->registrarDonacion($registro, $datos)], 201);
    }
}
