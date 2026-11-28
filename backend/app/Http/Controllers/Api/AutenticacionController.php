<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donante;
use App\Models\Hospital;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\Reglas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Módulo 1: Autenticación (Mayerli)
 *
 * Funciona como la portería de un edificio:
 *  - registro: te inscribes y te dan tu credencial (token)
 *  - login: muestras correo y contraseña y te dan una credencial nueva
 *  - logout: devuelves la credencial y deja de servir
 */
class AutenticacionController extends Controller
{
    /**
     * POST /api/registro/donante
     * Crea el usuario (rol donante) y su perfil de donante en una sola operación.
     */
    public function registrarDonante(Request $request): JsonResponse
    {
        $datos = $request->validate([
            ...Reglas::donante(),
            'password' => Reglas::password(),
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'carnet_donante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ], Reglas::mensajes());

        // Archivos: la foto se puede mostrar (disco public); el carnet es privado (disco local)
        $fotoRuta = $request->file('foto')?->store('fotos', 'public');
        $carnetRuta = $request->file('carnet_donante')?->store('carnets', 'local');

        $rolDonante = Rol::where('nombre', Rol::DONANTE)->firstOrFail();

        // Transacción: si falla la creación del donante, tampoco se guarda el usuario.
        $usuario = DB::transaction(function () use ($datos, $rolDonante, $fotoRuta, $carnetRuta) {
            $usuarioNuevo = Usuario::create([
                'rol_id' => $rolDonante->id,
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'password' => $datos['password'],   // el modelo la cifra automáticamente
                'estado' => 'activo',
            ]);

            Donante::create([
                'usuario_id' => $usuarioNuevo->id,
                'tipo_sangre_id' => $datos['tipo_sangre_id'],
                'apellidos' => $datos['apellidos'],
                'ci' => $datos['ci'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'sexo' => $datos['sexo'],
                'peso_kg' => $datos['peso_kg'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
                'zona' => $datos['zona'] ?? null,
                'latitud' => $datos['latitud'] ?? null,
                'longitud' => $datos['longitud'] ?? null,
                'disponible' => true,
                'fecha_ultima_donacion' => $datos['fecha_ultima_donacion'] ?? null,
                'foto_ruta' => $fotoRuta,
                'carnet_ruta' => $carnetRuta,
            ]);

            return $usuarioNuevo;
        });

        $token = $usuario->createToken('donavida')->plainTextToken;

        return response()->json([
            'mensaje' => 'Registro exitoso. ¡Gracias por querer donar!',
            'token' => $token,
            'usuario' => $this->cargarDatosUsuario($usuario),
        ], 201);
    }

    /**
     * POST /api/registro/hospital
     * Crea el usuario (rol hospital) en estado "pendiente".
     * No recibe token: debe esperar a que el administrador lo valide.
     */
    public function registrarHospital(Request $request): JsonResponse
    {
        $datos = $request->validate([
            ...Reglas::hospital(),
            'password' => Reglas::password(),
        ], Reglas::mensajes());

        $rolHospital = Rol::where('nombre', Rol::HOSPITAL)->firstOrFail();

        $usuario = DB::transaction(function () use ($datos, $rolHospital) {
            $usuarioNuevo = Usuario::create([
                'rol_id' => $rolHospital->id,
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'password' => $datos['password'],
                'estado' => 'pendiente',   // espera la validación del administrador
            ]);

            Hospital::create([
                'usuario_id' => $usuarioNuevo->id,
                'razon_social' => $datos['razon_social'],
                'nit' => $datos['nit'],
                'direccion' => $datos['direccion'],
                'zona' => $datos['zona'] ?? null,
                'latitud' => $datos['latitud'],
                'longitud' => $datos['longitud'],
                'telefono_contacto' => $datos['telefono_contacto'],
                'whatsapp' => $datos['whatsapp'] ?? null,
                'verificado' => false,
            ]);

            return $usuarioNuevo;
        });

        return response()->json([
            'mensaje' => 'Solicitud de registro enviada. Un administrador revisará los datos del hospital.',
            'usuario' => $this->cargarDatosUsuario($usuario),
        ], 201);
    }

    /**
     * POST /api/login
     */
    public function iniciarSesion(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = Usuario::where('email', $datos['email'])->first();

        // Mismo mensaje si no existe el correo o si la contraseña está mal:
        // así nadie puede averiguar qué correos están registrados.
        if ($usuario === null || Hash::check($datos['password'], $usuario->password) === false) {
            return response()->json([
                'mensaje' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        if ($usuario->estado === 'pendiente') {
            return response()->json([
                'mensaje' => 'Tu cuenta está en revisión. Un administrador debe validarla.',
            ], 403);
        }

        if ($usuario->estado === 'inactivo') {
            return response()->json([
                'mensaje' => 'Tu cuenta está desactivada. Comunícate con el administrador.',
            ], 403);
        }

        $token = $usuario->createToken('donavida')->plainTextToken;

        return response()->json([
            'mensaje' => 'Bienvenido(a), ' . $usuario->nombre,
            'token' => $token,
            'usuario' => $this->cargarDatosUsuario($usuario),
        ]);
    }

    /**
     * POST /api/logout   (requiere token)
     * Borra solo el token con el que se hizo la petición.
     */
    public function cerrarSesion(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * GET /api/yo   (requiere token)
     * Devuelve los datos del usuario que inició sesión.
     */
    public function usuarioActual(Request $request): JsonResponse
    {
        return response()->json([
            'usuario' => $this->cargarDatosUsuario($request->user()),
        ]);
    }

    /**
     * Carga el rol y, según el rol, el perfil de donante o los datos del hospital.
     */
    private function cargarDatosUsuario(Usuario $usuario): Usuario
    {
        $usuario->load('rol');

        if ($usuario->tieneRol(Rol::DONANTE)) {
            $usuario->load('donante.tipoSangre');
        }

        if ($usuario->tieneRol(Rol::HOSPITAL)) {
            $usuario->load('hospital');
        }

        return $usuario;
    }
}
