<?php

namespace Tests\Unit;

use Database\Seeders\Personal\PersonalSeeder;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class PersonalSeederFirmasTest extends TestCase
{
    private function copiar(string $nombre): void
    {
        $metodo = new ReflectionMethod(PersonalSeeder::class, 'copiarFirmaPorDefectoSiFalta');
        $metodo->setAccessible(true);
        $metodo->invoke(new PersonalSeeder(), $nombre);
    }

    public function test_los_archivos_de_firma_por_defecto_estan_versionados(): void
    {
        $this->assertFileExists(database_path('seeders/assets/firmas/Firma_Oscar.png'));
        $this->assertFileExists(database_path('seeders/assets/firmas/Sello_Victor.png'));
    }

    public function test_copia_la_firma_por_defecto_cuando_falta(): void
    {
        Storage::fake('public');

        $this->copiar('Firma_Oscar.png');

        Storage::disk('public')->assertExists('images/firmas/Firma_Oscar.png');
        $this->assertSame(
            file_get_contents(database_path('seeders/assets/firmas/Firma_Oscar.png')),
            Storage::disk('public')->get('images/firmas/Firma_Oscar.png')
        );
    }

    public function test_no_sobreescribe_un_archivo_existente(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/firmas/Sello_Victor.png', 'contenido-en-uso');

        $this->copiar('Sello_Victor.png');

        $this->assertSame('contenido-en-uso', Storage::disk('public')->get('images/firmas/Sello_Victor.png'));
    }
}
