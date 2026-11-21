<?php

namespace Tests\Feature;

use App\Models\Solicitud;
use App\Models\TipoSangre;
use App\Models\Usuario;
use Database\Seeders\DatosPruebaSeeder;
use Database\Seeders\RolSeeder;
use Database\Seeders\TipoSangreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Laravel\Sanctum\Sanctum;
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
