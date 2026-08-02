<?php

namespace Tests\Unit;

use App\Support\DownloadFilename;
use PHPUnit\Framework\TestCase;

class DownloadFilenameTest extends TestCase
{
    public function test_normaliza_el_numero_institucional_solo_para_la_descarga(): void
    {
        $filename = DownloadFilename::withExtension('Constancia-Finalizacion-N.º 0001-VRA/DVUS-2026', 'pdf');

        $this->assertSame('constancia-finalizacion-no-0001-vradvus-2026.pdf', $filename);
        $this->assertStringNotContainsString('/', $filename);
        $this->assertStringNotContainsString('\\', $filename);
    }

    public function test_elimina_caracteres_invalidos_futuros_y_conserva_la_extension(): void
    {
        $filename = DownloadFilename::withExtension('Constancia: <prueba> "2026" \\ /', 'P.D/F');

        $this->assertSame('constancia-prueba-2026.pdf', $filename);
    }
}
