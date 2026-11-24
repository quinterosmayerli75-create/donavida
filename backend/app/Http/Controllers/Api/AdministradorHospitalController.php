<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Módulo 1 (Mayerli): el administrador revisa y valida a los hospitales.
 * Solo accesible con el rol "administrador".
 */
class AdministradorHospitalController extends Controller
{
    /**
     * GET /api/admin/hospitales
     * GET /api/admin/hospitales?verificado=0   (solo los pendientes)
     * GET /api/admin/hospitales?verificado=1   (solo los validados)
     */
    public function listar(Request $request): JsonResponse
    {
        $consulta = Hospital::with('usuario')->orderBy('created_at', 'desc');

        if ($request->has('verificado')) {
            $soloVerificados = $request->boolean('verificado');
            $consulta->where('verificado', $soloVerificados);
        }

        return response()->json($consulta->get());
    }

    /**
     * PATCH /api/admin/hospitales/{hospital}/validar
     * Aprueba el hospital: ya puede iniciar sesión y publicar solicitudes.
     */
    public function validar(Hospital $hospital): JsonResponse
    {
        DB::transaction(function () use ($hospital) {
            $hospital->verificado = true;
            $hospital->save();

            $hospital->usuario->estado = 'activo';
            $hospital->usuario->save();

            Notificacion::create([
                'usuario_id' => $hospital->usuario_id,
                'tipo' => 'sistema',
                'titulo' => 'Hospital validado',
                'mensaje' => 'Tu hospital fue validado. Ya puedes publicar solicitudes y campañas.',
            ]);
        });

        return response()->json([
            'mensaje' => 'Hospital validado correctamente.',
            'hospital' => $hospital->fresh('usuario'),
        ]);
    }

    /**
     * PATCH /api/admin/hospitales/{hospital}/desactivar
     * Desactiva la cuenta del hospital (eliminación lógica: no se borra nada).
     */
    public function desactivar(Hospital $hospital): JsonResponse
    {
        DB::transaction(function () use ($hospital) {
            $hospital->verificado = false;
            $hospital->save();

            $hospital->usuario->estado = 'inactivo';
            $hospital->usuario->save();

            // Se cierran todas sus sesiones abiertas
            $hospital->usuario->tokens()->delete();
        });

        return response()->json([
            'mensaje' => 'Hospital desactivado.',
            'hospital' => $hospital->fresh('usuario'),
        ]);
    }
}
