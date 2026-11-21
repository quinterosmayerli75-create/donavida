<?php

namespace Tests\Feature;

use App\Models\Donante;
use App\Models\Notificacion;
use App\Models\Solicitud;
use App\Models\TipoSangre;
use App\Models\Usuario;
use Database\Seeders\DatosPruebaSeeder;
use Database\Seeders\RolSeeder;
use Database\Seeders\TipoSangreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class HospitalSolicitudesApiTest extends TestCase
{
    use RefreshDatabase;

    private const SOLICITUDES = '/api/hospital/solicitudes';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolSeeder::class, TipoSangreSeeder::class, DatosPruebaSeeder::class]);
    }

    public function test_solo_el_hospital_activo_y_verificado_accede_a_las_solicitudes(): void
    {
        $this->getJson(self::SOLICITUDES)->assertUnauthorized();

        Sanctum::actingAs($this->usuario('ana'));
        $this->getJson(self::SOLICITUDES)->assertForbidden();

        Sanctum::actingAs($this->usuario('norte'));
        $this->postJson(self::SOLICITUDES, $this->datosSolicitud())->assertForbidden();
    }

    public function test_el_hospital_solo_puede_gestionar_sus_solicitudes(): void
    {
        $otra = $this->usuario('norte')->hospital->solicitudes()->create($this->datosSolicitud());
        Sanctum::actingAs($this->usuario('central'));
        $urlOtra = self::SOLICITUDES.'/'.$otra->id;

        $this->getJson(self::SOLICITUDES)->assertOk()->assertJsonCount(1, 'solicitudes');
        $this->getJson($urlOtra)->assertNotFound();
        $this->patchJson($urlOtra, ['unidades_requeridas' => 2])->assertNotFound();
        $this->deleteJson($urlOtra)->assertNotFound();
        $this->getJson($urlOtra.'/donantes-compatibles')->assertNotFound();
    }

    public function test_valida_publica_edita_y_cancela_sin_borrar_la_solicitud(): void
    {
        $central = $this->usuario('central');
        Sanctum::actingAs($central);

        $this->postJson(self::SOLICITUDES, [
            'tipo_sangre_id' => 9999,
            'unidades_requeridas' => 0,
            'fecha_limite' => Date::now()->subDay()->toIso8601String(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['tipo_sangre_id', 'unidades_requeridas', 'fecha_limite']);

        $fechaLocal = Date::now('America/La_Paz')->addDays(2)->startOfSecond();
        $id = $this->postJson(self::SOLICITUDES, array_replace($this->datosSolicitud(), [
            'fecha_limite' => $fechaLocal->toIso8601String(),
            'hospital_id' => $this->usuario('norte')->hospital->id,
        ]))->assertCreated()
            ->assertJsonPath('solicitud.hospital_id', $central->hospital->id)
            ->assertJsonPath('solicitud.estado', Solicitud::ABIERTA)
            ->assertJsonPath('solicitud.prioridad', 'alta')
            ->assertJsonPath('solicitud.unidades_confirmadas', 0)
            ->json('solicitud.id');

        $url = self::SOLICITUDES.'/'.$id;
        $this->getJson($url)->assertOk()
            ->assertJsonPath('solicitud.tipo_sangre.codigo', 'A+')
            ->assertJsonPath('solicitud.fecha_limite', $fechaLocal->copy()->utc()->toISOString());

        $this->patchJson($url, [
            'unidades_requeridas' => '5',
            'prioridad' => 'critica',
            'estado' => 'cerrada',
            'unidades_confirmadas' => 5,
        ])->assertOk()
            ->assertJsonPath('solicitud.unidades_requeridas', 5)
            ->assertJsonPath('solicitud.prioridad', 'critica')
            ->assertJsonPath('solicitud.estado', Solicitud::ABIERTA)
            ->assertJsonPath('solicitud.unidades_confirmadas', 0);

        $this->deleteJson($url)->assertOk()->assertJsonPath('solicitud.estado', Solicitud::CANCELADA);
        $this->deleteJson($url)->assertOk()->assertJsonPath('solicitud.estado', Solicitud::CANCELADA);
        $this->patchJson($url, ['unidades_requeridas' => 7])->assertConflict();
        $this->getJson($url)->assertOk()->assertJsonPath('solicitud.estado', Solicitud::CANCELADA);
        $this->getJson($url.'/donantes-compatibles')->assertConflict();
        $this->assertDatabaseHas('solicitudes', ['id' => $id, 'estado' => Solicitud::CANCELADA]);
    }

    public function test_la_edicion_respeta_las_citas_y_las_unidades_confirmadas(): void
    {
        $central = $this->usuario('central');
        $solicitud = $central->hospital->solicitudes()->create($this->datosSolicitud());
        $solicitud->citas()->create([
            'donante_id' => $this->usuario('ana')->donante->id,
            'fecha_hora' => Date::now()->addDay(),
        ]);
        Sanctum::actingAs($central);
        $url = self::SOLICITUDES.'/'.$solicitud->id;

        $this->patchJson($url, ['tipo_sangre_id' => TipoSangre::where('codigo', 'O-')->sole()->id])
            ->assertUnprocessable()->assertJsonValidationErrors('tipo_sangre_id');

        $solicitud->update(['unidades_confirmadas' => 2]);

        $this->patchJson($url, ['unidades_requeridas' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('unidades_requeridas');
        $this->patchJson($url, ['unidades_requeridas' => 2])
            ->assertOk()->assertJsonPath('solicitud.estado', Solicitud::CUBIERTA);
        $this->deleteJson($url)->assertConflict();
    }

    public function test_filtra_y_ordena_donantes_compatibles_por_distancia(): void
    {
        $central = $this->usuario('central');
        $central->hospital->update(['latitud' => 0, 'longitud' => 0]);
        $ana = $this->usuario('ana');
        $ana->donante->update(['latitud' => 2, 'longitud' => 0]);
        $sofia = $this->usuario('sofia');
        $sofia->donante->update(['latitud' => 0, 'longitud' => 1]);

        $usuarioSinUbicacion = $ana->replicate()->fill(['email' => 'sin-ubicacion@donavida.test']);
        $usuarioSinUbicacion->save();
        $sinUbicacion = $ana->donante->replicate()->fill([
            'usuario_id' => $usuarioSinUbicacion->id,
            'ci' => '9000005',
            'latitud' => null,
            'longitud' => null,
        ]);
        $sinUbicacion->save();

        $solicitud = $central->hospital->solicitudes()->create($this->datosSolicitud());
        Sanctum::actingAs($central);
        $url = self::SOLICITUDES.'/'.$solicitud->id.'/donantes-compatibles';

        $this->getJson($url)->assertOk()
            ->assertJsonCount(3, 'donantes')
            ->assertJsonPath('hospital.id', $central->hospital->id)
            ->assertJsonPath('donantes.0.id', $sofia->donante->id)
            ->assertJsonPath('donantes.0.tipo_sangre', 'A+')
            ->assertJsonPath('donantes.0.distancia_km', 111.195)
            ->assertJsonPath('donantes.1.id', $ana->donante->id)
            ->assertJsonPath('donantes.1.distancia_km', 222.39)
            ->assertJsonPath('donantes.2.id', $sinUbicacion->id)
            ->assertJsonPath('donantes.2.distancia_km', null)
            ->assertJsonMissingPath('donantes.0.usuario')
            ->assertJsonMissingPath('donantes.0.ci');

        $ana->update(['estado' => 'inactivo']);
        $sofia->donante->update(['disponible' => false]);
        $this->getJson($url)->assertOk()
            ->assertJsonCount(1, 'donantes')
            ->assertJsonPath('donantes.0.id', $sinUbicacion->id);
    }

    public function test_el_emparejamiento_rechaza_solicitudes_vencidas(): void
    {
        $central = $this->usuario('central');
        $solicitud = $central->hospital->solicitudes()->create(array_replace($this->datosSolicitud(), [
            'fecha_limite' => Date::now()->subDay(),
        ]));
        Sanctum::actingAs($central);

        $this->getJson(self::SOLICITUDES.'/'.$solicitud->id.'/donantes-compatibles')->assertConflict();
    }

    public function test_publicar_crea_un_aviso_por_donante_elegible(): void
    {
        $ana = $this->usuario('ana');
        $ana->donante->update(['latitud' => null, 'longitud' => null]);
        $sofia = $this->usuario('sofia');
        Sanctum::actingAs($this->usuario('central'));

        $id = $this->postJson(self::SOLICITUDES, $this->datosSolicitud())
            ->assertCreated()
            ->assertJsonPath('notificaciones_creadas', 2)
            ->json('solicitud.id');

        $url = self::SOLICITUDES.'/'.$id;
        $this->getJson($url.'/donantes-compatibles')->assertOk();
        $this->patchJson($url, ['unidades_requeridas' => 4])->assertOk();

        $avisos = Notificacion::where('solicitud_id', $id)->get();
        $this->assertEqualsCanonicalizing([$ana->id, $sofia->id], $avisos->pluck('usuario_id')->all());

        Sanctum::actingAs($ana);
        $this->getJson('/api/notificaciones/no-leidas')->assertOk()
            ->assertJsonPath('no_leidas', 1);
        $this->getJson('/api/notificaciones')->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.solicitud_id', $id)
            ->assertJsonPath('0.tipo', 'solicitud_urgente')
            ->assertJsonPath('0.leida', false);
    }

    public function test_se_puede_publicar_cuando_no_hay_donantes_elegibles(): void
    {
        Donante::query()->update(['disponible' => false]);
        Sanctum::actingAs($this->usuario('central'));

        $this->postJson(self::SOLICITUDES, $this->datosSolicitud())
            ->assertCreated()
            ->assertJsonPath('solicitud.estado', Solicitud::ABIERTA)
            ->assertJsonPath('notificaciones_creadas', 0);

        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_publicar_revierte_la_solicitud_y_los_avisos_si_falla_un_guardado(): void
    {
        Sanctum::actingAs($this->usuario('central'));
        $solicitudesPrevias = Solicitud::count();
        $intentos = 0;

        Notificacion::creating(function () use (&$intentos) {
            if (++$intentos === 2) {
                throw new RuntimeException('Error de prueba al guardar el segundo aviso.');
            }
        });

        $this->postJson(self::SOLICITUDES, $this->datosSolicitud())->assertServerError();
        $this->assertSame(2, $intentos);
        $this->assertDatabaseCount('solicitudes', $solicitudesPrevias);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    private function usuario(string $cuenta): Usuario
    {
        return Usuario::where('email', $cuenta.'@donavida.test')->firstOrFail();
    }

    private function datosSolicitud(): array
    {
        return [
            'tipo_sangre_id' => TipoSangre::where('codigo', 'A+')->sole()->id,
            'unidades_requeridas' => 3,
            'fecha_limite' => Date::now()->addDays(2)->toIso8601String(),
        ];
    }
}
