<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IntegracionApi extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public const CACHE_KEY_PREFIX = 'integracion-api.activa.';

    public const PERFIL_ESTUDIANTE = 'ESTUDIANTE';
    public const PERFIL_EMPLEADO = 'EMPLEADO';
    public const PERFIL_EXTERNO = 'EXTERNO';

    public const AUTH_NINGUNA = 'NINGUNA';
    public const AUTH_BEARER = 'BEARER';
    public const AUTH_BASIC = 'BASIC';
    public const AUTH_API_KEY = 'API_KEY';

    protected $table = 'integraciones_api';

    protected $fillable = [
        'nombre',
        'codigo',
        'tipo_perfil',
        'base_url',
        'ruta_busqueda',
        'metodo_http',
        'tipo_autenticacion',
        'token_encriptado',
        'usuario_api_encriptado',
        'password_api_encriptado',
        'api_key_header',
        'api_key_ubicacion',
        'parametro_busqueda',
        'timeout_segundos',
        'reintentos',
        'verificar_ssl',
        'headers_json',
        'ruta_respuesta',
        'mapeo_campos_json',
        'activo',
        'protegida',
        'ultima_prueba_at',
        'ultima_prueba_exitosa',
        'ultimo_codigo_http',
        'ultima_duracion_ms',
        'ultimo_mensaje',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'token_encriptado',
        'usuario_api_encriptado',
        'password_api_encriptado',
        'headers_json',
    ];

    protected function casts(): array
    {
        return [
            'token_encriptado' => 'encrypted',
            'usuario_api_encriptado' => 'encrypted',
            'password_api_encriptado' => 'encrypted',
            'headers_json' => 'encrypted:array',
            'mapeo_campos_json' => 'array',
            'activo' => 'boolean',
            'protegida' => 'boolean',
            'verificar_ssl' => 'boolean',
            'ultima_prueba_at' => 'datetime',
            'ultima_prueba_exitosa' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $forgetCache = static fn (self $integracion) => Cache::forget(
            self::CACHE_KEY_PREFIX . $integracion->tipo_perfil
        );

        static::saved($forgetCache);
        static::deleted($forgetCache);
        static::restored($forgetCache);
    }

    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }

    public function getSlugAttribute(): string
    {
        return $this->codigo;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Integraciones API')
            ->logOnly([
                'nombre',
                'codigo',
                'tipo_perfil',
                'base_url',
                'ruta_busqueda',
                'metodo_http',
                'tipo_autenticacion',
                'api_key_header',
                'api_key_ubicacion',
                'parametro_busqueda',
                'timeout_segundos',
                'reintentos',
                'verificar_ssl',
                'ruta_respuesta',
                'mapeo_campos_json',
                'activo',
                'ultima_prueba_at',
                'ultima_prueba_exitosa',
                'ultimo_codigo_http',
                'ultimo_mensaje',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "La integración API fue {$eventName}");
    }
}
