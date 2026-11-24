<?php

namespace Tests;

use App\Models\Campana;
use App\Models\Cita;
use App\Models\Solicitud;
use App\Models\Usuario;
use Carbon\Carbon;
use Database\Seeders\DatosPruebaSeeder;
use Database\Seeders\RolSeeder;
use Database\Seeders\TipoSangreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

abstract class Modulo4TestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00', 'UTC'));
        $this->seed([RolSeeder::class, TipoSangreSeeder::class, DatosPruebaSeeder::class]);
        Mail::fake();
    }

    protected function usuario(string $nombre): Usuario
    {
        return Usuario::where('email', $nombre.'@donavida.test')->firstOrFail();
    }

    protected function actuar(string $nombre): Usuario
    {
        $usuario = $this->usuario($nombre);
        Sanctum::actingAs($usuario);

        return $usuario;
    }

    protected function solicitud(): Solicitud
    {
        return Solicitud::firstOrFail();
    }

    protected function campana(): Campana
    {
        return Campana::firstOrFail();
    }

    protected function reservaSolicitud(array $datos = []): array
    {
        return array_replace([
            'solicitud_id' => $this->solicitud()->id,
            'fecha_hora' => now()->addDay()->setTime(14, 0)->toISOString(),
        ], $datos);
    }

    protected function reservaCampana(): array
    {
        return ['campana_id' => $this->campana()->id,
            'fecha_hora' => $this->campana()->inicio()->addHour()->toISOString()];
    }

    protected function citaPasada(string $donante = 'ana', array $datos = []): Cita
    {
        return Cita::create(array_replace([
            'donante_id' => $this->usuario($donante)->donante->id,
            'solicitud_id' => $this->solicitud()->id,
            'fecha_hora' => now()->subHour(),
            'estado' => 'confirmada',
        ], $datos));
    }
}
