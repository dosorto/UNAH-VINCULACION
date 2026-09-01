<?php

namespace Tests\Unit;

use App\Support\Fichas\FirmaImagen;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FirmaImagenTest extends TestCase
{
    /** PNG de 1x1 px, mínimo válido. */
    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_en_modo_pdf_embebe_la_imagen_como_data_uri(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/firmas/firma.png', base64_decode(self::PNG_1PX));

        $resuelto = FirmaImagen::resolver('images/firmas/firma.png', true);

        $this->assertNotNull($resuelto);
        $this->assertStringStartsWith('data:image/png;base64,', $resuelto['src']);
        $this->assertSame(
            base64_decode(self::PNG_1PX),
            base64_decode(substr($resuelto['src'], strlen('data:image/png;base64,')))
        );
    }

    public function test_acepta_la_ruta_con_prefijo_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/firmas/firma.png', base64_decode(self::PNG_1PX));

        $resuelto = FirmaImagen::resolver('/storage/images/firmas/firma.png', true);

        $this->assertNotNull($resuelto);
        $this->assertStringStartsWith('data:image/png;base64,', $resuelto['src']);
    }

    public function test_en_pantalla_devuelve_una_url_publica_no_un_data_uri(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/firmas/firma.png', base64_decode(self::PNG_1PX));

        $resuelto = FirmaImagen::resolver('images/firmas/firma.png', false);

        $this->assertNotNull($resuelto);
        $this->assertStringNotContainsString('data:', $resuelto['src']);
        $this->assertStringContainsString('images/firmas/firma.png', $resuelto['src']);
    }

    public function test_devuelve_null_cuando_no_hay_ruta_o_el_archivo_no_existe(): void
    {
        Storage::fake('public');

        $this->assertNull(FirmaImagen::resolver(null, true));
        $this->assertNull(FirmaImagen::resolver('', true));
        $this->assertNull(FirmaImagen::resolver('images/firmas/no-existe.png', true));
    }

    public function test_respeta_un_data_uri_ya_embebido(): void
    {
        $dataUri = 'data:image/png;base64,' . self::PNG_1PX;

        $this->assertSame($dataUri, FirmaImagen::resolver($dataUri, true)['src']);
    }

    public function test_dimensiones_contenidas_encaja_en_la_caja_conservando_proporcion(): void
    {
        $sinArchivo = FirmaImagen::dimensionesContenidas(null, 160, 90);

        $this->assertSame(['width' => 160, 'height' => 90], $sinArchivo);
    }
}
