<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo a los usuarios que tienen uno de los roles indicados.
 * Es como el guardia de una puerta con cartel "Solo personal autorizado".
 *
 * Uso en las rutas:
 *   ->middleware('rol:administrador')
 *   ->middleware('rol:hospital,administrador')   // cualquiera de los dos
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$rolesPermitidos): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return response()->json([
                'mensaje' => 'Debes iniciar sesión.',
            ], 401);
        }

        $usuario->loadMissing('rol');
        $nombreRolUsuario = $usuario->rol?->nombre;

        if (in_array($nombreRolUsuario, $rolesPermitidos, true) === false) {
            return response()->json([
                'mensaje' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
