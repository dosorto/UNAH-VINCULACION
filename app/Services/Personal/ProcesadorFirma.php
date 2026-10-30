<?php

namespace App\Services\Personal;

use Imagick;
use ImagickException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProcesadorFirma
{
    /** Genera una copia PNG de una firma almacenada. */
    public function guardar(string $archivo): string
    {
        if (! extension_loaded('imagick')) {
            throw new RuntimeException('La extensión PHP Imagick no está instalada.');
        }

        $dimensiones = @getimagesize($archivo);
        if (! $dimensiones || $dimensiones[0] * $dimensiones[1] > 20_000_000) {
            throw new RuntimeException('La imagen de la firma supera el tamaño permitido.');
        }

        $imagen = new Imagick;
        try {
            $imagen->readImage($archivo.'[0]');
            $imagen->autoOrient();
            $imagen->setImageColorspace(Imagick::COLORSPACE_SRGB);
            $quantum = $imagen->getQuantumRange()['quantumRangeLong'];
            $tieneTransparencia = false;
            if ($imagen->getImageAlphaChannel()) {
                $alpha = clone $imagen;
                $alpha->setImageAlphaChannel(Imagick::ALPHACHANNEL_EXTRACT);
                $rango = $alpha->getImageChannelRange(Imagick::CHANNEL_RED);
                $tieneTransparencia = $rango['minima'] < $quantum;
                $alpha->clear();
            }

            if (! $tieneTransparencia) {
                // Compensa sombras del papel mediante una estimación local de
                // iluminación y convierte la intensidad de la tinta en opacidad.
                $mascara = clone $imagen;
                $mascara->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                $mascara->setImageColorspace(Imagick::COLORSPACE_GRAY);
                $fondo = clone $mascara;
                $fondo->blurImage(0, max(10, min($imagen->getImageWidth(), $imagen->getImageHeight()) / 32));
                $mascara->compositeImage($fondo, Imagick::COMPOSITE_DIVIDESRC, 0, 0);
                $mascara->levelImage($quantum * 0.75, 1, $quantum * 0.95);
                $mascara->negateImage(false);
                $imagen->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                $imagen->compositeImage($mascara, Imagick::COMPOSITE_COPYOPACITY, 0, 0);
                $fondo->clear();
                $mascara->clear();
            }
            if ($imagen->getImageWidth() > 1600 || $imagen->getImageHeight() > 800) {
                $imagen->thumbnailImage(1600, 800, true);
            }
            $imagen->setImageFormat('png');
            $imagen->setImageCompressionQuality(85);

            $ruta = 'images/firmas/'.Str::uuid().'.png';
            if (! Storage::disk('public')->put($ruta, $imagen->getImagesBlob())) {
                throw new RuntimeException('No se pudo guardar la imagen de la firma.');
            }

            return $ruta;
        } catch (ImagickException $exception) {
            throw new RuntimeException('No se pudo procesar la imagen de la firma.', 0, $exception);
        } finally {
            $imagen->clear();
        }
    }
}
