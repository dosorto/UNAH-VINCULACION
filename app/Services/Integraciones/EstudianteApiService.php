<?php

namespace App\Services\Integraciones;

use App\Exceptions\Integraciones\IntegracionApiException;
use App\Models\IntegracionApi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EstudianteApiService
{
    public const CAMPOS_ESTUDIANTE = [
        'numero_cuenta',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'nombre_completo',
        'sexo',
        'correo',
        'correo_institucional',
        'carrera_codigo',
        'carrera_nombre',
        'centro_codigo',
        'centro_nombre',
        'fecha_nacimiento',
        'telefono',
        'estado_academico',
    ];

    public function obtenerConfiguracionActiva(): ?IntegracionApi
    {
        return Cache::remember(
            IntegracionApi::CACHE_KEY_PREFIX . IntegracionApi::PERFIL_ESTUDIANTE,
            now()->addMinutes(5),
            fn () => IntegracionApi::query()
                ->activa()
                ->where('tipo_perfil', IntegracionApi::PERFIL_ESTUDIANTE)
                ->orderBy('id')
                ->first()
        );
    }

    public function buscarPorNumeroCuenta(string $numeroCuenta): array
    {
        $integracion = $this->obtenerConfiguracionActiva();

        if (! $integracion) {
            throw new IntegracionApiException(
                'NO_CONFIGURADA',
                'La integración de estudiantes no está configurada o está inactiva.'
            );
        }

        return $this->consultar($integracion, $numeroCuenta);
    }

    public function consultar(
        IntegracionApi $integracion,
        string $numeroCuenta,
        bool $permitirInactiva = false
    ): array
    {
        if (! $permitirInactiva && ! $integracion->activo) {
            throw new IntegracionApiException('INACTIVA', 'La integración está inactiva.');
        }

        $numeroCuenta = preg_replace('/\s+/u', '', trim($numeroCuenta));

        if ($numeroCuenta === '') {
            throw new IntegracionApiException('CONFIGURACION', 'Debe indicar un número de cuenta.');
        }

        $url = $this->construirUrl($integracion);
        $request = $this->crearSolicitud($integracion);
        $query = [$integracion->parametro_busqueda => $numeroCuenta];

        if (
            $integracion->tipo_autenticacion === IntegracionApi::AUTH_API_KEY
            && $integracion->api_key_ubicacion === 'QUERY'
        ) {
            $query[$integracion->api_key_header] = $integracion->token_encriptado;
        }

        try {
            $response = $integracion->metodo_http === 'POST'
                ? $request->post($url, $query)
                : $request->get($url, $query);
        } catch (ConnectionException $exception) {
            $message = Str::lower($exception->getMessage());
            $type = Str::contains($message, ['timed out', 'timeout'])
                ? 'TIMEOUT'
                : 'CONEXION';

            throw new IntegracionApiException(
                $type,
                $type === 'TIMEOUT'
                    ? 'La consulta excedió el tiempo permitido.'
                    : 'No fue posible conectar con la API institucional.'
            );
        } catch (\Throwable) {
            throw new IntegracionApiException('CONEXION', 'No fue posible conectar con la API institucional.');
        }

        return $this->procesarRespuesta($integracion, $response);
    }

    public function probarConexion(IntegracionApi $integracion, string $numeroCuenta): array
    {
        $started = microtime(true);

        try {
            $data = $this->consultar($integracion, $numeroCuenta, true);
            $result = [
                'ok' => true,
                'tipo' => $data['encontrado'] ? 'ENCONTRADO' : 'NO_ENCONTRADO',
                'mensaje' => $data['encontrado']
                    ? 'Conexión exitosa. Estudiante encontrado.'
                    : 'Respuesta válida, pero no se encontró el estudiante.',
                'resumen' => $this->resumenEnmascarado($data),
                'codigo_http' => 200,
            ];
        } catch (IntegracionApiException $exception) {
            $result = [
                'ok' => false,
                'tipo' => $exception->tipo,
                'mensaje' => $exception->getMessage(),
                'resumen' => [],
                'codigo_http' => null,
            ];
        }

        $result['duracion_ms'] = (int) round((microtime(true) - $started) * 1000);

        $integracion->update([
            'ultima_prueba_at' => now(),
            'ultima_prueba_exitosa' => $result['ok'],
            'ultimo_codigo_http' => $result['codigo_http'],
            'ultima_duracion_ms' => $result['duracion_ms'],
            'ultimo_mensaje' => $result['mensaje'],
            'updated_by' => auth()->id(),
        ]);

        $activity = activity('Integraciones API')
            ->performedOn($integracion)
            ->event('connection_tested')
            ->withProperties([
                'resultado' => $result['tipo'],
                'exitosa' => $result['ok'],
                'codigo_http' => $result['codigo_http'],
            ]);

        if (auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        $activity->log('Prueba de conexión ejecutada');

        return $result;
    }

    public function mapearRespuesta(IntegracionApi $integracion, array $respuesta): array
    {
        $root = filled($integracion->ruta_respuesta)
            ? data_get($respuesta, $integracion->ruta_respuesta)
            : $respuesta;

        if ($root === null || $root === []) {
            return $this->normalizar([]);
        }

        if (is_array($root) && array_is_list($root)) {
            $root = $root[0] ?? [];
        }

        if (! is_array($root)) {
            throw new IntegracionApiException(
                'RUTA_RESPUESTA',
                'La ruta interna configurada no contiene un objeto válido.'
            );
        }

        $mapped = [];

        foreach ($integracion->mapeo_campos_json ?? [] as $internal => $external) {
            if (in_array($internal, self::CAMPOS_ESTUDIANTE, true) && is_string($external)) {
                $mapped[$internal] = data_get($root, $external);
            }
        }

        return $this->normalizar($mapped);
    }

    public function normalizar(array $data): array
    {
        $normalized = array_fill_keys(self::CAMPOS_ESTUDIANTE, null);

        foreach ($normalized as $field => $_) {
            $value = $data[$field] ?? null;
            $normalized[$field] = is_string($value) ? trim($value) : $value;
        }

        if (filled($normalized['numero_cuenta'])) {
            $normalized['numero_cuenta'] = preg_replace('/\s+/u', '', (string) $normalized['numero_cuenta']);
        }

        foreach (['correo', 'correo_institucional'] as $emailField) {
            if (filled($normalized[$emailField])) {
                $normalized[$emailField] = Str::lower(trim((string) $normalized[$emailField]));
            }
        }

        $normalized['sexo'] = $this->normalizarSexo($normalized['sexo']);

        if (blank($normalized['nombre_completo'])) {
            $normalized['nombre_completo'] = collect([
                $normalized['primer_nombre'],
                $normalized['segundo_nombre'],
                $normalized['primer_apellido'],
                $normalized['segundo_apellido'],
            ])->filter(fn ($part) => filled($part))->implode(' ') ?: null;
        } else {
            $normalized['nombre_completo'] = preg_replace(
                '/\s+/u',
                ' ',
                trim((string) $normalized['nombre_completo'])
            );
        }

        return ['encontrado' => collect($normalized)->contains(fn ($value) => filled($value))]
            + $normalized;
    }

    public function validarUrlSegura(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new IntegracionApiException('SSRF', 'Solo se permiten URLs HTTP o HTTPS válidas.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new IntegracionApiException('SSRF', 'La URL no puede incluir credenciales.');
        }

        if (
            $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || in_array($host, ['metadata.google.internal', 'metadata.azure.internal'], true)
        ) {
            throw new IntegracionApiException('SSRF', 'La URL apunta a un destino no permitido.');
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (gethostbynamel($host) ?: []);

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new IntegracionApiException('SSRF', 'La URL apunta a una red privada o reservada.');
            }
        }
    }

    private function crearSolicitud(IntegracionApi $integracion): PendingRequest
    {
        $request = Http::acceptJson()
            ->withHeaders($integracion->headers_json ?? [])
            ->timeout((int) $integracion->timeout_segundos)
            ->connectTimeout(min(10, (int) $integracion->timeout_segundos))
            ->withOptions(['verify' => (bool) $integracion->verificar_ssl])
            ->withoutRedirecting();

        if ((int) $integracion->reintentos > 0) {
            $request->retry((int) $integracion->reintentos + 1, 100);
        }

        return match ($integracion->tipo_autenticacion) {
            IntegracionApi::AUTH_BEARER => $request->withToken($integracion->token_encriptado),
            IntegracionApi::AUTH_BASIC => $request->withBasicAuth(
                $integracion->usuario_api_encriptado,
                $integracion->password_api_encriptado
            ),
            IntegracionApi::AUTH_API_KEY => $integracion->api_key_ubicacion === 'HEADER'
                ? $request->withHeaders([$integracion->api_key_header => $integracion->token_encriptado])
                : $request,
            default => $request,
        };
    }

    private function construirUrl(IntegracionApi $integracion): string
    {
        $this->validarUrlSegura($integracion->base_url);

        return rtrim($integracion->base_url, '/') . '/' . ltrim($integracion->ruta_busqueda, '/');
    }

    private function procesarRespuesta(IntegracionApi $integracion, Response $response): array
    {
        if (in_array($response->status(), [401, 403], true)) {
            throw new IntegracionApiException('AUTENTICACION', 'La API rechazó las credenciales configuradas.');
        }

        if (! $response->successful()) {
            throw new IntegracionApiException(
                'HTTP',
                "La API respondió con el estado HTTP {$response->status()}."
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new IntegracionApiException('JSON_INVALIDO', 'La respuesta de la API no contiene JSON válido.');
        }

        return $this->mapearRespuesta($integracion, $payload);
    }

    private function normalizarSexo(mixed $sexo): ?string
    {
        if (blank($sexo)) {
            return null;
        }

        return match (Str::lower(trim((string) $sexo))) {
            'm', 'masculino', 'male' => 'M',
            'f', 'femenino', 'female' => 'F',
            default => 'O',
        };
    }

    private function resumenEnmascarado(array $data): array
    {
        return array_filter([
            'numero_cuenta' => filled($data['numero_cuenta'] ?? null)
                ? str_repeat('*', max(0, strlen($data['numero_cuenta']) - 4)) . substr($data['numero_cuenta'], -4)
                : null,
            'nombre_completo' => filled($data['nombre_completo'] ?? null)
                ? collect(explode(' ', $data['nombre_completo']))
                    ->filter()
                    ->map(fn (string $part): string => mb_substr($part, 0, 1) . '***')
                    ->implode(' ')
                : null,
            'correo_institucional' => $this->enmascararCorreo($data['correo_institucional'] ?? null),
            'carrera_nombre' => $data['carrera_nombre'] ?? null,
            'centro_nombre' => $data['centro_nombre'] ?? null,
            'estado_academico' => $data['estado_academico'] ?? null,
        ], fn ($value) => filled($value));
    }

    private function enmascararCorreo(?string $email): ?string
    {
        if (blank($email) || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1) . '***@' . $domain;
    }
}
