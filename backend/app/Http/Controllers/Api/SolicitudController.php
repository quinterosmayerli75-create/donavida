<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solicitud;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SolicitudController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'solicitudes' => $this->solicitudesDelHospital($request)->with('tipoSangre')->latest('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $this->validarDatos($request, creando: true);
        $solicitud = $this->solicitudesDelHospital($request)->create($datos)->refresh()->load('tipoSangre');

        return response()->json(['solicitud' => $solicitud], 201);
    }

    public function show(Request $request, string $solicitud): JsonResponse
    {
        return response()->json([
            'solicitud' => $this->solicitudesDelHospital($request)->findOrFail($solicitud)->load('tipoSangre'),
        ]);
    }

    public function update(Request $request, string $solicitud): JsonResponse
    {
        $actualizada = DB::transaction(function () use ($request, $solicitud) {
            $registro = $this->solicitudesDelHospital($request)->lockForUpdate()->findOrFail($solicitud);
            $this->verificarAbierta($registro);
            $datos = $this->validarDatos($request, creando: false);

            if (isset($datos['unidades_requeridas']) && $datos['unidades_requeridas'] < $registro->unidades_confirmadas) {
                throw ValidationException::withMessages([
                    'unidades_requeridas' => 'El total requerido debe ser igual o mayor que las unidades confirmadas.',
                ]);
            }

            if (isset($datos['tipo_sangre_id'])
                && (int) $datos['tipo_sangre_id'] !== $registro->tipo_sangre_id
                && ($registro->unidades_confirmadas > 0 || $registro->citas()->exists())) {
                throw ValidationException::withMessages([
                    'tipo_sangre_id' => 'El tipo de sangre queda fijado cuando la solicitud tiene citas o unidades confirmadas.',
                ]);
            }

            if (isset($datos['unidades_requeridas']) && (int) $datos['unidades_requeridas'] === $registro->unidades_confirmadas) {
                $datos['estado'] = Solicitud::CUBIERTA;
            }

            $registro->update($datos);

            return $registro->load('tipoSangre');
        });

        return response()->json(['solicitud' => $actualizada]);
    }

    public function destroy(Request $request, string $solicitud): JsonResponse
    {
        $cancelada = DB::transaction(function () use ($request, $solicitud) {
            $registro = $this->solicitudesDelHospital($request)->lockForUpdate()->findOrFail($solicitud);

            if ($registro->estado !== Solicitud::CANCELADA) {
                $this->verificarAbierta($registro);
                $registro->update(['estado' => Solicitud::CANCELADA]);
            }

            return $registro->load('tipoSangre');
        });

        return response()->json(['solicitud' => $cancelada]);
    }

    private function solicitudesDelHospital(Request $request): HasMany
    {
        return $request->user()->hospital->solicitudes();
    }

    private function verificarAbierta(Solicitud $solicitud): void
    {
        abort_unless($solicitud->estado === Solicitud::ABIERTA, 409, 'La solicitud debe estar abierta.');
    }

    private function validarDatos(Request $request, bool $creando): array
    {
        $presencia = $creando ? ['required'] : ['sometimes', 'required'];

        $datos = $request->validate([
            'tipo_sangre_id' => [...$presencia, 'integer', 'exists:tipos_sangre,id'],
            'unidades_requeridas' => [...$presencia, 'integer', 'between:1,65535'],
            'prioridad' => ['sometimes', 'required', 'in:baja,media,alta,critica'],
            'motivo' => ['sometimes', 'nullable', 'string', 'max:16000'],
            'fecha_limite' => [...$presencia, 'date', 'after:now'],
        ], [
            'tipo_sangre_id.exists' => 'El tipo de sangre elegido no existe.',
            'fecha_limite.after' => 'La fecha límite debe ser futura.',
        ]);

        if (isset($datos['fecha_limite'])) {
            $datos['fecha_limite'] = Date::parse($datos['fecha_limite'])->setTimezone(config('app.timezone'));
        }

        return $datos;
    }
}
