<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Institución / empresa del catálogo del FORM-DVUS-014 (ítems 20, 21 y 23 a 28).
 */
class PpsInstitucion extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'pps_instituciones';

    public const NACIONALIDADES = ['Nacional', 'Internacional'];

    /** Ítem 27 del formato. */
    public const TIPOS = [
        'gobierno_nacional' => 'Gobierno Nacional',
        'gobierno_municipal' => 'Gobierno Municipal',
        'ong' => 'ONG',
        'sociedad_civil' => 'Sociedad civil organizada',
        'sector_privado' => 'Sector Privado',
        'internacional' => 'Internacional',
    ];

    /** Ítem 28 del formato. */
    public const SECTORES = [
        'agricultura_alimentacion_silvicultura' => 'Agricultura, alimentación y silvicultura',
        'energia_mineria' => 'Energía y minería',
        'produccion' => 'Producción',
        'servicios_privados' => 'Sectores de servicios privados',
        'infraestructura_construccion' => 'Infraestructura, construcción y sectores relacionados',
        'educacion_investigacion' => 'Educación e investigación',
        'servicios_funcion_publicos' => 'Servicios y función públicos',
        'transporte' => 'Transporte, transporte marítimo y aéreo',
    ];

    public const CAMPOS = [
        'nombre', 'nacionalidad', 'pais', 'tipo', 'sector',
        'direccion', 'representante_legal', 'telefono', 'correo_rrhh',
    ];

    protected $fillable = self::CAMPOS;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(self::CAMPOS)
            ->setDescriptionForEvent(fn (string $eventName) => "La institución PPS/SS {$this->nombre} ha sido {$eventName}");
    }

    /** Validación de los datos del catálogo (al crearla desde el registro o desde el admin). */
    public static function reglas(string $prefijo = ''): array
    {
        return [
            "{$prefijo}nombre" => ['required', 'string', 'max:255'],
            "{$prefijo}nacionalidad" => ['required', 'in:'.implode(',', self::NACIONALIDADES)],
            "{$prefijo}pais" => ['required_if:'.$prefijo.'nacionalidad,Internacional', 'nullable', 'string', 'max:255', 'exists:pais,nombre'],
            "{$prefijo}tipo" => ['required', 'in:'.implode(',', array_keys(self::TIPOS))],
            "{$prefijo}sector" => ['required', 'in:'.implode(',', array_keys(self::SECTORES))],
            "{$prefijo}direccion" => ['required', 'string', 'max:2000'],
            "{$prefijo}representante_legal" => ['required', 'string', 'max:255'],
            "{$prefijo}telefono" => ['required', 'string', 'max:30'],
            "{$prefijo}correo_rrhh" => ['required', 'email', 'max:255'],
        ];
    }

    public function registros(): HasMany
    {
        return $this->hasMany(PpsServicioSocial::class, 'pps_institucion_id');
    }

    public function getPaisVisibleAttribute(): string
    {
        return $this->nacionalidad === 'Nacional' ? 'Honduras' : (string) $this->pais;
    }
}
