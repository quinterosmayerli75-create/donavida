<?php

namespace Database\Seeders;

use App\Models\Campana;
use App\Models\Donante;
use App\Models\Hospital;
use App\Models\Rol;
use App\Models\Solicitud;
use App\Models\TipoSangre;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para que los 4 integrantes tengan la misma información al desarrollar.
 * Todas las cuentas usan la contraseña: password123
 */
class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdministrador = Rol::where('nombre', Rol::ADMINISTRADOR)->first();
        $rolHospital = Rol::where('nombre', Rol::HOSPITAL)->first();
        $rolDonante = Rol::where('nombre', Rol::DONANTE)->first();

        // ---------- Administrador ----------
        Usuario::create([
            'rol_id' => $rolAdministrador->id,
            'nombre' => 'Administrador DonaVida',
            'email' => 'admin@donavida.test',
            'telefono' => '70000000',
            'password' => 'password123',
        ]);

        // ---------- Hospitales ----------
        $usuarioHospitalCentral = Usuario::create([
            'rol_id' => $rolHospital->id,
            'nombre' => 'Hospital Demo Central',
            'email' => 'central@donavida.test',
            'telefono' => '44000001',
            'password' => 'password123',
        ]);

        $hospitalCentral = Hospital::create([
            'usuario_id' => $usuarioHospitalCentral->id,
            'razon_social' => 'Hospital Demo Central',
            'nit' => '1000000011',
            'direccion' => 'Av. Aniceto Arce (dato de prueba)',
            'zona' => 'Centro',
            'latitud' => -17.3850000,
            'longitud' => -66.1490000,
            'telefono_contacto' => '44000001',
            'whatsapp' => '59170000001',
            'verificado' => true,
        ]);

        $usuarioHospitalNorte = Usuario::create([
            'rol_id' => $rolHospital->id,
            'nombre' => 'Clínica Demo Norte',
            'email' => 'norte@donavida.test',
            'telefono' => '44000002',
            'password' => 'password123',
            'estado' => 'pendiente',
        ]);

        Hospital::create([
            'usuario_id' => $usuarioHospitalNorte->id,
            'razon_social' => 'Clínica Demo Norte',
            'nit' => '1000000022',
            'direccion' => 'Av. América (dato de prueba)',
            'zona' => 'Norte',
            'latitud' => -17.3700000,
            'longitud' => -66.1580000,
            'telefono_contacto' => '44000002',
            'whatsapp' => '59170000002',
            'verificado' => false,   // sirve para probar la validación del administrador
        ]);

        // ---------- Donantes ----------
        $donantesDePrueba = [
            ['nombre' => 'Ana',   'apellidos' => 'Rojas Pérez',   'email' => 'ana@donavida.test',   'ci' => '9000001', 'sangre' => 'O-',  'sexo' => 'F', 'ultima' => null],
            ['nombre' => 'Luis',  'apellidos' => 'Mamani Flores', 'email' => 'luis@donavida.test',  'ci' => '9000002', 'sangre' => 'O+',  'sexo' => 'M', 'ultima' => Carbon::today()->subMonth()],    // NO puede donar (1 mes)
            ['nombre' => 'Sofía', 'apellidos' => 'Vargas Coca',   'email' => 'sofia@donavida.test', 'ci' => '9000003', 'sangre' => 'A+',  'sexo' => 'F', 'ultima' => Carbon::today()->subMonths(4)], // SÍ puede donar
            ['nombre' => 'Diego', 'apellidos' => 'Quispe Lara',   'email' => 'diego@donavida.test', 'ci' => '9000004', 'sangre' => 'AB+', 'sexo' => 'M', 'ultima' => null],
        ];

        foreach ($donantesDePrueba as $indice => $datos) {
            $usuarioDonante = Usuario::create([
                'rol_id' => $rolDonante->id,
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => '7100000' . ($indice + 1),
                'password' => 'password123',
            ]);

            $tipoSangre = TipoSangre::where('codigo', $datos['sangre'])->first();

            Donante::create([
                'usuario_id' => $usuarioDonante->id,
                'tipo_sangre_id' => $tipoSangre->id,
                'apellidos' => $datos['apellidos'],
                'ci' => $datos['ci'],
                'fecha_nacimiento' => Carbon::today()->subYears(22 + $indice * 5),
                'sexo' => $datos['sexo'],
                'peso_kg' => 60 + $indice * 5,
                'zona' => 'Cercado',
                'latitud' => -17.3935000 + ($indice * 0.004),
                'longitud' => -66.1570000 - ($indice * 0.004),
                'disponible' => true,
                'fecha_ultima_donacion' => $datos['ultima'],
            ]);
        }

        // ---------- Una solicitud y una campaña ----------
        Solicitud::create([
            'hospital_id' => $hospitalCentral->id,
            'tipo_sangre_id' => TipoSangre::where('codigo', 'O+')->first()->id,
            'unidades_requeridas' => 3,
            'prioridad' => 'critica',
            'motivo' => 'Paciente en cirugía de emergencia (dato de prueba)',
            'fecha_limite' => Carbon::now()->addDays(2),
        ]);

        Campana::create([
            'hospital_id' => $hospitalCentral->id,
            'nombre' => 'Campaña universitaria de donación',
            'descripcion' => 'Jornada de donación voluntaria (dato de prueba)',
            'lugar' => 'Plaza principal (dato de prueba)',
            'fecha' => Carbon::today()->addWeeks(2),
            'hora_inicio' => '08:00',
            'hora_fin' => '13:00',
            'cupos' => 40,
        ]);
    }
}
