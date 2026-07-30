<?php

namespace Tests\Unit;

use App\Services\Constancias\NumeroConstanciaFinalizacion;
use PHPUnit\Framework\TestCase;

class NumeroConstanciaFinalizacionTest extends TestCase
{
    public function test_formatea_el_correlativo_institucional_a_cuatro_digitos(): void
    {
        $formateador = new NumeroConstanciaFinalizacion();
        $this->assertSame('N.º 0001-VRA/DVUS-2026', $formateador->format(1, 2026));
        $this->assertSame('N.º 0110-VRA/DVUS-2026', $formateador->format(110, 2026));
    }
}
