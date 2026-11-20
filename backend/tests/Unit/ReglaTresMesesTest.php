<?php

namespace Tests\Unit;

use App\Models\Donante;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Módulo 2: Donantes (Maya)
 * Pruebas de la regla de negocio: deben pasar al menos 3 meses entre donaciones.
 *
 * Se "congela" la fecha de hoy con Carbon::setTestNow() para que las pruebas
 * den siempre el mismo resultado, sin importar el día en que se ejecuten.
 * No usan la base de datos: solo prueban la lógica del modelo.
 */
class ReglaTresMesesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-20');   // "hoy" para todas las pruebas
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();   // vuelve a la fecha real
        parent::tearDown();
    }

    private function donanteQueDono(?string $fecha): Donante
    {
        return new Donante(['fecha_ultima_donacion' => $fecha]);
    }

    public function test_puede_donar_si_nunca_dono(): void
    {
        $donante = $this->donanteQueDono(null);

        $this->assertTrue($donante->puedeDonarHoy());
        $this->assertNull($donante->proximaFechaParaDonar());
        $this->assertSame(0, $donante->diasParaPoderDonar());
    }

    public function test_no_puede_donar_si_dono_hace_un_mes(): void
    {
        $donante = $this->donanteQueDono('2026-10-20');

        $this->assertFalse($donante->puedeDonarHoy());
        $this->assertSame('2027-01-20', $donante->proximaFechaParaDonar()->toDateString());
        $this->assertSame(61, $donante->diasParaPoderDonar());
    }

    public function test_no_puede_donar_un_dia_antes_de_cumplir_tres_meses(): void
    {
        // Donó el 21/08: los 3 meses se cumplen mañana (21/11)
        $donante = $this->donanteQueDono('2026-08-21');

        $this->assertFalse($donante->puedeDonarHoy());
        $this->assertSame(1, $donante->diasParaPoderDonar());
    }

    public function test_puede_donar_el_dia_exacto_en_que_se_cumplen_tres_meses(): void
    {
        $donante = $this->donanteQueDono('2026-08-20');

        $this->assertTrue($donante->puedeDonarHoy());
        $this->assertSame(0, $donante->diasParaPoderDonar());
    }

    public function test_puede_donar_si_pasaron_mas_de_tres_meses(): void
    {
        $donante = $this->donanteQueDono('2026-05-01');

        $this->assertTrue($donante->puedeDonarHoy());
    }

    public function test_fin_de_mes_no_se_desborda_al_mes_siguiente(): void
    {
        // 30/11 + 3 meses = 28/02 (febrero no tiene día 30), no el 2 de marzo
        Carbon::setTestNow('2027-02-28');
        $donante = $this->donanteQueDono('2026-11-30');

        $this->assertSame('2027-02-28', $donante->proximaFechaParaDonar()->toDateString());
        $this->assertTrue($donante->puedeDonarHoy());
    }

    public function test_insignia_segun_cantidad_de_donaciones(): void
    {
        $this->assertSame(0, Donante::calcularInsignia(0)['nivel']);
        $this->assertSame('Aún sin insignia', Donante::calcularInsignia(0)['titulo']);
        $this->assertSame(1, Donante::calcularInsignia(0)['siguiente_nivel_en']);

        $this->assertSame('Héroe nivel 1', Donante::calcularInsignia(1)['titulo']);
        $this->assertSame(2, Donante::calcularInsignia(1)['siguiente_nivel_en']);

        $this->assertSame(2, Donante::calcularInsignia(3)['nivel']);
        $this->assertSame(3, Donante::calcularInsignia(7)['nivel']);

        $this->assertSame(4, Donante::calcularInsignia(12)['nivel']);
        $this->assertNull(Donante::calcularInsignia(12)['siguiente_nivel_en']);
    }
}
