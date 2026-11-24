<?php

use App\Models\Usuario;
use App\Services\GestionCitas;
use Database\Seeders\DatosPruebaSeeder;
use Database\Seeders\RolSeeder;
use Database\Seeders\TipoSangreSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$temporal = null;
$codigoSalida = 0;

try {
    if ($app->configurationIsCached()) {
        throw new RuntimeException('Ejecuta php artisan config:clear antes de verificar Mailtrap.');
    }
    $smtp = config('mail.mailers.smtp');
    if (config('mail.default') !== 'smtp' || ($smtp['host'] ?? '') !== 'sandbox.smtp.mailtrap.io'
        || ! empty($smtp['url'])) {
        throw new RuntimeException('Configura MAIL_MAILER=smtp y MAIL_HOST=sandbox.smtp.mailtrap.io en backend/.env; deja MAIL_URL vacío.');
    }
    foreach (['username', 'password'] as $campo) {
        if (empty($smtp[$campo]) || str_contains($smtp[$campo], '_del_sandbox')) {
            throw new RuntimeException('Faltan las credenciales SMTP reales del sandbox en backend/.env.');
        }
    }

    // Se prueba reserva -> job después del commit -> SMTP, sin usar la base del .env.
    $temporal = tempnam(sys_get_temp_dir(), 'donavida-smtp-');
    if ($temporal === false) {
        throw new RuntimeException('No se pudo crear la base temporal de correo.');
    }
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $temporal,
        'database.connections.sqlite.url' => null, 'queue.default' => 'database',
        'queue.connections.database.connection' => 'sqlite', 'queue.failed.database' => 'sqlite',
        'cache.default' => 'array', 'session.driver' => 'array', 'logging.default' => 'null',
        'mail.mailers.smtp.timeout' => 20, 'hashing.bcrypt.rounds' => 4]);
    DB::purge('sqlite');
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Falló la preparación de la base temporal.');
    }
    foreach ([RolSeeder::class, TipoSangreSeeder::class, DatosPruebaSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $enviado = false;
    Event::listen(MessageSent::class, function () use (&$enviado) {
        $enviado = true;
    });
    $ana = Usuario::where('email', 'ana@donavida.test')->firstOrFail()->donante;
    app(GestionCitas::class)->reservar($ana, ['solicitud_id' => 1, 'fecha_hora' => now()->addHour()->toISOString()]);
    if (DB::table('jobs')->count() !== 1) {
        throw new RuntimeException('La reserva no encoló exactamente una confirmación.');
    }
    Artisan::call('queue:work', ['--once' => true, '--tries' => 1, '--timeout' => 30]);
    if (! $enviado || DB::table('jobs')->exists() || DB::table('failed_jobs')->exists()) {
        throw new RuntimeException('Mailtrap no confirmó el envío SMTP. Revisa las credenciales y la conexión al sandbox.');
    }
    echo json_encode(['resultado' => 'SMTP aceptado por Mailtrap', 'correo' => 'ConfirmacionCita',
        'flujo' => 'reserva -> cola -> SMTP', 'trabajos_pendientes' => 0, 'trabajos_fallidos' => 0,
        'base_del_proyecto_modificada' => false], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $error) {
    // No mostrar trazas SMTP: pueden contener datos de autenticación.
    $mensaje = $error instanceof RuntimeException && get_class($error) === RuntimeException::class
        ? $error->getMessage() : 'La verificación falló; comprueba la configuración SMTP y las extensiones PHP.';
    fwrite(STDERR, $mensaje.PHP_EOL);
    $codigoSalida = 1;
} finally {
    if (is_string($temporal)) {
        DB::disconnect('sqlite');
        if (is_file($temporal)) {
            unlink($temporal);
        }
    }
}

exit($codigoSalida);
