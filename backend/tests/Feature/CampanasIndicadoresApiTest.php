<?php

namespace Tests\Feature;

use App\Models\Donacion;
use Tests\Modulo4TestCase;

class CampanasIndicadoresApiTest extends Modulo4TestCase
{
    private function datosCampana(array $datos = []): array
    {
        return array_replace([
            'nombre' => 'Jornada solidaria', 'lugar' => 'Hospital Central',
            'fecha' => now()->addWeek()->toDateString(),
            'hora_inicio' => '13:00', 'hora_fin' => '17:00', 'cupos' => 2,
        ], $datos);
    }

    public function test_crud_de_campanas_con_cupos_y_cancelacion_logica(): void
    {
        $hospital = $this->actuar('central')->hospital;
        $id = $this->postJson('/api/hospital/campanas', $this->datosCampana(['hospital_id' => 999]))
            ->assertCreated()->assertJsonPath('campana.hospital_id', $hospital->id)
            ->assertJsonPath('campana.estado', 'programada')->assertJsonPath('campana.cupos_disponibles', 2)
            ->assertJsonPath('campana.inicio_utc', '2026-12-01T13:00:00.000000Z')->json('campana.id');
        $this->getJson('/api/hospital/campanas')->assertOk()->assertJsonCount(2, 'campanas');
        $this->getJson('/api/hospital/campanas/'.$id)->assertOk()->assertJsonPath('campana.nombre', 'Jornada solidaria');
        $this->patchJson('/api/hospital/campanas/'.$id, ['nombre' => 'Jornada nueva', 'cupos' => 3])
            ->assertOk()->assertJsonPath('campana.cupos_disponibles', 3);
        $this->deleteJson('/api/hospital/campanas/'.$id)->assertOk()->assertJsonPath('campana.estado', 'cancelada');
        $this->deleteJson('/api/hospital/campanas/'.$id)->assertOk();
        $this->patchJson('/api/hospital/campanas/'.$id, ['nombre' => 'Otra'])->assertConflict();
        $this->assertDatabaseHas('campanas', ['id' => $id, 'nombre' => 'Jornada nueva', 'estado' => 'cancelada']);
    }

    public function test_campanas_exigen_rol_hospital_y_verificacion(): void
    {
        $this->getJson('/api/hospital/campanas')->assertUnauthorized();
        $this->actuar('ana');
        $this->postJson('/api/hospital/campanas', $this->datosCampana())->assertForbidden();
        $this->actuar('norte');
        $this->getJson('/api/hospital/campanas')->assertForbidden();
    }

    public function test_hospital_no_puede_ver_editar_ni_cancelar_campanas_ajenas(): void
    {
        $norte = $this->usuario('norte');
        $otra = $norte->hospital->campanas()->create($this->datosCampana());
        $this->actuar('central');
        $url = '/api/hospital/campanas/'.$otra->id;
        $this->getJson($url)->assertNotFound();
        $this->patchJson($url, ['cupos' => 1])->assertNotFound();
        $this->deleteJson($url)->assertNotFound();
        $this->getJson('/api/hospital/campanas')->assertOk()->assertJsonCount(1, 'campanas');
    }

