<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatosPruebaSeeder;
use Database\Seeders\RolSeeder;
use Database\Seeders\TipoSangreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HospitalPerfilApiTest extends TestCase
{
    use RefreshDatabase;

    private const PERFIL = '/api/hospital/perfil';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolSeeder::class, TipoSangreSeeder::class, DatosPruebaSeeder::class]);
    }

    public function test_el_perfil_requiere_un_hospital_activo_y_verificado(): void
    {
        $this->getJson(self::PERFIL)->assertUnauthorized();

        Sanctum::actingAs($this->usuario('ana'));
        $this->getJson(self::PERFIL)->assertForbidden();

        Sanctum::actingAs($this->usuario('norte'));
        $this->putJson(self::PERFIL, ['zona' => 'Norte'])->assertForbidden();

        $inactivo = $this->usuario('central');
        $inactivo->update(['estado' => 'inactivo']);
        Sanctum::actingAs($inactivo);
        $this->getJson(self::PERFIL)->assertForbidden();
    }

    public function test_el_hospital_consulta_y_actualiza_su_propio_perfil(): void
    {
        Sanctum::actingAs($this->usuario('central'));

        $this->getJson(self::PERFIL)->assertOk()
            ->assertJsonPath('perfil.razon_social', 'Hospital Demo Central');

        $this->putJson(self::PERFIL, ['latitud' => -17])->assertUnprocessable()
            ->assertJsonValidationErrors('longitud');
        $this->putJson(self::PERFIL, ['latitud' => null, 'longitud' => null])->assertUnprocessable();
        $this->putJson(self::PERFIL, ['whatsapp' => 'número inválido'])->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp');

        $this->putJson(self::PERFIL, [
            'razon_social' => 'Hospital Central Actualizado',
            'latitud' => -17,
            'longitud' => -66,
            'whatsapp' => '+591 (7000) 0001',
            'verificado' => false,
            'nit' => 'otro',
        ])->assertOk()
            ->assertJsonPath('perfil.razon_social', 'Hospital Central Actualizado')
            ->assertJsonPath('perfil.latitud', '-17.0000000')
            ->assertJsonPath('perfil.whatsapp', '59170000001')
            ->assertJsonPath('perfil.nit', '1000000011')
            ->assertJsonPath('perfil.verificado', true);
    }

    private function usuario(string $cuenta): Usuario
    {
        return Usuario::where('email', $cuenta.'@donavida.test')->firstOrFail();
    }
}
