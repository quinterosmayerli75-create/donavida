<?php

namespace Tests\Integration;

use App\Models\Campana;
use App\Models\Cita;
use App\Models\Donacion;
use App\Models\Donante;
use App\Models\Solicitud;
use App\Models\TipoSangre;
use App\Models\Usuario;
use Database\Seeders\DatosPruebaSeeder;
use Database\Seeders\RolSeeder;
use Database\Seeders\TipoSangreSeeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MySqlConcurrenciaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // No usar RefreshDatabase: los procesos deben ver fixtures ya confirmados.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('donavida_concurrency_test', config('database.connections.mysql.database'),
            'Las migraciones destructivas solo se permiten en la base exclusiva de concurrencia.');
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        $this->seed([RolSeeder::class, TipoSangreSeeder::class, DatosPruebaSeeder::class]);
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->campana()->update(['cupos' => 1]);
        Solicitud::firstOrFail()->update([
            'tipo_sangre_id' => TipoSangre::where('codigo', 'AB+')->firstOrFail()->id,
            'unidades_requeridas' => 2,
        ]);
    }

    public function test_dos_donantes_disputan_el_ultimo_cupo_sin_sobreventa(): void
    {
        $resultados = $this->competir([
            $this->reserva('ana', 'campanas'), $this->reserva('sofia', 'campanas'),
        ]);
        $this->assertEqualsCanonicalizing([201, 409], array_column($resultados, 'estado'));
        $this->assertSame(1, $this->campana()->citas()->count());
        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_dos_reservas_simultaneas_del_mismo_donante_solo_crean_una_cita(): void
    {
        $primera = $this->reserva('ana', 'donantes');
        $otra = $this->campana()->replicate();
        $otra->save();
        $segunda = $primera;
        $segunda['datos']['campana_id'] = $otra->id;
        $resultados = $this->competir([$primera, $segunda]);
        $this->assertEqualsCanonicalizing([201, 409], array_column($resultados, 'estado'));
        $this->assertSame(1, Cita::count());
    }

    public function test_dos_registros_de_la_misma_cita_no_duplican_donacion_ni_unidades(): void
    {
        $cita = $this->citaAsistida('ana');
        $tarea = ['accion' => 'donar', 'cita_id' => $cita->id, 'tabla_barrera' => 'donantes'];
        $resultados = $this->competir([$tarea, $tarea]);
        $this->assertEqualsCanonicalizing([201, 409], array_column($resultados, 'estado'));
        $this->assertSame(1, Donacion::count());
        $this->assertSame(1, Solicitud::firstOrFail()->unidades_confirmadas);
        $this->assertSame(now()->toDateString(), $this->donante('ana')->fecha_ultima_donacion->toDateString());
    }

    public function test_dos_citas_del_mismo_donante_no_eluden_el_intervalo_minimo(): void
    {
        $primera = $this->citaAsistida('ana');
        $segunda = $this->citaAsistida('ana');
        $resultados = $this->competir([
            ['accion' => 'donar', 'cita_id' => $primera->id, 'tabla_barrera' => 'donantes'],
            ['accion' => 'donar', 'cita_id' => $segunda->id, 'tabla_barrera' => 'donantes'],
        ]);
        $this->assertEqualsCanonicalizing([201, 409], array_column($resultados, 'estado'));
        $this->assertSame(1, Donacion::count());
        $this->assertSame(1, Solicitud::firstOrFail()->unidades_confirmadas);
    }

    public function test_donaciones_de_distintos_donantes_suman_sin_perder_actualizaciones(): void
    {
        $primera = $this->citaAsistida('ana');
        $segunda = $this->citaAsistida('sofia');
        $resultados = $this->competir([
            ['accion' => 'donar', 'cita_id' => $primera->id, 'tabla_barrera' => 'solicitudes'],
            ['accion' => 'donar', 'cita_id' => $segunda->id, 'tabla_barrera' => 'solicitudes'],
        ]);
        $this->assertSame([201, 201], array_column($resultados, 'estado'));
        $this->assertSame(2, Donacion::count());
        $solicitud = Solicitud::firstOrFail();
        $this->assertSame(2, $solicitud->unidades_confirmadas);
        $this->assertSame('cubierta', $solicitud->estado);
    }

    public function test_reducir_cupos_y_reservar_a_la_vez_preserva_el_limite(): void
    {
        $this->campana()->update(['cupos' => 2]);
        $this->campana()->citas()->create([
            'donante_id' => $this->donante('ana')->id,
            'fecha_hora' => $this->campana()->inicio()->addHour(), 'estado' => 'confirmada',
        ]);
        $resultados = $this->competir([
            $this->reserva('sofia', 'campanas'),
            ['accion' => 'editar_campana', 'campana_id' => $this->campana()->id,
                'datos' => ['cupos' => 1], 'tabla_barrera' => 'campanas'],
        ]);
        $estados = array_column($resultados, 'estado');
        $this->assertContains($estados, [[201, 422], [409, 200]]);
        $this->assertLessThanOrEqual($this->campana()->cupos, $this->campana()->citas()->count());
    }

    public function test_cancelar_campana_y_reservar_a_la_vez_no_deja_reservas_activas(): void
    {
        $resultados = $this->competir([
            $this->reserva('ana', 'campanas'),
            ['accion' => 'cancelar_campana', 'campana_id' => $this->campana()->id, 'tabla_barrera' => 'campanas'],
        ]);
        $this->assertContains($resultados[0]['estado'], [201, 409]);
        $this->assertSame(200, $resultados[1]['estado']);
        $this->assertSame('cancelada', $this->campana()->estado);
        $this->assertSame(0, $this->campana()->citas()->whereIn('estado', Cita::RESERVADAS)->count());
    }

    private function campana(): Campana
    {
        return Campana::firstOrFail();
    }

    private function donante(string $nombre): Donante
    {
        return Usuario::where('email', $nombre.'@donavida.test')->firstOrFail()->donante;
    }

    private function reserva(string $nombre, string $barrera): array
    {
        return ['accion' => 'reservar', 'donante_id' => $this->donante($nombre)->id,
            'tabla_barrera' => $barrera, 'datos' => ['campana_id' => $this->campana()->id,
                'fecha_hora' => $this->campana()->inicio()->addHour()->toISOString()]];
    }

    private function citaAsistida(string $nombre): Cita
    {
        return Cita::create(['donante_id' => $this->donante($nombre)->id,
            'solicitud_id' => Solicitud::firstOrFail()->id,
            'fecha_hora' => now()->subHour(), 'estado' => 'asistio']);
    }

    private function competir(array $tareas): array
    {
        $conexion = config('database.connections.mysql');
        $entorno = ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key'),
            'DB_CONNECTION' => 'mysql', 'DB_URL' => '', 'DB_HOST' => $conexion['host'],
            'DB_PORT' => (string) $conexion['port'], 'DB_DATABASE' => $conexion['database'],
            'DB_USERNAME' => $conexion['username'], 'DB_PASSWORD' => $conexion['password'],
            'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array', 'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array', 'LOG_CHANNEL' => 'null'];
        $barrera = sys_get_temp_dir().DIRECTORY_SEPARATOR.'donavida-race-'.bin2hex(random_bytes(8));
        mkdir($barrera, 0700);
        $salida = $barrera.DIRECTORY_SEPARATOR.'salida';
        $procesos = [];
        try {
            foreach ($tareas as $indice => $tarea) {
                $tarea['listo'] = $barrera.DIRECTORY_SEPARATOR.'listo-'.$indice;
                $tarea['salida'] = $salida;
                $proceso = new Process([PHP_BINARY, base_path('tests/Support/concurrencia-worker.php')],
                    base_path(), $entorno, json_encode($tarea, JSON_THROW_ON_ERROR), 30);
                $proceso->start();
                $procesos[] = $proceso;
            }
            $limite = microtime(true) + 15;
            do {
                clearstatcache();
                $listos = count(glob($barrera.DIRECTORY_SEPARATOR.'listo-*'));
                if ($listos === count($procesos)) {
                    break;
                }
                foreach ($procesos as $indice => $proceso) {
                    if (! $proceso->isRunning()) {
                        $traza = $barrera.DIRECTORY_SEPARATOR.'consulta-listo-'.$indice;
                        $this->fail('Worker '.$indice.' terminó con código '.$proceso->getExitCode().' antes de disputar el bloqueo: '
                            .$proceso->getOutput().$proceso->getErrorOutput().(is_file($traza) ? file_get_contents($traza) : 'Sin consultas'));
                    }
                }
                usleep(10000);
            } while (microtime(true) < $limite);
            $this->assertSame(count($procesos), $listos, 'Los workers no alcanzaron juntos la barrera.');
            file_put_contents($salida, 'go');

            return array_map(function (Process $proceso) {
                $proceso->wait();
                $this->assertSame(0, $proceso->getExitCode(), $proceso->getOutput().$proceso->getErrorOutput());

                return json_decode($proceso->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            }, $procesos);
        } finally {
            foreach ($procesos as $proceso) {
                if ($proceso->isRunning()) {
                    $proceso->stop();
                }
            }
            foreach (glob($barrera.DIRECTORY_SEPARATOR.'*') as $archivo) {
                unlink($archivo);
            }
            rmdir($barrera);
        }
    }
}
