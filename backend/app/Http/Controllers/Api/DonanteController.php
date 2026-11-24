<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPerfilDonanteRequest;
use App\Models\Donacion;
use App\Models\Donante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Módulo 2: Donantes (Maya)
 *
 * Todas las rutas usan el token del donante que inició sesión:
 * nadie puede ver ni editar el perfil de otra persona, porque el donante
 * siempre se obtiene del usuario dueño del token ($request->user()).
 */
class DonanteController extends Controller
{
    /**
     * GET /api/donante/perfil
     * Datos del donante con su tipo de sangre, su edad y si puede donar hoy.
     */
    public function verPerfil(Request $request): JsonResponse
    {
        $donante = $this->donanteActual($request);

        return response()->json([
            'perfil' => $this->formatearPerfil($donante),
        ]);
    }

    /**
     * PUT /api/donante/perfil
     * Edita los datos del donante. Solo cambia los campos que se envían.
     */
    public function actualizarPerfil(ActualizarPerfilDonanteRequest $request): JsonResponse
    {
        $donante = $this->donanteActual($request);
        $datos = $request->validated();

        // nombre y teléfono están en la tabla usuarios; el resto en la tabla donantes.
        $camposUsuario = array_intersect_key($datos, array_flip(['nombre', 'telefono']));
        $camposDonante = array_diff_key($datos, $camposUsuario);

        // Transacción: se guardan las dos tablas o ninguna.
        DB::transaction(function () use ($donante, $camposUsuario, $camposDonante) {
            if ($camposUsuario !== []) {
                $donante->usuario->update($camposUsuario);
            }

            if ($camposDonante !== []) {
                $donante->update($camposDonante);
            }
        });

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente.',
            'perfil' => $this->formatearPerfil($donante->fresh()),
        ]);
    }

    /**
     * GET /api/donante/puedo-donar
     * Aplica la regla de los 3 meses con Donante::puedeDonarHoy().
     */
    public function puedoDonar(Request $request): JsonResponse
    {
        $donante = $this->donanteActual($request);
        $puedeDonar = $donante->puedeDonarHoy();
        $proximaFecha = $donante->proximaFechaParaDonar();

        if ($donante->fecha_ultima_donacion === null) {
            $mensaje = '¡Puedes donar! Aún no tienes donaciones registradas.';
        } elseif ($puedeDonar) {
            $mensaje = '¡Puedes donar! Ya pasaron ' . Donante::MESES_ENTRE_DONACIONES . ' meses desde tu última donación.';
        } else {
            $mensaje = 'Todavía no puedes donar. Podrás hacerlo desde el '
                . $proximaFecha->format('d/m/Y')
                . ' (faltan ' . $donante->diasParaPoderDonar() . ' días).';
        }

        return response()->json([
            'puede_donar' => $puedeDonar,
            'fecha_ultima_donacion' => $donante->fecha_ultima_donacion?->toDateString(),
            'proxima_fecha_para_donar' => $proximaFecha?->toDateString(),
            'dias_restantes' => $donante->diasParaPoderDonar(),
            'meses_entre_donaciones' => Donante::MESES_ENTRE_DONACIONES,
            'mensaje' => $mensaje,
        ]);
    }

    /**
     * GET /api/donante/historial
     * Donaciones del donante (la más reciente primero) y su insignia "Héroe nivel X".
     * Las donaciones anuladas se muestran, pero no cuentan para la insignia.
     */
    public function historial(Request $request): JsonResponse
    {
        $donante = $this->donanteActual($request);

        $donaciones = $donante->donaciones()
            ->with(['cita.solicitud.hospital', 'cita.campana.hospital'])
            ->orderByDesc('fecha_donacion')
            ->get();

        $donacionesValidas = $donaciones->where('estado', 'valida');

        return response()->json([
            'insignia' => Donante::calcularInsignia($donacionesValidas->count()),
            'total_donaciones' => $donacionesValidas->count(),
            'total_ml' => (int) $donacionesValidas->sum('volumen_ml'),
            'donaciones' => $donaciones->map(fn (Donacion $donacion) => $this->formatearDonacion($donacion))->values(),
        ]);
    }

    // ---------------------------------------------------------------------
    // Métodos de apoyo
    // ---------------------------------------------------------------------

    /** El perfil de donante del usuario dueño del token. */
    private function donanteActual(Request $request): Donante
    {
        $donante = $request->user()->donante;

        // No debería pasar (el registro crea siempre el perfil), pero por si acaso.
        abort_if($donante === null, 404, 'No se encontró tu perfil de donante.');

        return $donante;
    }

    private function formatearPerfil(Donante $donante): array
    {
        $donante->loadMissing(['usuario', 'tipoSangre']);

        return [
            'id' => $donante->id,
            'nombre' => $donante->usuario->nombre,
            'apellidos' => $donante->apellidos,
            'email' => $donante->usuario->email,
            'telefono' => $donante->usuario->telefono,
            'ci' => $donante->ci,
            'fecha_nacimiento' => $donante->fecha_nacimiento->toDateString(),
            'edad' => $donante->edad(),
            'sexo' => $donante->sexo,
            'peso_kg' => $this->aNumero($donante->peso_kg),
            'tipo_sangre' => [
                'id' => $donante->tipoSangre->id,
                'codigo' => $donante->tipoSangre->codigo,
            ],
            'direccion' => $donante->direccion,
            'zona' => $donante->zona,
            'latitud' => $this->aNumero($donante->latitud),
            'longitud' => $this->aNumero($donante->longitud),
            'disponible' => $donante->disponible,
            'fecha_ultima_donacion' => $donante->fecha_ultima_donacion?->toDateString(),
            'puede_donar_hoy' => $donante->puedeDonarHoy(),
        ];
    }

    /** Las columnas decimal llegan como texto ("-17.3935000"); el frontend las necesita como número. */
    private function aNumero(?string $valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }

    private function formatearDonacion(Donacion $donacion): array
    {
        $cita = $donacion->cita;

        // La cita viene de una solicitud urgente o de una campaña.
        if ($cita?->solicitud !== null) {
            $origen = 'Solicitud urgente';
            $hospital = $cita->solicitud->hospital?->razon_social;
        } elseif ($cita?->campana !== null) {
            $origen = 'Campaña: ' . $cita->campana->nombre;
            $hospital = $cita->campana->hospital?->razon_social;
        } else {
            $origen = null;
            $hospital = null;
        }

        return [
            'id' => $donacion->id,
            'fecha_donacion' => $donacion->fecha_donacion->toDateTimeString(),
            'volumen_ml' => $donacion->volumen_ml,
            'estado' => $donacion->estado,
            'observaciones' => $donacion->observaciones,
            'origen' => $origen,
            'hospital' => $hospital,
        ];
    }
}