    public function test_valida_cupos_coordenadas_fecha_y_horas(): void
    {
        $this->actuar('central');
        $this->postJson('/api/hospital/campanas', $this->datosCampana([
            'cupos' => 0, 'fecha' => 'ayer', 'latitud' => 100, 'longitud' => -200,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['cupos', 'fecha', 'latitud', 'longitud']);
        $this->postJson('/api/hospital/campanas', $this->datosCampana(['fecha' => now()->subDay()->toDateString()]))->assertUnprocessable();
        $this->postJson('/api/hospital/campanas', $this->datosCampana(['hora_fin' => '12:00']))->assertUnprocessable();
        $this->postJson('/api/hospital/campanas', $this->datosCampana(['hora_fin' => '13:00']))->assertUnprocessable();
        $this->postJson('/api/hospital/campanas', $this->datosCampana(['estado' => 'finalizada']))->assertUnprocessable();
        $this->patchJson('/api/hospital/campanas/'.$this->campana()->id, ['hora_inicio' => '14:00'])->assertUnprocessable();
    }

    public function test_cupos_no_bajan_de_reservas_ni_se_cambia_su_horario_o_lugar(): void
    {
        $this->actuar('ana');
        $this->postJson('/api/donante/citas', $this->reservaCampana())->assertCreated();
        $this->actuar('sofia');
        $this->postJson('/api/donante/citas', $this->reservaCampana())->assertCreated();
        $this->actuar('central');
        $url = '/api/hospital/campanas/'.$this->campana()->id;
        $this->patchJson($url, ['cupos' => 1])->assertUnprocessable();
        $this->patchJson($url, ['fecha' => now()->addMonth()->toDateString()])->assertUnprocessable();
        $this->patchJson($url, ['hora_inicio' => '08:30'])->assertUnprocessable();
        $this->patchJson($url, ['lugar' => 'Otro lugar'])->assertUnprocessable();
        $this->patchJson($url, ['cupos' => 2])->assertOk()->assertJsonPath('campana.cupos_disponibles', 0);
    }

    public function test_cancelar_campana_cancela_reservas_y_libera_al_donante(): void
    {
        $this->actuar('ana');
        $id = $this->postJson('/api/donante/citas', $this->reservaCampana())->assertCreated()->json('cita.id');
        $this->actuar('central');
        $this->deleteJson('/api/hospital/campanas/'.$this->campana()->id)->assertOk();
        $this->assertDatabaseHas('citas', ['id' => $id, 'estado' => 'cancelada']);
        $this->assertDatabaseCount('citas', 1);
        $this->actuar('ana');
        $this->postJson('/api/donante/citas', $this->reservaSolicitud())->assertCreated();
    }

    public function test_editar_con_reservas_acepta_el_mismo_horario_con_y_sin_segundos(): void
    {
        $this->campana()->update(['hora_inicio' => '08:00:00', 'hora_fin' => '13:00:00']);
        $this->actuar('ana');
        $this->postJson('/api/donante/citas', $this->reservaCampana())->assertCreated();
        $this->actuar('central');
        $url = '/api/hospital/campanas/'.$this->campana()->id;
        $this->patchJson($url, ['hora_inicio' => '08:00', 'hora_fin' => '13:00'])->assertOk();
        $this->patchJson($url, ['hora_inicio' => '08:00:00', 'hora_fin' => '13:00:00'])->assertOk();
        $this->travelTo($this->campana()->inicio()->addHour());
        $this->patchJson($url, ['hora_inicio' => '08:00', 'estado' => 'en_curso'])->assertOk();
    }

    public function test_ciclo_de_estados_de_campana_respeta_su_horario(): void
    {
        $campana = $this->campana();
        $url = '/api/hospital/campanas/'.$campana->id;
        $this->actuar('central');
        $this->patchJson($url, ['estado' => 'en_curso'])->assertConflict();
        $this->patchJson($url, ['estado' => 'finalizada'])->assertConflict();
        $this->travelTo($campana->inicio()->addHour());
        $this->patchJson($url, ['estado' => 'en_curso'])->assertOk();
        $this->patchJson($url, ['estado' => 'programada'])->assertConflict();
        $this->deleteJson($url)->assertConflict();
        $this->travelTo($campana->fin()->addMinute());
        $this->patchJson($url, ['estado' => 'finalizada'])->assertOk();
        $this->patchJson($url, ['nombre' => 'Cambio tardío'])->assertConflict();
    }

    public function test_listado_donante_solo_incluye_solicitudes_compatibles_abiertas_y_vigentes(): void
    {
        $this->actuar('ana');
        $this->getJson('/api/donante/solicitudes')->assertOk()->assertJsonCount(1, 'solicitudes');
        $this->actuar('diego');
        $this->getJson('/api/donante/solicitudes')->assertOk()->assertJsonCount(0, 'solicitudes');
        $this->actuar('ana');
        $this->solicitud()->update(['estado' => 'cubierta']);
        $this->getJson('/api/donante/solicitudes')->assertOk()->assertJsonCount(0, 'solicitudes');
        $this->solicitud()->update(['estado' => 'abierta', 'fecha_limite' => now()->subMinute()]);
        $this->getJson('/api/donante/solicitudes')->assertOk()->assertJsonCount(0, 'solicitudes');
    }

    public function test_listado_donante_excluye_campanas_sin_cupos_vencidas_canceladas_o_hospital_inactivo(): void
    {
        $this->campana()->update(['cupos' => 1]);
        $this->actuar('ana');
        $this->getJson('/api/donante/campanas')->assertOk()->assertJsonCount(1, 'campanas')
            ->assertJsonPath('campanas.0.cupos_disponibles', 1);
        $id = $this->postJson('/api/donante/citas', $this->reservaCampana())->assertCreated()->json('cita.id');
        $this->getJson('/api/donante/campanas')->assertOk()->assertJsonCount(0, 'campanas');
        $this->patchJson('/api/donante/citas/'.$id.'/cancelar')->assertOk();
        $this->usuario('central')->update(['estado' => 'inactivo']);
        $this->getJson('/api/donante/campanas')->assertOk()->assertJsonCount(0, 'campanas');
        $this->usuario('central')->update(['estado' => 'activo']);
        $this->campana()->update(['estado' => 'cancelada']);
        $this->getJson('/api/donante/campanas')->assertOk()->assertJsonCount(0, 'campanas');
        $this->campana()->update(['estado' => 'programada', 'fecha' => now()->toDateString(), 'hora_fin' => '11:00']);
        $this->getJson('/api/donante/campanas')->assertOk()->assertJsonCount(0, 'campanas');
    }

    public function test_indicadores_cuentan_solo_donaciones_validas_y_datos_del_hospital(): void
    {
        $cita = $this->citaPasada('ana', ['estado' => 'asistio']);
        $this->actuar('central');
        $this->postJson('/api/hospital/citas/'.$cita->id.'/donacion')->assertCreated();
        $anulada = $this->citaPasada('sofia', ['estado' => 'asistio']);
        Donacion::create(['cita_id' => $anulada->id, 'donante_id' => $anulada->donante_id,
            'fecha_donacion' => now(), 'volumen_ml' => 450, 'estado' => 'anulada']);

        $hospitalNorte = $this->usuario('norte')->hospital;
        $otra = $hospitalNorte->solicitudes()->create([
            'tipo_sangre_id' => $this->solicitud()->tipo_sangre_id,
            'unidades_requeridas' => 1, 'unidades_confirmadas' => 1,
            'fecha_limite' => now()->addDay(), 'estado' => 'cubierta',
        ]);
        $externa = $this->citaPasada('diego', ['solicitud_id' => $otra->id, 'estado' => 'asistio']);
        Donacion::create(['cita_id' => $externa->id, 'donante_id' => $externa->donante_id,
            'fecha_donacion' => now(), 'volumen_ml' => 400]);

        $respuesta = $this->getJson('/api/hospital/indicadores')->assertOk()
            ->assertJsonPath('indicadores.donaciones_validas', 1)
            ->assertJsonPath('indicadores.volumen_total_ml', 450)
            ->assertJsonPath('indicadores.unidades_confirmadas', 1)
            ->assertJsonPath('indicadores.solicitudes_por_estado.abierta', 1)
            ->assertJsonPath('indicadores.solicitudes_por_estado.cubierta', 0)
            ->assertJsonCount(8, 'indicadores.donaciones_por_tipo_sangre');
        $tipos = collect($respuesta->json('indicadores.donaciones_por_tipo_sangre'))->pluck('total', 'tipo_sangre');
        $this->assertSame(1, $tipos['O-']);
        $this->assertSame(0, $tipos['A+']);
        $this->actuar('admin');
        $this->getJson('/api/admin/indicadores')->assertOk()
            ->assertJsonPath('indicadores.donaciones_validas', 2)
            ->assertJsonPath('indicadores.volumen_total_ml', 850)
            ->assertJsonPath('indicadores.solicitudes_por_estado.cubierta', 1);
    }

    public function test_indicadores_exigen_token_y_rol_y_devuelven_ceros(): void
    {
        $this->getJson('/api/admin/indicadores')->assertUnauthorized();
        $this->actuar('ana');
        $this->getJson('/api/admin/indicadores')->assertForbidden();
        $this->getJson('/api/hospital/indicadores')->assertForbidden();
        $this->actuar('central');
        $this->getJson('/api/admin/indicadores')->assertForbidden();
        $this->getJson('/api/hospital/indicadores')->assertOk()->assertJsonPath('indicadores.donaciones_validas', 0)
            ->assertJsonPath('indicadores.volumen_total_ml', 0);
        $this->actuar('norte');
        $this->getJson('/api/hospital/indicadores')->assertForbidden();
    }
}
