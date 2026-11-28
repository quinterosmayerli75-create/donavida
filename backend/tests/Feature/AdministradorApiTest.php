<?php

namespace Tests\Feature;

use Tests\Modulo4TestCase;

/**
 * Módulo 1 (Mayerli): rutas del administrador para Usuarios y Campañas.
 */
class AdministradorApiTest extends Modulo4TestCase
{
    public function test_admin_lista_usuarios_con_totales_y_filtro_por_rol(): void
    {
        $this->actuar('admin');

        $respuesta = $this->getJson('/api/admin/usuarios')->assertOk();
        $total = $respuesta->json('totales.donante') + $respuesta->json('totales.hospital') + $respuesta->json('totales.administrador');
        $this->assertCount($total, $respuesta->json('usuarios'));

        $donantes = $this->getJson('/api/admin/usuarios?rol=donante')->assertOk()->json('usuarios');
        $this->assertNotEmpty($donantes);
        foreach ($donantes as $donante) {
            $this->assertSame('donante', $donante['rol']);
            $this->assertNotNull($donante['tipo_sangre']);
        }

        $this->getJson('/api/admin/usuarios?rol=otro')->assertUnprocessable();
    }

    public function test_admin_suspende_y_reactiva_un_donante(): void
    {
        $ana = $this->usuario('ana');
        $ana->createToken('prueba');
        $this->actuar('admin');

        $this->patchJson("/api/admin/usuarios/{$ana->id}/estado", ['estado' => 'inactivo'])
            ->assertOk()->assertJsonPath('usuario.estado', 'inactivo');
        $this->assertDatabaseHas('usuarios', ['id' => $ana->id, 'estado' => 'inactivo']);
        $this->assertSame(0, $ana->tokens()->count());

        $this->patchJson("/api/admin/usuarios/{$ana->id}/estado", ['estado' => 'activo'])
            ->assertOk()->assertJsonPath('usuario.estado', 'activo');
    }

    public function test_no_cambia_su_propia_cuenta_ni_hospitales(): void
    {
        $admin = $this->actuar('admin');
        $central = $this->usuario('central');

        $this->patchJson("/api/admin/usuarios/{$admin->id}/estado", ['estado' => 'inactivo'])->assertUnprocessable();
        $this->patchJson("/api/admin/usuarios/{$central->id}/estado", ['estado' => 'inactivo'])->assertUnprocessable();
        $this->patchJson("/api/admin/usuarios/{$central->id}/estado", ['estado' => 'raro'])->assertUnprocessable();
    }

    public function test_admin_ve_campanas_de_toda_la_red(): void
    {
        $this->actuar('admin');

        $this->getJson('/api/admin/campanas')->assertOk()
            ->assertJsonPath('campanas.0.hospital.razon_social', $this->campana()->hospital->razon_social)
            ->assertJsonStructure(['campanas' => [['id', 'nombre', 'fecha', 'cupos', 'cupos_ocupados', 'cupos_disponibles', 'estado']]]);
    }

    public function test_admin_crea_ve_y_edita_un_donante(): void
    {
        $this->actuar('admin');
        $tipo = \App\Models\TipoSangre::where('codigo', 'B-')->value('id');
        $datos = [
            'nombre' => 'Mariana', 'apellidos' => 'Soliz', 'email' => 'mariana@correo.com', 'telefono' => '71234567',
            'password' => 'Segura#2026', 'password_confirmation' => 'Segura#2026', 'ci' => '6429182',
            'fecha_nacimiento' => '1998-03-02', 'sexo' => 'F', 'tipo_sangre_id' => $tipo,
        ];

        $id = $this->postJson('/api/admin/donantes', $datos)->assertCreated()
            ->assertJsonPath('donante.tipo_sangre', 'B-')->json('donante.id');
        $this->getJson("/api/admin/donantes/{$id}")->assertOk()->assertJsonPath('donante.ci', '6429182');

        // Editar sin cambiar la contraseña; su propio correo y carnet no cuentan como repetidos
        $cambios = array_replace($datos, ['nombre' => 'Mariana Elena', 'password' => null, 'password_confirmation' => null]);
        $this->putJson("/api/admin/donantes/{$id}", $cambios)->assertOk()->assertJsonPath('donante.nombre', 'Mariana Elena');

        // El correo de otra persona sí está repetido
        $this->putJson("/api/admin/donantes/{$id}", array_replace($cambios, ['email' => 'ana@donavida.test']))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_admin_crea_y_edita_un_hospital_ya_validado(): void
    {
        $this->actuar('admin');
        $datos = [
            'nombre' => 'Marcelo Torrico', 'email' => 'viedma@correo.com', 'telefono' => '70000099',
            'password' => 'Segura#2026', 'password_confirmation' => 'Segura#2026',
            'razon_social' => 'Hospital Clínico Viedma', 'nit' => '1020304050', 'direccion' => 'Av. Aniceto Arce',
            'latitud' => -17.38, 'longitud' => -66.15, 'telefono_contacto' => '44533240',
        ];

        $id = $this->postJson('/api/admin/hospitales', $datos)->assertCreated()
            ->assertJsonPath('hospital.verificado', true)->assertJsonPath('hospital.usuario.estado', 'activo')->json('hospital.id');

        $this->putJson("/api/admin/hospitales/{$id}", array_replace($datos, ['zona' => 'Cercado', 'password' => null, 'password_confirmation' => null]))
            ->assertOk()->assertJsonPath('hospital.zona', 'Cercado');
    }

    public function test_solo_el_administrador_entra(): void
    {
        $this->getJson('/api/admin/usuarios')->assertUnauthorized();
        $this->actuar('ana');
        $this->getJson('/api/admin/usuarios')->assertForbidden();
        $this->getJson('/api/admin/campanas')->assertForbidden();
        $this->postJson("/api/admin/donantes", [])->assertForbidden();
        $this->putJson("/api/admin/hospitales/1", [])->assertForbidden();
    }
}
