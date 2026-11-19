<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donante;
use App\Models\Hospital;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\Carbon;
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
        // Edad permitida para donar: 18 a 60 años (Ley 1687, art. 19)
        $fechaMaximaNacimiento = Carbon::today()->subYears(Donante::EDAD_MINIMA)->toDateString();
        $fechaMinimaNacimiento = Carbon::today()->subYears(Donante::EDAD_MAXIMA + 1)->addDay()->toDateString();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'telefono' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'ci' => ['required', 'string', 'max:20', 'unique:donantes,ci'],
            'fecha_nacimiento' => [
                'required',
                'date',
                'before_or_equal:' . $fechaMaximaNacimiento,
                'after_or_equal:' . $fechaMinimaNacimiento,
            ],
            'sexo' => ['required', 'in:F,M'],
            'tipo_sangre_id' => ['required', 'integer', 'exists:tipos_sangre,id'],
            'peso_kg' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'zona' => ['nullable', 'string', 'max:100'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'fecha_nacimiento.before_or_equal' => 'Debes tener al menos 18 años para registrarte como donante.',
            'fecha_nacimiento.after_or_equal' => 'Solo pueden donar personas de hasta 60 años.',
            'email.unique' => 'Ese correo ya está registrado.',
            'ci.unique' => 'Ese carnet ya está registrado.',
        ]);

        $rolDonante = Rol::where('nombre', Rol::DONANTE)->firstOrFail();

        // Transacción: si falla la creación del donante, tampoco se guarda el usuario.
        $usuario = DB::transaction(function () use ($datos, $rolDonante) {
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
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'telefono' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'razon_social' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:20', 'unique:hospitales,nit'],
            'direccion' => ['required', 'string', 'max:200'],
            'zona' => ['nullable', 'string', 'max:100'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'telefono_contacto' => ['required', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
        ], [
            'email.unique' => 'Ese correo ya está registrado.',
            'nit.unique' => 'Ese NIT ya está registrado.',
        ]);

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
