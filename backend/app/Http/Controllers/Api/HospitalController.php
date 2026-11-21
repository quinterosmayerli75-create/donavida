<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HospitalController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['perfil' => $request->user()->hospital]);
    }

    public function update(Request $request): JsonResponse
    {
        $whatsapp = $request->input('whatsapp');
        if (is_string($whatsapp)) {
            $request->merge(['whatsapp' => preg_replace('/^\+|[\s()-]/', '', $whatsapp)]);
        }

        $datos = $request->validate([
            'razon_social' => ['sometimes', 'required', 'string', 'max:150'],
            'direccion' => ['sometimes', 'required', 'string', 'max:200'],
            'zona' => ['sometimes', 'nullable', 'string', 'max:100'],
            'latitud' => ['required_with:longitud', 'numeric', 'between:-90,90'],
            'longitud' => ['required_with:latitud', 'numeric', 'between:-180,180'],
            'telefono_contacto' => ['sometimes', 'required', 'string', 'max:20'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/D'],
        ], [
            'latitud.required_with' => 'Envía la latitud y la longitud juntas.',
            'longitud.required_with' => 'Envía la latitud y la longitud juntas.',
            'whatsapp.regex' => 'Ingresa un número de WhatsApp con código de país, por ejemplo, +59170000001.',
        ]);

        $hospital = $request->user()->hospital;
        $hospital->update($datos);

        return response()->json([
            'mensaje' => 'Perfil del hospital actualizado.',
            'perfil' => $hospital,
        ]);
    }
}
