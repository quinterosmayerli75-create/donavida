<?php

namespace Tests\Feature;

use App\Mail\ConfirmacionCita;
use App\Models\Cita;
use App\Models\Donante;
use App\Models\TipoSangre;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\Modulo4TestCase;

class CitasDonacionesApiTest extends Modulo4TestCase
{
    public function test_las_rutas_exigen_token_rol_y_hospital_verificado(): void
    {
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertUnauthorized();
        $this->getJson('/api/hospital/citas')->assertUnauthorized();
        $this->actuar('central');
        $this->getJson('/api/donante/citas')->assertForbidden();
        $this->actuar('ana');
        $this->getJson('/api/hospital/citas')->assertForbidden();
        $this->actuar('norte');
        $this->getJson('/api/hospital/citas')->assertForbidden();
        $this->getJson('/api/hospital/donaciones')->assertForbidden();
    }

    public function test_reserva_compatible_guarda_utc_y_encola_confirmacion(): void
    {
        $ana = $this->actuar('ana');
        $respuesta = $this->postJson('/api/donante/citas', $this->reservaSolicitud([
            'fecha_hora' => '2026-11-25T10:00:00-04:00',
            'donante_id' => $this->usuario('luis')->donante->id,
            'estado' => 'asistio',
        ]))->assertCreated()->assertJsonPath('cita.estado', 'confirmada')
            ->assertJsonPath('cita.donante_id', $ana->donante->id)
            ->assertJsonPath('cita.fecha_hora', '2026-11-25T14:00:00.000000Z');
        $this->assertDatabaseHas('citas', ['id' => $respuesta->json('cita.id'), 'fecha_hora' => '2026-11-25 14:00:00']);
        Mail::assertQueued(ConfirmacionCita::class, fn ($mail) => $mail->hasTo($ana->email) && $mail->afterCommit);
        $mail = new ConfirmacionCita(Cita::findOrFail($respuesta->json('cita.id')));
        $this->assertStringContainsString('25/11/2026 10:00', $mail->render());
    }

    public function test_exige_exactamente_un_origen_y_un_horario_futuro(): void
    {
        $this->actuar('ana');
        $this->postJson('/api/donante/citas', ['fecha_hora' => now()->addHour()->toISOString()])
            ->assertUnprocessable()->assertJsonValidationErrors(['solicitud_id', 'campana_id']);
        $this->postJson('/api/donante/citas', [...$this->reservaSolicitud(), 'campana_id' => $this->campana()->id])->assertUnprocessable();
        $this->postJson('/api/donante/citas', $this->reservaSolicitud(['solicitud_id' => null, 'campana_id' => null]))->assertUnprocessable();
        $this->postJson('/api/donante/citas', $this->reservaSolicitud(['solicitud_id' => 999]))->assertUnprocessable();
        $this->postJson('/api/donante/citas', $this->reservaSolicitud(['fecha_hora' => now()->subHour()->toISOString()]))->assertUnprocessable();
        $this->assertDatabaseCount('citas', 0);
        Mail::assertNothingQueued();
    }

