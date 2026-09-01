<?php

namespace App\Support\Fichas;

use Illuminate\Support\Facades\Storage;

/**
 * Resuelve la imagen de una firma o sello para usarla dentro de una ficha.
 *
 * El punto delicado es el PDF: lo genera DomPDF, que aplica un "chroot" y no
 * sigue el symlink `public/storage`. En producción ese symlink suele apuntar a
 * un directorio fuera del release actual, así que DomPDF descarta las imágenes
 * en silencio y los cuadros de firma salen vacíos. Para el PDF, entonces, se
 * embeben los bytes de la imagen como data URI (base64): así no depende del
 * symlink, ni de la ruta física, ni de los permisos del usuario de nginx.
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
            return self::comoDataUri($archivoLocal, $rutaNormalizada);
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
     * @return array{src: string, path: string|null}|null
     */
    private static function comoDataUri(?string $archivoLocal, string $rutaNormalizada): ?array
    {
        if ($archivoLocal !== null) {
            $datos = @file_get_contents($archivoLocal);

            if ($datos !== false && $datos !== '') {
                return [
                    'src' => 'data:' . self::mime($archivoLocal, $datos) . ';base64,' . base64_encode($datos),
                    'path' => $archivoLocal,
                ];
            }
        }

        // Último intento: leer a través del disco 'public' de Laravel.
        if (Storage::disk('public')->exists($rutaNormalizada)) {
            $datos = Storage::disk('public')->get($rutaNormalizada);

            if (is_string($datos) && $datos !== '') {
                $mime = Storage::disk('public')->mimeType($rutaNormalizada) ?: 'image/png';

                return [
                    'src' => 'data:' . $mime . ';base64,' . base64_encode($datos),
                    'path' => null,
                ];
            }
        }

        return null;
    }

    private static function mime(string $archivo, string $datos): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectado = $finfo ? finfo_buffer($finfo, $datos) : false;

            if ($finfo) {
                finfo_close($finfo);
            }

            if (is_string($detectado) && str_starts_with($detectado, 'image/')) {
                return $detectado;
            }
        }

        return match (strtolower(pathinfo($archivo, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }
}
