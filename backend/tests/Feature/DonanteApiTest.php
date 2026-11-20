<?php

namespace Tests\Feature;

use App\Models\Campana;
use App\Models\Cita;
use App\Models\Donacion;
use App\Models\Donante;
use App\Models\Hospital;
use App\Models\Rol;
use App\Models\TipoSangre;
use App\Models\Usuario;
use Carbon\Carbon;
use Database\Seeders\RolSeeder;
use Database\Seeders\TipoSangreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Módulo 2: Donantes (Maya)
 * Pruebas de las rutas /api/donante/*
 * Usan una base SQLite en memoria (phpunit.xml): no tocan la base MySQL real.
 */
class DonanteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolSeeder::class, TipoSangreSeeder::class]);
    }

    private function crearDonante(array $datos = []): Donante
    {
        $usuario = Usuario::create([
            'rol_id' => Rol::where('nombre', Rol::DONANTE)->first()->id,
            'nombre' => 'Ana',
            'email' => 'ana' . uniqid() . '@prueba.test',
            'telefono' => '70000001',
            'password' => 'password123',
        ]);

        return Donante::create(array_merge([
            'usuario_id' => $usuario->id,
            'tipo_sangre_id' => TipoSangre::where('codigo', 'O-')->first()->id,
            'apellidos' => 'Rojas',
            'ci' => (string) random_int(1000000, 9999999),
            'fecha_nacimiento' => Carbon::today()->subYears(25)->toDateString(),
            'sexo' => 'F',
            'disponible' => true,
        ], $datos));
    }

    private function iniciarSesionComo(Donante $donante): void
    {
        Sanctum::actingAs($donante->usuario);
    }

    private function crearHospital(): Hospital
    {
        $usuario = Usuario::create([
            'rol_id' => Rol::where('nombre', Rol::HOSPITAL)->first()->id,
            'nombre' => 'Hospital Prueba',
            'email' => 'hospital' . uniqid() . '@prueba.test',
            'telefono' => '44000000',
            'password' => 'password123',
        ]);

        return Hospital::create([
            'usuario_id' => $usuario->id,
            'razon_social' => 'Hospital Prueba',
            'nit' => (string) random_int(100000000, 999999999),
            'direccion' => 'Calle 1',
            'latitud' => -17.38,
            'longitud' => -66.15,
            'telefono_contacto' => '44000000',
            'verificado' => true,
        ]);
    }

    private function registrarDonacion(Donante $donante, Hospital $hospital, string $fecha, string $estado = 'valida'): void
    {
        $campana = Campana::create([
            'hospital_id' => $hospital->id,
            'nombre' => 'Campaña ' . $fecha,
            'lugar' => 'Hospital',
            'fecha' => $fecha,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
            'cupos' => 10,
            'estado' => 'finalizada',
        ]);

        $cita = Cita::create([
            'donante_id' => $donante->id,
            'campana_id' => $campana->id,
            'fecha_hora' => $fecha . ' 09:00:00',
            'estado' => 'asistio',
        ]);

        Donacion::create([
            'cita_id' => $cita->id,
            'donante_id' => $donante->id,
            'fecha_donacion' => $fecha . ' 09:00:00',
            'estado' => $estado,
        ]);
    }

    // ---------- Seguridad ----------

    public function test_sin_token_no_puede_entrar(): void
    {
        $this->getJson('/api/donante/perfil')->assertStatus(401);
    }

    public function test_un_hospital_no_puede_usar_las_rutas_de_donante(): void
    {
        Sanctum::actingAs($this->crearHospital()->usuario);

        $this->getJson('/api/donante/perfil')->assertStatus(403);
    }

    // ---------- Perfil ----------

    public function test_el_donante_ve_su_perfil(): void
    {
        $donante = $this->crearDonante();
        $this->iniciarSesionComo($donante);

        $this->getJson('/api/donante/perfil')
            ->assertOk()
            ->assertJsonPath('perfil.nombre', 'Ana')
            ->assertJsonPath('perfil.tipo_sangre.codigo', 'O-')
            ->assertJsonPath('perfil.edad', 25)
            ->assertJsonPath('perfil.puede_donar_hoy', true);
    }

    public function test_el_donante_edita_ubicacion_disponibilidad_y_tipo_de_sangre(): void
    {
        $donante = $this->crearDonante();
        $this->iniciarSesionComo($donante);
        $tipoAPositivo = TipoSangre::where('codigo', 'A+')->first();

        $this->putJson('/api/donante/perfil', [
            'nombre' => 'Ana María',
            'tipo_sangre_id' => $tipoAPositivo->id,
            'latitud' => -17.3935,
            'longitud' => -66.157,
            'zona' => 'Cala Cala',
            'disponible' => false,
        ])
            ->assertOk()
            ->assertJsonPath('perfil.nombre', 'Ana María')
            ->assertJsonPath('perfil.tipo_sangre.codigo', 'A+')
            ->assertJsonPath('perfil.zona', 'Cala Cala')
            ->assertJsonPath('perfil.disponible', false);

        $donante->refresh();
        $this->assertSame($tipoAPositivo->id, $donante->tipo_sangre_id);
        $this->assertEquals(-17.3935, (float) $donante->latitud);
        $this->assertFalse($donante->disponible);
        $this->assertSame('Ana María', $donante->usuario->nombre);
    }

    public function test_no_acepta_menores_de_18_al_editar(): void
    {
        $this->iniciarSesionComo($this->crearDonante());

        $this->putJson('/api/donante/perfil', [
            'fecha_nacimiento' => Carbon::today()->subYears(17)->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('fecha_nacimiento');
    }

    public function test_no_acepta_mayores_de_60_al_editar(): void
    {
        $this->iniciarSesionComo($this->crearDonante());

        $this->putJson('/api/donante/perfil', [
            'fecha_nacimiento' => Carbon::today()->subYears(61)->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('fecha_nacimiento');
    }

    public function test_acepta_el_limite_de_60_anios(): void
    {
        $this->iniciarSesionComo($this->crearDonante());

        $this->putJson('/api/donante/perfil', [
            'fecha_nacimiento' => Carbon::today()->subYears(60)->toDateString(),
        ])->assertOk()->assertJsonPath('perfil.edad', 60);
    }

    public function test_la_ubicacion_debe_enviarse_completa_y_valida(): void
    {
        $this->iniciarSesionComo($this->crearDonante());

        $this->putJson('/api/donante/perfil', ['latitud' => -17.39])
            ->assertStatus(422)->assertJsonValidationErrors('longitud');

        $this->putJson('/api/donante/perfil', ['latitud' => 120, 'longitud' => -66.15])
            ->assertStatus(422)->assertJsonValidationErrors('latitud');
    }

    public function test_editar_otro_dato_no_borra_la_ubicacion(): void
    {
        $donante = $this->crearDonante(['latitud' => -17.39, 'longitud' => -66.15]);
        $this->iniciarSesionComo($donante);

        $this->putJson('/api/donante/perfil', ['disponible' => false])->assertOk();

        $donante->refresh();
        $this->assertEquals(-17.39, (float) $donante->latitud);
        $this->assertEquals(-66.15, (float) $donante->longitud);
    }

    // ---------- ¿Puedo donar? ----------

    public function test_puede_donar_si_nunca_dono(): void
    {
        $this->iniciarSesionComo($this->crearDonante());

        $this->getJson('/api/donante/puedo-donar')
            ->assertOk()
            ->assertJsonPath('puede_donar', true)
            ->assertJsonPath('dias_restantes', 0)
            ->assertJsonPath('proxima_fecha_para_donar', null);
    }

    public function test_no_puede_donar_si_dono_hace_un_mes(): void
    {
        $haceUnMes = Carbon::today()->subMonth();
        $this->iniciarSesionComo($this->crearDonante(['fecha_ultima_donacion' => $haceUnMes->toDateString()]));

        $this->getJson('/api/donante/puedo-donar')
            ->assertOk()
            ->assertJsonPath('puede_donar', false)
            ->assertJsonPath('proxima_fecha_para_donar', $haceUnMes->copy()->addMonthsNoOverflow(3)->toDateString());
    }

    public function test_puede_donar_si_dono_hace_cuatro_meses(): void
    {
        $this->iniciarSesionComo($this->crearDonante([
            'fecha_ultima_donacion' => Carbon::today()->subMonths(4)->toDateString(),
        ]));

        $this->getJson('/api/donante/puedo-donar')->assertOk()->assertJsonPath('puede_donar', true);
    }

    // ---------- Historial ----------

    public function test_historial_vacio_sin_insignia(): void
    {
        $this->iniciarSesionComo($this->crearDonante());

        $this->getJson('/api/donante/historial')
            ->assertOk()
            ->assertJsonPath('total_donaciones', 0)
            ->assertJsonPath('insignia.nivel', 0)
            ->assertJsonCount(0, 'donaciones');
    }

    public function test_historial_con_insignia_y_solo_donaciones_propias(): void
    {
        $hospital = $this->crearHospital();
        $donante = $this->crearDonante();
        $otroDonante = $this->crearDonante();

        $this->registrarDonacion($donante, $hospital, '2025-03-10');
        $this->registrarDonacion($donante, $hospital, '2025-07-15');
        $this->registrarDonacion($donante, $hospital, '2025-11-20');
        $this->registrarDonacion($donante, $hospital, '2026-03-01', 'anulada');   // no cuenta
        $this->registrarDonacion($otroDonante, $hospital, '2026-01-05');          // de otra persona

        $this->iniciarSesionComo($donante);

        $this->getJson('/api/donante/historial')
            ->assertOk()
            ->assertJsonCount(4, 'donaciones')                    // se ven las 4 propias (incluye la anulada)
            ->assertJsonPath('total_donaciones', 3)               // pero solo cuentan las 3 válidas
            ->assertJsonPath('total_ml', 1350)
            ->assertJsonPath('insignia.titulo', 'Héroe nivel 2')
            ->assertJsonPath('donaciones.0.fecha_donacion', '2026-03-01 09:00:00')   // la más reciente primero
            ->assertJsonPath('donaciones.0.estado', 'anulada')
            ->assertJsonPath('donaciones.1.hospital', 'Hospital Prueba');
    }
}