    public function test_no_reserva_si_no_pasaron_tres_meses_aunque_la_cita_sea_futura(): void
    {
        $this->actuar('luis');
        $this->postJson('/api/donante/citas', $this->reservaCampana())->assertConflict();
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_permite_reserva_en_el_dia_exacto_de_tres_meses(): void
    {
        $ana = $this->actuar('ana');
        $ana->donante->update(['fecha_ultima_donacion' => now()->subMonthsNoOverflow(3)->toDateString()]);
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertCreated();
    }

    public function test_rechaza_incompatibilidad_indisponibilidad_y_edad_invalida(): void
    {
        $this->actuar('diego');
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertUnprocessable();
        $ana = $this->actuar('ana');
        $ana->donante->update(['disponible' => false]);
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertConflict();
        $ana->donante->update(['disponible' => true, 'fecha_nacimiento' => now()->subYears(17)]);
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertUnprocessable();
        $ana->donante->update(['fecha_nacimiento' => now()->subYears(61)]);
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertUnprocessable();
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_cuenta_inactiva_no_puede_reservar_ni_listar(): void
    {
        $ana = $this->actuar('ana');
        $ana->update(['estado' => 'inactivo']);
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertForbidden();
        $this->getJson('/api/donante/citas')->assertForbidden();
        $this->getJson('/api/donante/solicitudes')->assertForbidden();
        $this->getJson('/api/donante/campanas')->assertForbidden();
    }

    public function test_rechaza_solicitudes_cerradas_vencidas_y_horarios_fuera_del_limite(): void
    {
        $this->actuar('ana');
        foreach (['cubierta', 'cerrada', 'cancelada'] as $estado) {
            $this->solicitud()->update(['estado' => $estado]);
            $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertConflict();
        }
        $this->solicitud()->update(['estado' => 'abierta', 'fecha_limite' => now()->subMinute()]);
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertConflict();
        $this->solicitud()->update(['fecha_limite' => now()->addHour()]);
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertUnprocessable();
    }

    public function test_no_reserva_en_un_hospital_desactivado_o_no_verificado(): void
    {
        $this->actuar('ana');
        $this->usuario('central')->hospital->update(['verificado' => false]);
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertConflict();
        $this->usuario('central')->hospital->update(['verificado' => true]);
        $this->usuario('central')->update(['estado' => 'inactivo']);
        $this->postJson('/api/donante/citas', $this->reservaCampana())->assertConflict();
    }

    public function test_solo_permite_una_reserva_activa_y_cancelar_libera_el_cupo(): void
    {
        $this->campana()->update(['cupos' => 1]);
        $this->actuar('ana');
        $id = $this->postJson('/api/donante/citas', $this->reservaCampana())->assertCreated()->json('cita.id');
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertConflict();
        $this->actuar('sofia');
        $this->postJson('/api/donante/citas', $this->reservaCampana())->assertConflict();
        $this->actuar('ana');
        $this->patchJson('/api/donante/citas/'.$id.'/cancelar')->assertOk()->assertJsonPath('cita.estado', 'cancelada');
        $this->patchJson('/api/donante/citas/'.$id.'/cancelar')->assertOk();
        $this->actuar('sofia');
        $this->postJson('/api/donante/citas', $this->reservaCampana())->assertCreated();
        $this->assertDatabaseCount('citas', 2);
    }

    public function test_horario_de_campana_incluye_inicio_y_excluye_fin(): void
    {
        $this->actuar('ana');
        $datos = $this->reservaCampana();
        $this->postJson('/api/donante/citas', [...$datos, 'fecha_hora' => $this->campana()->inicio()->subMinute()->toISOString()])->assertUnprocessable();
        $this->postJson('/api/donante/citas', [...$datos, 'fecha_hora' => $this->campana()->fin()->toISOString()])->assertUnprocessable();
        $this->postJson('/api/donante/citas', [...$datos, 'fecha_hora' => $this->campana()->inicio()->toISOString()])->assertCreated();
    }

    public function test_no_reserva_en_campana_finalizada_cancelada_o_vencida(): void
    {
        $this->actuar('ana');
        foreach (['finalizada', 'cancelada'] as $estado) {
            $this->campana()->update(['estado' => $estado]);
            $this->postJson('/api/donante/citas', $this->reservaCampana())->assertConflict();
        }
        $datos = $this->reservaCampana();
        $this->campana()->update(['estado' => 'programada', 'fecha' => now()->subDay()->toDateString()]);
        $this->postJson('/api/donante/citas', $datos)->assertConflict();
    }

    public function test_donante_y_hospital_solo_ven_y_modifican_sus_citas(): void
    {
        $this->actuar('ana');
        $id = $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertCreated()->json('cita.id');
        $this->actuar('sofia');
        $this->getJson('/api/donante/citas')->assertOk()->assertJsonCount(0, 'citas');
        $this->patchJson('/api/donante/citas/'.$id.'/cancelar')->assertNotFound();
        $norte = $this->usuario('norte');
        $norte->update(['estado' => 'activo']);
        $norte->hospital->update(['verificado' => true]);
        $this->actuar('norte');
        $this->getJson('/api/hospital/citas')->assertOk()->assertJsonCount(0, 'citas');
        $this->patchJson('/api/hospital/citas/'.$id.'/asistencia', ['estado' => 'asistio'])->assertNotFound();
        $this->patchJson('/api/hospital/citas/'.$id.'/cancelar')->assertNotFound();
        $this->postJson('/api/hospital/citas/'.$id.'/donacion')->assertNotFound();
        $this->actuar('central');
        $this->getJson('/api/hospital/citas')->assertOk()->assertJsonCount(1, 'citas');
        $this->patchJson('/api/hospital/citas/'.$id.'/cancelar')->assertOk();
    }

    public function test_agenda_filtra_por_fecha_utc_y_estado_y_valida_el_rango(): void
    {
        $cita = $this->citaPasada();
        $this->actuar('central');
        $this->getJson('/api/hospital/citas?desde=2026-11-24&hasta=2026-11-24&estado=confirmada')
            ->assertOk()->assertJsonCount(1, 'citas')->assertJsonPath('citas.0.id', $cita->id);
        $this->getJson('/api/hospital/citas?hasta=2026-11-23')->assertOk()->assertJsonCount(0, 'citas');
        $this->getJson('/api/hospital/citas?desde=2026-11-25&hasta=2026-11-24')->assertUnprocessable();
        $this->getJson('/api/hospital/citas?estado=desconocido')->assertUnprocessable();
    }

    public function test_asistencia_solo_una_vez_y_despues_del_horario(): void
    {
        $cita = $this->citaPasada();
        $futura = $this->citaPasada('sofia', ['fecha_hora' => now()->addHour()]);
        $this->actuar('central');
        $url = '/api/hospital/citas/'.$cita->id.'/asistencia';
        $this->patchJson($url, ['estado' => 'otro'])->assertUnprocessable();
        $this->patchJson('/api/hospital/citas/'.$futura->id.'/asistencia', ['estado' => 'asistio'])->assertConflict();
        $this->patchJson($url, ['estado' => 'no_asistio'])->assertOk()->assertJsonPath('cita.estado', 'no_asistio');
        $this->patchJson($url, ['estado' => 'no_asistio'])->assertOk();
        $this->patchJson($url, ['estado' => 'asistio'])->assertConflict();
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertConflict();
    }

    public function test_no_se_puede_cancelar_una_cita_pasada(): void
    {
        $cita = $this->citaPasada();
        $this->actuar('ana');
        $this->patchJson('/api/donante/citas/'.$cita->id.'/cancelar')->assertConflict();
    }

    public function test_donacion_actualiza_historial_fecha_unidades_y_cubre_solicitud(): void
    {
        $this->solicitud()->update(['unidades_requeridas' => 1]);
        $cita = $this->citaPasada();
        $this->actuar('central');
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertConflict();
        $this->patchJson('/api/hospital/citas/'.$cita->id.'/asistencia', ['estado' => 'asistio'])->assertOk();
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion', [
            'volumen_ml' => 450, 'donante_id' => $this->usuario('luis')->donante->id,
            'fecha_donacion' => '2000-01-01', 'estado' => 'anulada',
        ])->assertCreated()->assertJsonPath('donacion.estado', 'valida')
            ->assertJsonPath('donacion.fecha_donacion', '2026-11-24T12:00:00.000000Z');
        $this->assertDatabaseHas('solicitudes', ['id' => $this->solicitud()->id, 'estado' => 'cubierta', 'unidades_confirmadas' => 1]);
        $this->assertDatabaseHas('donantes', ['id' => $cita->donante_id, 'fecha_ultima_donacion' => '2026-11-24 00:00:00']);
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertConflict();
        $this->assertDatabaseCount('donaciones', 1);
        $this->actuar('ana');
        $this->getJson('/api/donante/historial')->assertOk()->assertJsonCount(1, 'donaciones');
        $this->getJson('/api/donante/puedo-donar')->assertOk()->assertJsonPath('puede_donar', false);
    }

