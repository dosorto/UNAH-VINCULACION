<?php

namespace Tests\Unit;

use App\Exceptions\Integraciones\IntegracionApiException;
use App\Models\IntegracionApi;
use App\Services\Integraciones\EstudianteApiService;
use App\Services\Integraciones\IntegracionApiConfigValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class EstudianteApiServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_envia_numero_cuenta_y_bearer_y_mapea_rutas_anidadas(): void
    {
        Http::fake([
            'https://api.example.test/*' => Http::response([
                'data' => [
                    'estudiante' => [
                        'cuenta' => '001234',
                        'nombres' => ['primero' => ' Ana ', 'segundo' => 'María'],
                        'apellidos' => ['primero' => 'López'],
                        'sexo' => 'Femenino',
                        'email' => ' ANA@UNAH.EDU.HN ',
                        'carrera' => ['nombre' => 'Ingeniería en Sistemas'],
                    ],
                ],
            ]),
        ]);

        $integration = $this->integration([
            'tipo_autenticacion' => 'BEARER',
            'token_encriptado' => 'bearer-secret',
            'ruta_respuesta' => 'data.estudiante',
            'mapeo_campos_json' => [
                'numero_cuenta' => 'cuenta',
                'primer_nombre' => 'nombres.primero',
                'segundo_nombre' => 'nombres.segundo',
                'primer_apellido' => 'apellidos.primero',
                'sexo' => 'sexo',
                'correo_institucional' => 'email',
                'carrera_nombre' => 'carrera.nombre',
            ],
        ]);

        $result = app(EstudianteApiService::class)->consultar($integration, ' 001234 ');

        $this->assertTrue($result['encontrado']);
        $this->assertSame('001234', $result['numero_cuenta']);
        $this->assertSame('Ana María López', $result['nombre_completo']);
        $this->assertSame('F', $result['sexo']);
        $this->assertSame('ana@unah.edu.hn', $result['correo_institucional']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.example.test/estudiantes?numero_cuenta=001234'
            && $request->hasHeader('Authorization', 'Bearer bearer-secret'));
    }

    public function test_basic_auth_y_post_json_funcionan(): void
    {
        Http::fake(['https://api.example.test/*' => Http::response(['numeroCuenta' => '0099'])]);
        $integration = $this->integration([
            'metodo_http' => 'POST',
            'tipo_autenticacion' => 'BASIC',
            'usuario_api_encriptado' => 'api-user',
            'password_api_encriptado' => 'api-password',
        ]);

        app(EstudianteApiService::class)->consultar($integration, '0099');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request['numero_cuenta'] === '0099'
            && $request->hasHeader('Authorization', 'Basic ' . base64_encode('api-user:api-password')));
    }

    public function test_api_key_puede_enviarse_en_query(): void
    {
        Http::fake(['https://api.example.test/*' => Http::response(['numeroCuenta' => '123'])]);
        $integration = $this->integration([
            'tipo_autenticacion' => 'API_KEY',
            'api_key_header' => 'api_key',
            'api_key_ubicacion' => 'QUERY',
            'token_encriptado' => 'query-secret',
        ]);

        app(EstudianteApiService::class)->consultar($integration, '123');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api_key=query-secret')
            && str_contains($request->url(), 'numero_cuenta=123'));
    }

    public function test_maneja_timeout_error_http_y_json_invalido_sin_exponer_respuesta(): void
    {
        $service = app(EstudianteApiService::class);
        $integration = $this->integration();

        Http::fake(fn () => throw new ConnectionException('Request timed out token=secreto'));
        $timeout = $service->probarConexion($integration, '123');
        $this->assertSame('TIMEOUT', $timeout['tipo']);
        $this->assertStringNotContainsString('secreto', $timeout['mensaje']);

        Http::swap(new Factory());
        Http::fake(['*' => Http::response(['detail' => 'token=secreto'], 500)]);
        $http = $service->probarConexion($integration, '123');
        $this->assertSame('HTTP', $http['tipo']);
        $this->assertStringNotContainsString('secreto', $http['mensaje']);

        Http::swap(new Factory());
        Http::fake(['*' => Http::response('no-json', 200, ['Content-Type' => 'text/plain'])]);
        $json = $service->probarConexion($integration, '123');
        $this->assertSame('JSON_INVALIDO', $json['tipo']);
        $this->assertArrayNotHasKey('respuesta', $json);
    }

    public function test_normaliza_sexo_correo_nombres_y_estructura_estable(): void
    {
        $result = app(EstudianteApiService::class)->normalizar([
            'numero_cuenta' => ' 001 234 ',
            'primer_nombre' => 'Ana',
            'primer_apellido' => 'López',
            'sexo' => 'male',
            'correo' => ' ANA@EXAMPLE.COM ',
        ]);

        $this->assertSame('001234', $result['numero_cuenta']);
        $this->assertSame('Ana López', $result['nombre_completo']);
        $this->assertSame('M', $result['sexo']);
        $this->assertSame('ana@example.com', $result['correo']);
        $this->assertArrayHasKey('estado_academico', $result);
        $this->assertArrayHasKey('centro_codigo', $result);
    }

    public function test_ssrf_bloquea_localhost_metadata_redes_privadas_y_esquemas_no_http(): void
    {
        $service = app(EstudianteApiService::class);

        foreach ([
            'http://localhost/api',
            'http://127.0.0.1/api',
            'http://169.254.169.254/latest/meta-data',
            'http://10.0.0.1/api',
            'http://172.16.0.1/api',
            'http://192.168.1.1/api',
            'file:///etc/passwd',
            'ftp://example.com/file',
            'gopher://example.com',
        ] as $url) {
            try {
                $service->validarUrlSegura($url);
                $this->fail("La URL {$url} no fue bloqueada.");
            } catch (IntegracionApiException $exception) {
                $this->assertSame('SSRF', $exception->tipo);
            }
        }
    }

    public function test_valida_headers_mapeo_claves_duplicadas_y_expresiones(): void
    {
        $validator = app(IntegracionApiConfigValidator::class);

        $this->assertSame(
            ['Accept' => 'application/json'],
            $validator->decodeHeaders('{"Accept":"application/json"}', 'NINGUNA')
        );
        $this->assertSame(
            ['numero_cuenta' => 'data.estudiante.numeroCuenta'],
            $validator->decodeMapping('{"numero_cuenta":"data.estudiante.numeroCuenta"}')
        );

        foreach ([
            fn () => $validator->decodeHeaders('{"Host":"evil.test"}', 'NINGUNA'),
            fn () => $validator->decodeHeaders('{"X":"1","X":"2"}', 'NINGUNA'),
            fn () => $validator->decodeMapping('{"campo_arbitrario":"data.value"}'),
            fn () => $validator->decodeMapping('{"numero_cuenta":"{{ php }}"}'),
        ] as $invalidConfiguration) {
            $this->assertThrows($invalidConfiguration, InvalidArgumentException::class);
        }
    }

    private function integration(array $overrides = []): IntegracionApi
    {
        return IntegracionApi::create(array_merge([
            'nombre' => 'Servicio API ' . fake()->unique()->numerify('###'),
            'codigo' => fake()->unique()->slug(2),
            'tipo_perfil' => 'ESTUDIANTE',
            'base_url' => 'https://api.example.test',
            'ruta_busqueda' => '/estudiantes',
            'metodo_http' => 'GET',
            'tipo_autenticacion' => 'NINGUNA',
            'api_key_ubicacion' => 'HEADER',
            'parametro_busqueda' => 'numero_cuenta',
            'timeout_segundos' => 10,
            'reintentos' => 0,
            'verificar_ssl' => true,
            'headers_json' => ['Accept' => 'application/json'],
            'mapeo_campos_json' => ['numero_cuenta' => 'numeroCuenta'],
            'activo' => true,
            'protegida' => false,
        ], $overrides));
    }
}
