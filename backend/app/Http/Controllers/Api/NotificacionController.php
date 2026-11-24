<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Módulo 1 (Mayerli): la "campanita" de avisos.
 * Cada usuario solo puede ver y marcar SUS propias notificaciones.
 */
class NotificacionController extends Controller
{
    /**
     * GET /api/notificaciones
     * GET /api/notificaciones?leida=0   (solo las no leídas)
     */
    public function listar(Request $request): JsonResponse
    {
        $consulta = Notificacion::where('usuario_id', $request->user()->id)
            ->with('solicitud.tipoSangre')
            ->orderBy('created_at', 'desc');

        if ($request->has('leida')) {
            $consulta->where('leida', $request->boolean('leida'));
        }

        return response()->json($consulta->get());
    }

    /**
     * GET /api/notificaciones/no-leidas
     * Devuelve solo el número, para el globito rojo de la campanita.
     */
    public function contarNoLeidas(Request $request): JsonResponse
    {
        $cantidad = Notificacion::where('usuario_id', $request->user()->id)
            ->where('leida', false)
            ->count();

        return response()->json([
            'no_leidas' => $cantidad,
        ]);
    }

    /**
     * PATCH /api/notificaciones/{notificacion}/leer
     */
    public function marcarLeida(Request $request, Notificacion $notificacion): JsonResponse
    {
        if ((int) $notificacion->usuario_id !== (int) $request->user()->id) {
            return response()->json([
                'mensaje' => 'Esta notificación no te pertenece.',
            ], 403);
        }

        if ($notificacion->leida === false) {
            $notificacion->leida = true;
            $notificacion->fecha_lectura = now();
            $notificacion->save();
        }

        return response()->json($notificacion);
    }

    /**
     * PATCH /api/notificaciones/leer-todas
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $cantidadActualizada = Notificacion::where('usuario_id', $request->user()->id)
            ->where('leida', false)
            ->update([
                'leida' => true,
                'fecha_lectura' => now(),
            ]);

        return response()->json([
            'mensaje' => 'Notificaciones marcadas como leídas.',
            'actualizadas' => $cantidadActualizada,
        ]);
    }
}
