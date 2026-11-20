<?php

namespace Database\Seeders;

use App\Models\Campana;
use App\Models\Cita;
use App\Models\Donacion;
use App\Models\Donante;
use App\Models\Hospital;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Módulo 2: Donantes (Maya)
 * Donaciones pasadas de prueba, para poder ver el historial y las insignias
 * antes de que el módulo de citas y donaciones (Mollo) esté terminado.
 *
 * Cada donación necesita una cita, y cada cita una campaña (o solicitud),
 * así que se crean campañas ya "finalizadas" en las fechas de las donaciones.
 * Las fechas coinciden con fecha_ultima_donacion de DatosPruebaSeeder.
 */
class HistorialDonacionesSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::where('nit', '1000000011')->first(); // Hospital Demo Central

        $historiales = [
            // Sofía: 3 donaciones → Héroe nivel 2. La última hace 4 meses (sí puede donar).
            'sofia@donavida.test' => [12, 8, 4],
            // Luis: 1 donación hace 1 mes → Héroe nivel 1 (todavía NO puede donar).
            'luis@donavida.test' => [1],
        ];

        foreach ($historiales as $email => $mesesAtras) {
            $donante = Donante::whereHas('usuario', fn ($consulta) => $consulta->where('email', $email))->first();

            foreach ($mesesAtras as $meses) {
                $fecha = Carbon::today()->subMonths($meses)->setTime(9, 0);

                $campana = Campana::create([
                    'hospital_id' => $hospital->id,
                    'nombre' => 'Campaña de donación ' . $fecha->format('m/Y'),
                    'descripcion' => 'Campaña pasada (dato de prueba del historial)',
                    'lugar' => 'Hospital Demo Central',
                    'fecha' => $fecha->toDateString(),
                    'hora_inicio' => '08:00',
                    'hora_fin' => '13:00',
                    'cupos' => 30,
                    'estado' => 'finalizada',
                ]);

                $cita = Cita::create([
                    'donante_id' => $donante->id,
                    'campana_id' => $campana->id,
                    'fecha_hora' => $fecha,
                    'estado' => 'asistio',
                ]);

                Donacion::create([
                    'cita_id' => $cita->id,
                    'donante_id' => $donante->id,
                    'fecha_donacion' => $fecha,
                    'volumen_ml' => 450,
                    'estado' => 'valida',
                ]);
            }
        }
    }
}
