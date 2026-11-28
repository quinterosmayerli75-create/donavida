<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\Donante;
use App\Models\Hospital;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\Reglas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Módulo 1 (Mayerli): pantallas "Usuarios" y "Campañas" del administrador.
 * Solo accesible con el rol "administrador".
 */
class AdministradorController extends Controller
{
    /**
     * GET /api/admin/usuarios
     * GET /api/admin/usuarios?rol=donante   (donante | hospital | administrador)
     * Devuelve la lista de cuentas y cuántas hay de cada rol.
     */
    public function usuarios(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'rol' => ['sometimes', Rule::in([Rol::ADMINISTRADOR, Rol::HOSPITAL, Rol::DONANTE])],
        ]);

        $consulta = Usuario::with(['rol', 'donante.tipoSangre', 'hospital'])->orderBy('created_at', 'desc');
        if (isset($datos['rol'])) {
            $consulta->whereHas('rol', fn ($query) => $query->where('nombre', $datos['rol']));
        }

        $totales = Rol::withCount('usuarios')->pluck('usuarios_count', 'nombre');

        return response()->json([
            'totales' => [
                Rol::DONANTE => (int) ($totales[Rol::DONANTE] ?? 0),
                Rol::HOSPITAL => (int) ($totales[Rol::HOSPITAL] ?? 0),
                Rol::ADMINISTRADOR => (int) ($totales[Rol::ADMINISTRADOR] ?? 0),
            ],
            'usuarios' => $consulta->get()->map(fn (Usuario $usuario) => [
                'id' => $usuario->id,
                'donante_id' => $usuario->donante?->id,
                'hospital_id' => $usuario->hospital?->id,
                'foto_url' => $usuario->donante?->foto_url,
                'nombre' => $usuario->donante
                    ? $usuario->nombre.' '.$usuario->donante->apellidos
                    : ($usuario->hospital->razon_social ?? $usuario->nombre),
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'rol' => $usuario->rol->nombre,
                'tipo_sangre' => $usuario->donante?->tipoSangre?->codigo,
                'estado' => $usuario->estado,
                'created_at' => $usuario->created_at,
            ]),
        ]);
    }

    /**
     * PATCH /api/admin/usuarios/{usuario}/estado   { "estado": "activo" | "inactivo" }
     * Suspende o reactiva la cuenta de un donante o de otro administrador.
     * Los hospitales se validan o desactivan desde /api/admin/hospitales.
     */
    public function cambiarEstado(Request $request, Usuario $usuario): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ]);

        abort_if($usuario->id === $request->user()->id, 422, 'No puedes cambiar el estado de tu propia cuenta.');
        abort_if($usuario->tieneRol(Rol::HOSPITAL), 422, 'Los hospitales se validan o desactivan desde la sección Hospitales.');

        $usuario->estado = $datos['estado'];
        $usuario->save();

        // Si se suspende, se cierran todas sus sesiones abiertas
        if ($datos['estado'] === 'inactivo') {
            $usuario->tokens()->delete();
        }

        return response()->json([
            'mensaje' => $datos['estado'] === 'activo' ? 'Cuenta reactivada.' : 'Cuenta suspendida.',
            'usuario' => ['id' => $usuario->id, 'estado' => $usuario->estado],
        ]);
    }

    /**
     * GET /api/admin/campanas
     * Todas las campañas de la red (de todos los hospitales), las más próximas primero.
     */
    public function campanas(): JsonResponse
    {
        $campanas = Campana::with('hospital:id,razon_social')
            ->withCount(['citas as cupos_ocupados' => fn ($query) => $query->where('estado', '!=', 'cancelada')])
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn (Campana $campana) => CampanaController::presentar($campana));

        return response()->json(['campanas' => $campanas]);
    }

    /**
     * GET /api/admin/donantes/{donante}
     * Datos completos de un donante (para el formulario de edición).
     */
    public function verDonante(Donante $donante): JsonResponse
    {
        return response()->json(['donante' => $this->presentarDonante($donante)]);
    }

    /**
     * POST /api/admin/donantes
     * El administrador registra un donante (por ejemplo, en una campaña presencial).
     */
    public function crearDonante(Request $request): JsonResponse
    {
        $datos = $request->validate([...Reglas::donante(), 'password' => Reglas::password()], Reglas::mensajes());

        $donante = DB::transaction(function () use ($datos) {
            $usuario = Usuario::create([
                'rol_id' => Rol::where('nombre', Rol::DONANTE)->firstOrFail()->id,
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'password' => $datos['password'],
                'estado' => 'activo',
            ]);

            return Donante::create([...$this->camposDonante($datos), 'usuario_id' => $usuario->id, 'disponible' => true]);
        });

        return response()->json(['mensaje' => 'Donante registrado.', 'donante' => $this->presentarDonante($donante)], 201);
    }

    /**
     * PUT /api/admin/donantes/{donante}
     * Edita los datos de un donante. La contraseña es opcional (solo si se quiere cambiar).
     */
    public function actualizarDonante(Request $request, Donante $donante): JsonResponse
    {
        $datos = $request->validate([
            ...Reglas::donante($donante->usuario_id, $donante->id),
            'password' => Reglas::password(false),
        ], Reglas::mensajes());

        DB::transaction(function () use ($datos, $donante) {
            $this->guardarCuenta($donante->usuario, $datos);
            $donante->update($this->camposDonante($datos));
        });

        return response()->json(['mensaje' => 'Donante actualizado.', 'donante' => $this->presentarDonante($donante->refresh())]);
    }

    /**
     * POST /api/admin/hospitales
     * El administrador registra un hospital: queda validado y activo de una vez.
     */
    public function crearHospital(Request $request): JsonResponse
    {
        $datos = $request->validate([...Reglas::hospital(), 'password' => Reglas::password()], Reglas::mensajes());

        $hospital = DB::transaction(function () use ($datos) {
            $usuario = Usuario::create([
                'rol_id' => Rol::where('nombre', Rol::HOSPITAL)->firstOrFail()->id,
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'password' => $datos['password'],
                'estado' => 'activo',
            ]);

            return Hospital::create([...$this->camposHospital($datos), 'usuario_id' => $usuario->id, 'verificado' => true]);
        });

        return response()->json(['mensaje' => 'Hospital registrado y validado.', 'hospital' => $hospital->fresh('usuario')], 201);
    }

    /**
     * PUT /api/admin/hospitales/{hospital}
     * Edita los datos del hospital y de su responsable. La contraseña es opcional.
     */
    public function actualizarHospital(Request $request, Hospital $hospital): JsonResponse
    {
        $datos = $request->validate([
            ...Reglas::hospital($hospital->usuario_id, $hospital->id),
            'password' => Reglas::password(false),
        ], Reglas::mensajes());

        DB::transaction(function () use ($datos, $hospital) {
            $this->guardarCuenta($hospital->usuario, $datos);
            $hospital->update($this->camposHospital($datos));
        });

        return response()->json(['mensaje' => 'Hospital actualizado.', 'hospital' => $hospital->fresh('usuario')]);
    }

    // Nombre, correo, teléfono y (si viene) contraseña nueva de la cuenta
    private function guardarCuenta(Usuario $usuario, array $datos): void
    {
        $usuario->fill(['nombre' => $datos['nombre'], 'email' => $datos['email'], 'telefono' => $datos['telefono']]);
        if (! empty($datos['password'])) {
            $usuario->password = $datos['password'];
        }
        $usuario->save();
    }

    private function camposDonante(array $datos): array
    {
        return collect($datos)->only([
            'tipo_sangre_id', 'apellidos', 'ci', 'fecha_nacimiento', 'sexo', 'peso_kg',
            'direccion', 'zona', 'latitud', 'longitud', 'fecha_ultima_donacion',
        ])->all();
    }

    private function camposHospital(array $datos): array
    {
        return collect($datos)->only([
            'razon_social', 'nit', 'direccion', 'zona', 'latitud', 'longitud', 'telefono_contacto', 'whatsapp',
        ])->all();
    }

    private function presentarDonante(Donante $donante): array
    {
        $donante->load('usuario', 'tipoSangre');

        return [
            'id' => $donante->id,
            'usuario_id' => $donante->usuario_id,
            'nombre' => $donante->usuario->nombre,
            'apellidos' => $donante->apellidos,
            'email' => $donante->usuario->email,
            'telefono' => $donante->usuario->telefono,
            'estado' => $donante->usuario->estado,
            'ci' => $donante->ci,
            'fecha_nacimiento' => $donante->fecha_nacimiento?->toDateString(),
            'sexo' => $donante->sexo,
            'tipo_sangre_id' => $donante->tipo_sangre_id,
            'tipo_sangre' => $donante->tipoSangre->codigo,
            'peso_kg' => $donante->peso_kg,
            'zona' => $donante->zona,
            'direccion' => $donante->direccion,
            'fecha_ultima_donacion' => $donante->fecha_ultima_donacion?->toDateString(),
            'foto_url' => $donante->foto_url,
            'tiene_carnet' => $donante->tiene_carnet,
        ];
    }
}
