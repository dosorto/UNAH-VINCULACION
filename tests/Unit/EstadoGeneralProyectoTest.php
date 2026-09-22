<?php

namespace Tests\Unit;

use App\Support\Proyecto\EstadoGeneralProyecto;
use PHPUnit\Framework\TestCase;

class EstadoGeneralProyectoTest extends TestCase
{
    public function test_estados_historicos_se_presentan_sin_confundir_cargos_y_etapas(): void
    {
        foreach (['Autoguardado' => 'Borrador', 'Enlace Vinculacion' => 'En revision',
            'Jefe Departamento' => 'En revision', 'Subsanacion' => 'Subsanacion',
            'En curso' => 'Registrado', 'Registrado' => 'Registrado', 'Finalizado' => 'Finalizado'] as $anterior => $actual) {
            $this->assertSame($actual, EstadoGeneralProyecto::nombre($anterior));
            $this->assertContains($anterior, EstadoGeneralProyecto::compatibles($actual));
        }
    }
}
