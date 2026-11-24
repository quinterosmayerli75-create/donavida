<?php

use App\Http\Controllers\Api\CampanaController;
use App\Models\Cita;
use App\Models\Donante;
use App\Models\Usuario;
use App\Services\GestionCitas;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    if (! $app->environment('testing') || config('database.default') !== 'mysql'
        || config('database.connections.mysql.database') !== 'donavida_concurrency_test') {
        throw new RuntimeException('Este worker solo admite la base donavida_concurrency_test.');
    }

    $tarea = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $usuarioHospital = Usuario::where('email', 'central@donavida.test')->with('hospital')->firstOrFail();
    $esperando = false;

    // Los procesos se encuentran justo antes del SELECT FOR UPDATE disputado.
    // En reservas de distintos donantes, ambos ya abrieron su instantánea de lectura.
    DB::connection()->beforeExecuting(function (string $sql) use ($tarea, &$esperando) {
        file_put_contents(dirname($tarea['listo']).DIRECTORY_SEPARATOR.'consulta-'.basename($tarea['listo']), $sql.PHP_EOL, FILE_APPEND);
        if ($esperando || ! str_contains(strtolower($sql), 'for update')
            || ! str_contains($sql, 'from `'.$tarea['tabla_barrera'].'`')) {
            return;
        }
        $esperando = true;
        file_put_contents($tarea['listo'], 'ready');
        $limite = microtime(true) + 15;
        while (! is_file($tarea['salida'])) {
            if (microtime(true) > $limite) {
                throw new RuntimeException('Tiempo agotado en la barrera de concurrencia.');
            }
            usleep(10000);
            clearstatcache(true, $tarea['salida']);
        }
    });

    $servicio = app(GestionCitas::class);
    switch ($tarea['accion']) {
        case 'reservar':
            $registro = $servicio->reservar(Donante::findOrFail($tarea['donante_id']), $tarea['datos']);
            $estado = 201;
            break;
        case 'donar':
            $registro = $servicio->registrarDonacion(Cita::findOrFail($tarea['cita_id']), []);
            $estado = 201;
            break;
        case 'editar_campana':
        case 'cancelar_campana':
            $peticion = Request::create('/', 'PATCH', $tarea['datos'] ?? []);
            $peticion->setUserResolver(fn () => $usuarioHospital);
            $controlador = app(CampanaController::class);
            $respuesta = $tarea['accion'] === 'editar_campana'
                ? $controlador->update($peticion, (string) $tarea['campana_id'])
                : $controlador->destroy($peticion, (string) $tarea['campana_id']);
            $estado = $respuesta->getStatusCode();
            $registro = null;
            break;
        default:
            throw new RuntimeException('Acción de prueba desconocida.');
    }

    echo json_encode(['estado' => $estado, 'id' => $registro?->id], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    $estado = match (true) {
        $error instanceof HttpExceptionInterface => $error->getStatusCode(),
        $error instanceof ValidationException => 422,
        default => 500,
    };
    echo json_encode(['estado' => $estado, 'error' => get_class($error)], JSON_THROW_ON_ERROR);
    exit($estado === 500 ? 1 : 0);
}
