<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarHospitalActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario->estado !== 'activo' || ! $usuario->hospital?->verificado) {
            return response()->json([
                'mensaje' => 'Solo los hospitales activos y verificados pueden acceder.',
            ], 403);
        }

        return $next($request);
    }
}
