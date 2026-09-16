<?php

namespace App\Support\Fichas;

use Illuminate\Support\Facades\Storage;

/**
 * Resuelve la imagen de una firma o sello para usarla dentro de una ficha.
 *
 * El PDF se genera con DomPDF dentro del chroot de la aplicación. Las rutas
 * locales son compatibles con ese motor; las data URI no se renderizan de
 * forma confiable en la versión instalada y hacen que DomPDF imprima el texto
 * alternativo de la imagen.
 */
class FirmaImagen
{
    /**
     * @return array{src: string, path: string|null}|null
     */
    public static function resolver(?string $ruta, bool $isPdf): ?array
    {
        if (empty($ruta)) {
            return null;
        }

        // Ya viene embebida como data URI.
        if (str_starts_with($ruta, 'data:')) {
            return ['src' => $ruta, 'path' => null];
        }

        // URL absoluta: se deja tal cual.
        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            return ['src' => $ruta, 'path' => null];
        }

        $rutaNormalizada = ltrim($ruta, '/');

        if (str_starts_with($rutaNormalizada, 'storage/')) {
            $rutaNormalizada = substr($rutaNormalizada, strlen('storage/'));
        }

        $archivoLocal = self::ubicarArchivoLocal($ruta, $rutaNormalizada);

        if ($isPdf) {
            return self::comoRutaLocalParaPdf($archivoLocal, $rutaNormalizada);
        }

        if ($archivoLocal !== null && str_starts_with($archivoLocal, public_path())) {
            $relativa = ltrim(str_replace(public_path(), '', $archivoLocal), '/\\');

            return ['src' => asset($relativa), 'path' => $archivoLocal];
        }

        return ['src' => Storage::url($rutaNormalizada), 'path' => $archivoLocal];
    }

    /**
     * Tamaño "contain": misma proporción, encajado dentro de la caja de firma.
     * DomPDF no respeta `object-fit`, así que el width/height va explícito en
     * la etiqueta <img>.
     *
     * @return array{width: int, height: int}
     */
    public static function dimensionesContenidas(?string $path, int $ancho, int $alto): array
    {
        $medidas = $path ? @getimagesize($path) : false;

        if (! $medidas || $medidas[0] <= 0 || $medidas[1] <= 0) {
            return ['width' => $ancho, 'height' => $alto];
        }

        $escala = min($ancho / $medidas[0], $alto / $medidas[1]);

        return [
            'width' => (int) round($medidas[0] * $escala),
            'height' => (int) round($medidas[1] * $escala),
        ];
    }

    private static function ubicarArchivoLocal(string $rutaOriginal, string $rutaNormalizada): ?string
    {
        $candidatos = [
            $rutaOriginal,
            public_path('storage/' . $rutaNormalizada),
            storage_path('app/public/' . $rutaNormalizada),
        ];

        foreach ($candidatos as $candidato) {
            if (is_file($candidato) && is_readable($candidato)) {
                return $candidato;
            }
        }

        return null;
    }

    /**
     * Devuelve un recurso local dentro del chroot configurado para DomPDF.
     *
     * @return array{src: string, path: string}|null
     */
    private static function comoRutaLocalParaPdf(?string $archivoLocal, string $rutaNormalizada): ?array
    {
        if ($archivoLocal === null && Storage::disk('public')->exists($rutaNormalizada)) {
            try {
                $archivoLocal = Storage::disk('public')->path($rutaNormalizada);
            } catch (\Throwable) {
                return null;
            }
        }

        $rutaReal = $archivoLocal ? realpath($archivoLocal) : false;
        $chroots = array_values(array_unique(array_filter([
            realpath(base_path()),
            realpath(storage_path('app/public')),
            realpath(public_path('storage')),
        ])));

        $rutaPermitida = $rutaReal && collect($chroots)->contains(
            fn (string $chroot): bool => $rutaReal === $chroot || str_starts_with($rutaReal, $chroot . DIRECTORY_SEPARATOR)
        );

        if (! $rutaPermitida) {
            return null;
        }

        return [
            'src' => 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', $rutaReal),
            'path' => $rutaReal,
        ];
    }
}
