<?php

namespace Tests\Unit;

use App\Services\Personal\ProcesadorFirma;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Imagick;
use RuntimeException;
use Tests\TestCase;

class ProcesadorFirmaTest extends TestCase
{
    public function test_informa_si_falta_la_extension(): void
    {
        if (extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick está instalado.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Imagick no está instalada');
        app(ProcesadorFirma::class)->guardar(UploadedFile::fake()->image('firma.png')->getRealPath());
    }

    public function test_convierte_fondo_blanco_y_conserva_trazos(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Se necesita la extensión Imagick para procesar firmas.');
        }

        Storage::fake('public');
        $archivo = UploadedFile::fake()->image('firma.png', 100, 40);
        $imagen = imagecreatetruecolor(100, 40);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 255, 255, 255));
        imagesetpixel($imagen, 10, 10, imagecolorallocate($imagen, 0, 0, 0));
        imagepng($imagen, $archivo->getRealPath());
        imagedestroy($imagen);

        $original = new Imagick($archivo->getRealPath());
        $this->assertSame(0, $original->getImagePixelColor(10, 10)->getColor()['r']);
        $original->clear();

        $ruta = app(ProcesadorFirma::class)->guardar($archivo->getRealPath());
        Storage::disk('public')->assertExists($ruta);
        $resultado = new Imagick(Storage::disk('public')->path($ruta));
        $this->assertSame('PNG', $resultado->getImageFormat());
        $this->assertEqualsWithDelta(0, $resultado->getImagePixelColor(0, 0)->getColorValue(Imagick::COLOR_ALPHA), 0.01);
        $this->assertEqualsWithDelta(1, $resultado->getImagePixelColor(10, 10)->getColorValue(Imagick::COLOR_ALPHA), 0.01);
        $resultado->clear();
    }
    public function test_elimina_sombras_en_papel_y_conserva_la_tinta(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Se necesita Imagick.');
        }
        Storage::fake('public');
        $archivo = UploadedFile::fake()->image('firma.png', 300, 100);
        $imagen = imagecreatetruecolor(300, 100);
        for ($x = 0; $x < 300; $x++) {
            $gris = 160 + intdiv($x, 6);
            imageline($imagen, $x, 0, $x, 99, imagecolorallocate($imagen, $gris, $gris, $gris));
        }
        imageline($imagen, 80, 50, 220, 50, imagecolorallocate($imagen, 20, 20, 20));
        imagepng($imagen, $archivo->getRealPath());
        imagedestroy($imagen);

        $ruta = app(ProcesadorFirma::class)->guardar($archivo->getRealPath());
        $resultado = new Imagick(Storage::disk('public')->path($ruta));
        $this->assertLessThan(0.1, $resultado->getImagePixelColor(150, 20)->getColorValue(Imagick::COLOR_ALPHA));
        $this->assertGreaterThan(0.8, $resultado->getImagePixelColor(150, 50)->getColorValue(Imagick::COLOR_ALPHA));
        $resultado->clear();
    }

    public function test_conserva_la_transparencia_de_una_firma_existente(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Se necesita Imagick.');
        }
        Storage::fake('public');
        $archivo = UploadedFile::fake()->image('firma.png', 100, 40);
        $imagen = new Imagick;
        $imagen->newImage(100, 40, new \ImagickPixel('transparent'), 'png');
        $trazo = new \ImagickDraw;
        $trazo->setFillColor('blue');
        $trazo->rectangle(10, 10, 20, 20);
        $imagen->drawImage($trazo);
        $imagen->writeImage($archivo->getRealPath());
        $imagen->clear();

        $ruta = app(ProcesadorFirma::class)->guardar($archivo->getRealPath());
        $resultado = new Imagick(Storage::disk('public')->path($ruta));
        $this->assertLessThan(0.01, $resultado->getImagePixelColor(0, 0)->getColorValue(Imagick::COLOR_ALPHA));
        $this->assertGreaterThan(0.99, $resultado->getImagePixelColor(15, 15)->getColorValue(Imagick::COLOR_ALPHA));
        $resultado->clear();
    }

}
