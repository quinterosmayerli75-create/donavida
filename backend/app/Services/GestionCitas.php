<?php

namespace App\Services;

use App\Mail\ConfirmacionCita;
use App\Models\Campana;
use App\Models\Cita;
use App\Models\Donacion;
use App\Models\Donante;
use App\Models\Solicitud;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GestionCitas
{
    public function reservar(Donante $perfil, array $datos): Cita
    {
        return DB::transaction(function () use ($perfil, $datos) {
            // El mismo orden de bloqueo se usa al registrar y cancelar: donante, origen, cita.
            $donante = Donante::lockForUpdate()->findOrFail($perfil->id);
            $this->verificarDonante($donante);
            abort_if($donante->citas()->whereIn('estado', Cita::RESERVADAS)->exists(), 409,
                'Ya tienes una cita pendiente o confirmada. Cancélala antes de reservar otra.');

            $fecha = CarbonImmutable::parse($datos['fecha_hora'])->utc();
            abort_unless($fecha->isFuture(), 422, 'El horario debe ser futuro.');

            if (! empty($datos['solicitud_id'])) {
                $origen = Solicitud::lockForUpdate()->findOrFail($datos['solicitud_id']);
                abort_unless($origen->estado === Solicitud::ABIERTA && $origen->fecha_limite->isFuture(),
                    409, 'La solicitud ya no acepta citas.');
                abort_if($fecha->greaterThan($origen->fecha_limite), 422, 'El horario supera la fecha límite de la solicitud.');
                abort_unless(in_array($donante->tipoSangre->codigo, $origen->tipoSangre->puede_recibir_de, true),
                    422, 'Tu tipo de sangre no es compatible con la solicitud.');
                $origenDatos = ['solicitud_id' => $origen->id];
            } else {
                $origen = Campana::lockForUpdate()->findOrFail($datos['campana_id']);
                abort_unless(in_array($origen->estado, ['programada', 'en_curso'], true) && $origen->fin()->isFuture(),
                    409, 'La campaña ya no acepta citas.');
                abort_unless($fecha->greaterThanOrEqualTo($origen->inicio()) && $fecha->lessThan($origen->fin()),
                    422, 'El horario debe estar dentro de la campaña (fin excluido).');
                // Lectura actual: en MySQL REPEATABLE READ un COUNT normal podría usar
                // una instantánea anterior a la espera por el bloqueo de la campaña.
                abort_if($origen->citas()->where('estado', '!=', 'cancelada')->lockForUpdate()->get(['id'])->count() >= $origen->cupos,
                    409, 'La campaña ya no tiene cupos disponibles.');
                $origenDatos = ['campana_id' => $origen->id];
            }

            abort_unless($origen->hospital->verificado && $origen->hospital->usuario->estado === 'activo',
                409, 'El hospital no está activo y verificado.');

            $cita = $donante->citas()->create([
                ...$origenDatos,
                'fecha_hora' => $fecha,
                'estado' => 'confirmada',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            // Si la transacción falla no se envía correo; el worker puede reintentar un fallo SMTP.
            Mail::to($donante->usuario->email)->queue((new ConfirmacionCita($cita))->afterCommit());

            return $cita->load(['solicitud.tipoSangre', 'campana']);
        }, 3);
    }

    public function cancelar(Cita $referencia): Cita
    {
        return DB::transaction(function () use ($referencia) {
            $cita = $this->bloquear($referencia);
            if ($cita->estado === 'cancelada') {
                return $cita;
            }

            abort_unless(in_array($cita->estado, Cita::RESERVADAS, true) && $cita->fecha_hora->isFuture(),
                409, 'Solo puedes cancelar una cita futura pendiente o confirmada.');
            $cita->update(['estado' => 'cancelada']);

            return $cita;
        }, 3);
    }

    public function asistencia(Cita $referencia, string $estado): Cita
    {
        return DB::transaction(function () use ($referencia, $estado) {
            $cita = $this->bloquear($referencia);
            if ($cita->estado === $estado) {
                return $cita;
            }

            abort_unless(in_array($cita->estado, Cita::RESERVADAS, true),
                409, 'La asistencia solo se registra una vez en una cita pendiente o confirmada.');
            abort_if($cita->fecha_hora->isFuture(), 409, 'Todavía no llegó el horario de la cita.');
            $origen = $cita->solicitud ?? $cita->campana;
            abort_if($origen->estado === 'cancelada', 409, 'El origen de la cita está cancelado.');
            $cita->update(['estado' => $estado]);

            return $cita;
        }, 3);
    }

    public function registrarDonacion(Cita $referencia, array $datos): Donacion
    {
        return DB::transaction(function () use ($referencia, $datos) {
            $cita = $this->bloquear($referencia);
            abort_if($cita->donacion()->exists(), 409, 'Esta cita ya tiene una donación registrada.');
            abort_unless($cita->estado === 'asistio' && ! $cita->fecha_hora->isFuture(),
                409, 'Primero debes registrar la asistencia del donante.');

            $donante = $cita->donante;
            abort_unless($donante->puedeDonarHoy(), 409, 'Todavía no pasaron tres meses desde la última donación.');
            $solicitud = $cita->solicitud;
            if ($solicitud) {
                abort_unless(in_array($solicitud->estado, [Solicitud::ABIERTA, Solicitud::CUBIERTA], true),
                    409, 'La solicitud está cancelada o cerrada.');
                abort_unless(in_array($donante->tipoSangre->codigo, $solicitud->tipoSangre->puede_recibir_de, true),
                    422, 'El tipo de sangre del donante ya no es compatible.');
            } else {
                abort_if($cita->campana->estado === 'cancelada', 409, 'La campaña está cancelada.');
            }

            $fecha = CarbonImmutable::now('UTC');
            $donacion = $cita->donacion()->create([
                'donante_id' => $donante->id,
                'fecha_donacion' => $fecha,
                'volumen_ml' => $datos['volumen_ml'] ?? 450,
                'estado' => 'valida',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
            $donante->update(['fecha_ultima_donacion' => $fecha->toDateString()]);

            if ($solicitud) {
                abort_if($solicitud->unidades_confirmadas >= 65535, 409, 'Se alcanzó el límite de unidades registrables.');
                $solicitud->unidades_confirmadas++;
                if ($solicitud->unidades_confirmadas >= $solicitud->unidades_requeridas) {
                    $solicitud->estado = Solicitud::CUBIERTA;
                }
                $solicitud->save();
            }

            return $donacion->load('cita');
        }, 3);
    }

    private function verificarDonante(Donante $donante): void
    {
        abort_unless($donante->usuario->estado === 'activo', 403, 'La cuenta del donante debe estar activa.');
        abort_unless($donante->disponible, 409, 'Debes activar tu disponibilidad antes de reservar.');
        abort_unless($donante->puedeDonarHoy(), 409, 'Todavía no pasaron tres meses desde la última donación.');
        abort_unless($donante->edad() >= Donante::EDAD_MINIMA && $donante->edad() <= Donante::EDAD_MAXIMA,
            422, 'La edad del donante debe estar entre 18 y 60 años.');
    }

    private function bloquear(Cita $referencia): Cita
    {
        $donante = Donante::lockForUpdate()->findOrFail($referencia->donante_id);
        if ($referencia->solicitud_id !== null) {
            $origen = Solicitud::lockForUpdate()->findOrFail($referencia->solicitud_id);
            $relacion = 'solicitud';
        } else {
            $origen = Campana::lockForUpdate()->findOrFail($referencia->campana_id);
            $relacion = 'campana';
        }

        return Cita::lockForUpdate()->findOrFail($referencia->id)
            ->setRelation('donante', $donante)->setRelation($relacion, $origen);
    }
}