    public function test_no_registra_donacion_si_no_asistio_cancelada_o_ya_dono_recientemente(): void
    {
        $this->actuar('central');
        foreach (['no_asistio', 'cancelada', 'confirmada'] as $estado) {
            $cita = $this->citaPasada('ana', ['estado' => $estado]);
            $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertConflict();
        }
        $cita = $this->citaPasada('luis', ['estado' => 'asistio']);
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertConflict();
        $this->assertDatabaseCount('donaciones', 0);
    }

    public function test_no_registra_dos_donaciones_del_mismo_donante_en_distintas_citas(): void
    {
        $primera = $this->citaPasada('ana', ['estado' => 'asistio']);
        $segunda = $this->citaPasada('ana', ['estado' => 'asistio']);
        $this->actuar('central');
        $this->postJson('/api/hospital/citas/'.$primera->id.'/donacion')->assertCreated();
        $this->postJson('/api/hospital/citas/'.$segunda->id.'/donacion')->assertConflict();
        $this->assertDatabaseCount('donaciones', 1);
        $this->assertSame(1, $this->solicitud()->unidades_confirmadas);
    }

    public function test_donacion_de_campana_no_modifica_unidades_de_solicitud(): void
    {
        $cita = $this->citaPasada('ana', ['solicitud_id' => null, 'campana_id' => $this->campana()->id, 'estado' => 'asistio']);
        $this->actuar('central');
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertCreated();
        $this->assertSame(0, $this->solicitud()->unidades_confirmadas);
        $this->getJson('/api/hospital/donaciones')->assertOk()->assertJsonCount(1, 'donaciones');
    }

    public function test_donacion_valida_volumen_y_revalida_compatibilidad_y_origen(): void
    {
        $cita = $this->citaPasada('ana', ['estado' => 'asistio']);
        $this->actuar('central');
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion', ['volumen_ml' => 0])->assertUnprocessable();
        $cita->donante->update(['tipo_sangre_id' => TipoSangre::where('codigo', 'AB+')->firstOrFail()->id]);
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertUnprocessable();
        $this->solicitud()->update(['estado' => 'cancelada']);
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertConflict();
        $this->assertDatabaseCount('donaciones', 0);
    }

    public function test_si_falla_la_actualizacion_del_donante_se_revierte_toda_la_donacion(): void
    {
        $cita = $this->citaPasada('ana', ['estado' => 'asistio']);
        $this->actuar('central');
        $evento = 'eloquent.updating: '.Donante::class;
        Event::listen($evento, fn () => throw new RuntimeException('Fallo simulado al actualizar el donante'));
        try {
            $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertInternalServerError();
        } finally {
            Event::forget($evento);
        }
        $this->assertDatabaseCount('donaciones', 0);
        $this->assertNull($this->usuario('ana')->donante->fecha_ultima_donacion);
        $this->assertSame(0, $this->solicitud()->unidades_confirmadas);
    }
}
