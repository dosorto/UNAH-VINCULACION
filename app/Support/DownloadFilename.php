<?php

namespace App\Support;

use Illuminate\Support\Str;

final class DownloadFilename
{
    /**
     * Builds an ASCII filename safe for use in a Content-Disposition header.
     */
    public static function withExtension(string $label, string $extension, string $fallback = 'download'): string
    {
        $basename = trim(Str::slug($label));
        $basename = $basename !== '' ? $basename : $fallback;

        $extension = strtolower(trim($extension));
        $extension = preg_replace('/[^a-z0-9]+/i', '', $extension) ?: '';

        return $extension === '' ? $basename : $basename.'.'.$extension;
    }
}
