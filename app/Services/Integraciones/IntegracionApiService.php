<?php

namespace App\Services\Integraciones;

use App\Models\IntegracionApi;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IntegracionApiService
{
    public const ESTUDIANTE_FIELDS = ['numero_cuenta','nombres','apellidos','nombre_completo','sexo','correo','carrera','centro_facultad','fecha_nacimiento','identidad'];

    public function obtenerActivaPara(string $tipoPerfil): ?IntegracionApi { return IntegracionApi::where('tipo_perfil',$tipoPerfil)->where('activo',true)->first(); }
    public function buscarEstudiantePorCuenta(string $numeroCuenta): array {
        $api=$this->obtenerActivaPara('ESTUDIANTE'); if (! $api) throw new RuntimeException('La integración de estudiantes no está configurada.');
        return $this->probarConexion($api,$numeroCuenta);
    }
    public function probarConexion(IntegracionApi $api, string $valor): array {
        $started=microtime(true); $this->assertSafeUrl($api->base_url); $url=rtrim($api->base_url,'/').'/'.ltrim($api->ruta_busqueda,'/');
        try {
            $request=$this->request($api)->timeout($api->timeout_segundos)->connectTimeout(min(10,$api->timeout_segundos))->withoutRedirecting();
            $response=$api->metodo_http === 'POST' ? $request->post($url, [$api->parametro_busqueda=>$valor]) : $request->get($url, [$api->parametro_busqueda=>$valor]);
            $payload=$response->json(); if (! is_array($payload)) throw new RuntimeException('La respuesta de la API no contiene JSON válido.');
            $mapped=$this->mapearRespuesta($api,$payload); $ok=$response->successful() && ! empty($mapped);
            $result=['ok'=>$ok,'codigo_http'=>$response->status(),'duracion_ms'=>(int)((microtime(true)-$started)*1000),'datos'=>$mapped,'mensaje'=>$ok?'Conexión exitosa.':'La respuesta de la API no contiene los campos esperados.'];
        } catch (\Throwable $e) { $result=['ok'=>false,'codigo_http'=>null,'duracion_ms'=>(int)((microtime(true)-$started)*1000),'datos'=>[],'mensaje'=>'La API institucional no está disponible temporalmente.']; }
        $api->update(['ultima_prueba_at'=>now(),'ultima_prueba_exitosa'=>$result['ok'],'ultimo_codigo_http'=>$result['codigo_http'],'ultima_duracion_ms'=>$result['duracion_ms'],'ultimo_mensaje'=>$result['mensaje']]); return $result;
    }
    public function mapearRespuesta(IntegracionApi $api, array $respuesta): array { $data=$api->ruta_respuesta ? data_get($respuesta,$api->ruta_respuesta) : $respuesta; if (! is_array($data)) return []; $mapped=[]; foreach (($api->mapeo_campos_json ?? []) as $internal=>$external) if (in_array($internal,self::ESTUDIANTE_FIELDS,true) && is_string($external)) $mapped[$internal]=data_get($data,$external); return array_filter($mapped,fn($v)=>$v!==null && $v!==''); }
    private function request(IntegracionApi $api): PendingRequest { $headers=$api->headers_json ?? []; foreach (['Authorization','X-Api-Key','Host'] as $sensitive) unset($headers[$sensitive]); $request=Http::acceptJson()->withHeaders($headers); return match($api->tipo_autenticacion) { 'BEARER'=>$request->withToken($api->token_encriptado), 'API_KEY'=>$request->withHeaders([$api->api_key_header=>$api->token_encriptado]), 'BASIC'=>$request->withBasicAuth($api->usuario_api_encriptado,$api->password_api_encriptado), default=>$request }; }
    private function assertSafeUrl(string $url): void { $parts=parse_url($url); if (! in_array($parts['scheme'] ?? '',['http','https'],true) || empty($parts['host'])) throw new RuntimeException('URL insegura.'); $host=strtolower($parts['host']); if (in_array($host,['localhost','metadata.google.internal'],true)) throw new RuntimeException('URL insegura.'); foreach (gethostbynamel($host) ?: [] as $ip) if (! filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) throw new RuntimeException('URL insegura.'); }
}
