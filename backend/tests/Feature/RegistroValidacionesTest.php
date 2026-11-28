<?php

namespace Tests\Feature;

use App\Models\Donante;
use App\Models\TipoSangre;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Modulo4TestCase;

/**
 * Módulo 1 (Mayerli): validaciones del registro (letras, números, contraseña segura)
 * y archivos del donante (foto y carnet de donante anterior).
 */
class RegistroValidacionesTest extends Modulo4TestCase
{
    private function datosDonante(array $cambios = []): array
    {
        return array_replace([
            'nombre' => 'Valeria', 'apellidos' => 'Quiroga Rojas', 'email' => 'valeria@correo.com',
            'telefono' => '70712345', 'password' => 'Segura#2026', 'password_confirmation' => 'Segura#2026',
            'ci' => '8765432', 'fecha_nacimiento' => '2000-05-10', 'sexo' => 'F',
            'tipo_sangre_id' => TipoSangre::where('codigo', 'O-')->value('id'), 'zona' => 'Cala Cala',
        ], $cambios);
    }

    private function datosHospital(array $cambios = []): array
    {
        return array_replace([
            'nombre' => 'Marcelo Torrico', 'email' => 'viedma@correo.com', 'telefono' => '70000099',
            'password' => 'Segura#2026', 'password_confirmation' => 'Segura#2026',
            'razon_social' => 'Hospital Clínico Viedma', 'nit' => '1020304050', 'direccion' => 'Av. Aniceto Arce',
            'latitud' => -17.38, 'longitud' => -66.15, 'telefono_contacto' => '44533240',
        ], $cambios);
    }

    public function test_registra_donante_con_foto_y_carnet_de_donante(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $respuesta = $this->post('/api/registro/donante', $this->datosDonante([
            'fecha_ultima_donacion' => '2026-01-15',
            // PNG de 1x1 píxel (no necesita la extensión GD de PHP)
            'foto' => UploadedFile::fake()->createWithContent('foto.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')),
            'carnet_donante' => UploadedFile::fake()->create('carnet.pdf', 200, 'application/pdf'),
        ]), ['Accept' => 'application/json'])->assertCreated();

        $donante = Donante::where('ci', '8765432')->firstOrFail();
        Storage::disk('public')->assertExists($donante->foto_ruta);
        Storage::disk('local')->assertExists($donante->carnet_ruta);
        $this->assertSame('2026-01-15', $donante->fecha_ultima_donacion->toDateString());
        $this->assertNotNull($respuesta->json('usuario.donante.foto_url'));
        $this->assertTrue($respuesta->json('usuario.donante.tiene_carnet'));
        $this->assertArrayNotHasKey('carnet_ruta', $respuesta->json('usuario.donante'));
    }

    public function test_nombres_solo_letras_y_telefono_ci_solo_numeros(): void
    {
        $this->postJson('/api/registro/donante', $this->datosDonante([
            'nombre' => 'Vale123', 'apellidos' => 'Quiroga 2', 'telefono' => '70a12345', 'ci' => '87654AB',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['nombre', 'apellidos', 'telefono', 'ci'])
            ->assertJsonPath('errors.nombre.0', 'El nombre solo puede tener letras.');

        // Tildes, ñ y espacios sí son letras
        $this->postJson('/api/registro/donante', $this->datosDonante(['nombre' => 'María José', 'apellidos' => 'Peña Ñuflo']))
            ->assertCreated();
    }

    public function test_contrasena_debe_ser_segura(): void
    {
        foreach (['corta1!', 'sinmayuscula1!', 'SINMINUSCULA1!', 'SinNumero!!', 'SinSimbolo12'] as $clave) {
            $this->postJson('/api/registro/donante', $this->datosDonante(['password' => $clave, 'password_confirmation' => $clave]))
                ->assertUnprocessable()->assertJsonValidationErrors(['password']);
        }
    }

    public function test_archivos_invalidos_se_rechazan(): void
    {
        $this->post('/api/registro/donante', $this->datosDonante([
            'foto' => UploadedFile::fake()->create('virus.exe', 10),
        ]), ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['foto']);
    }

    public function test_hospital_valida_numeros_y_contrasena(): void
    {
        $this->postJson('/api/registro/hospital', $this->datosHospital([
            'nombre' => 'Dr 007', 'nit' => 'NIT-123', 'telefono_contacto' => '4-4533240', 'password' => 'debil', 'password_confirmation' => 'debil',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['nombre', 'nit', 'telefono_contacto', 'password']);

        $this->postJson('/api/registro/hospital', $this->datosHospital())->assertCreated();
    }
}
