<?php

namespace App\Services\Integraciones;

use InvalidArgumentException;

class IntegracionApiConfigValidator
{
    private const FORBIDDEN_HEADERS = [
        'host',
        'content-length',
        'proxy-authorization',
        'cookie',
        'set-cookie',
    ];

    public function decodeMapping(string $json): array
    {
        $mapping = $this->decodeObject($json, 'El mapeo de campos');
        $invalid = array_diff(array_keys($mapping), EstudianteApiService::CAMPOS_ESTUDIANTE);

        if ($invalid !== []) {
            throw new InvalidArgumentException(
                'El mapeo contiene campos internos no permitidos: ' . implode(', ', $invalid) . '.'
            );
        }

        if (! array_key_exists('numero_cuenta', $mapping) && ! array_key_exists('nombre_completo', $mapping)) {
            throw new InvalidArgumentException(
                'Debe mapear al menos numero_cuenta o nombre_completo.'
            );
        }

        foreach ($mapping as $internal => $path) {
            if (! is_string($path) || trim($path) === '') {
                throw new InvalidArgumentException("La ruta del campo {$internal} debe ser texto.");
            }

            $this->assertSafeDataPath($path, "La ruta del campo {$internal}");
        }

        return $mapping;
    }

    public function decodeHeaders(
        string $json,
        string $authentication,
        ?string $apiKeyName = null
    ): array {
        $headers = $this->decodeObject($json, 'Los headers');
        $forbidden = self::FORBIDDEN_HEADERS;

        if ($authentication !== 'NINGUNA') {
            $forbidden[] = 'authorization';
        }

        if ($authentication === 'API_KEY' && filled($apiKeyName)) {
            $forbidden[] = strtolower($apiKeyName);
        }

        foreach ($headers as $name => $value) {
            if (! is_string($name) || trim($name) === '' || ! is_string($value)) {
                throw new InvalidArgumentException('Los headers deben ser pares de texto.');
            }

            if (in_array(strtolower($name), $forbidden, true)) {
                throw new InvalidArgumentException("El header {$name} no puede configurarse manualmente.");
            }

            $this->assertNoExecutableContent($name);
            $this->assertNoExecutableContent($value);
        }

        return $headers;
    }

    public function assertSafeDataPath(?string $path, string $label = 'La ruta interna'): void
    {
        if (blank($path)) {
            return;
        }

        $path = trim($path);
        $this->assertNoExecutableContent($path);

        if (! preg_match('/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/', $path)) {
            throw new InvalidArgumentException("{$label} contiene caracteres no permitidos.");
        }
    }

    private function decodeObject(string $json, string $label): array
    {
        try {
            $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidArgumentException("{$label} debe contener JSON válido.");
        }

        $isEmptyObject = trim($json) === '{}';

        if (! is_array($decoded) || (array_is_list($decoded) && ! $isEmptyObject)) {
            throw new InvalidArgumentException("{$label} debe ser un objeto JSON.");
        }

        preg_match_all('/"((?:\\\\.|[^"\\\\])*)"\\s*:/u', $json, $matches);
        $keys = array_map(static fn (string $key): string => stripcslashes($key), $matches[1] ?? []);

        if (count($keys) !== count(array_unique($keys))) {
            throw new InvalidArgumentException("{$label} no permite claves duplicadas.");
        }

        return $decoded;
    }

    private function assertNoExecutableContent(string $value): void
    {
        if (preg_match('/<\?(?:php|=)|\{\{|\{!!|<script|javascript:|eval\s*\(|\$\(/i', $value)) {
            throw new InvalidArgumentException('La configuración contiene una expresión no permitida.');
        }
    }
}
