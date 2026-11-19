<?php

use App\Http\Controllers\Api\AdministradorHospitalController;
use App\Http\Controllers\Api\AutenticacionController;
use App\Http\Controllers\Api\NotificacionController;
use App\Models\Rol;
use App\Models\TipoSangre;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de DonaVida  (todas las rutas empiezan con /api)
|--------------------------------------------------------------------------
| Cada integrante agrega aquí las rutas de su módulo, dentro de su bloque.
| Ver TAREAS.md en la raíz del repositorio.
|
| Para proteger por rol:  ->middleware('rol:hospital')
| Roles: administrador, hospital, donante
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

// ---------- Módulo 1: Autenticación y usuarios (Mayerli) — rutas públicas ----------
Route::post('/registro/donante', [AutenticacionController::class, 'registrarDonante']);
Route::post('/registro/hospital', [AutenticacionController::class, 'registrarHospital']);
Route::post('/login', [AutenticacionController::class, 'iniciarSesion']);

// ---------- Rutas que necesitan token (Authorization: Bearer <token>) ----------
Route::middleware('auth:sanctum')->group(function () {

    // ---------- Módulo 1: Autenticación, usuarios y notificaciones (Mayerli) ----------
    Route::get('/yo', [AutenticacionController::class, 'usuarioActual']);
    Route::post('/logout', [AutenticacionController::class, 'cerrarSesion']);

    Route::get('/notificaciones', [NotificacionController::class, 'listar']);
    Route::get('/notificaciones/no-leidas', [NotificacionController::class, 'contarNoLeidas']);
    Route::patch('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);
    Route::patch('/notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida']);

    Route::middleware('rol:' . Rol::ADMINISTRADOR)->prefix('admin')->group(function () {
        Route::get('/hospitales', [AdministradorHospitalController::class, 'listar']);
        Route::patch('/hospitales/{hospital}/validar', [AdministradorHospitalController::class, 'validar']);
        Route::patch('/hospitales/{hospital}/desactivar', [AdministradorHospitalController::class, 'desactivar']);
    });

    // ---------- Módulo 2: Donantes (Maya) ----------
    // Route::middleware('rol:' . Rol::DONANTE)->group(function () { ... });

    // ---------- Módulo 3: Hospitales y solicitudes (Mati) ----------
    // Route::middleware('rol:' . Rol::HOSPITAL)->group(function () { ... });

    // ---------- Módulo 4: Citas, donaciones y campañas (Mollo) ----------
});
