<?php

use App\Models\TipoSangre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de DonaVida  (todas las rutas empiezan con /api)
|--------------------------------------------------------------------------
| Cada integrante agrega aquí las rutas de su módulo, dentro de su bloque.
| Ver TAREAS.md en la raíz del repositorio.
*/

// Prueba rápida de que la API responde: GET /api/estado
Route::get('/estado', function () {
    return response()->json([
        'sistema' => 'DonaVida API',
        'estado' => 'funcionando',
    ]);
});

// Catálogo público de tipos de sangre: GET /api/tipos-sangre
Route::get('/tipos-sangre', function () {
    return TipoSangre::orderBy('id')->get();
});

// ---------- Módulo 1: Autenticación y usuarios (Integrante A) ----------
// Route::post('/registro', ...);
// Route::post('/login', ...);

Route::middleware('auth:sanctum')->group(function () {

    // Datos del usuario que inició sesión: GET /api/yo
    Route::get('/yo', function (Request $request) {
        return $request->user()->load('rol');
    });

    // Route::post('/logout', ...);

    // ---------- Módulo 2: Donantes (Integrante B) ----------

    // ---------- Módulo 3: Hospitales y solicitudes (Integrante C) ----------

    // ---------- Módulo 4: Citas, donaciones y campañas (Integrante D) ----------
});
