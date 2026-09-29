<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EntidadContraparte extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'entidad_contraparte';

    /** Tipos de contraparte del formato, con su etiqueta. */
    public const TIPOS = [
        'gobierno_nacional' => 'Gobierno Nacional',
        'gobierno_municipal' => 'Gobierno Municipal',
        'ong' => 'ONG',
        'sociedad_civil' => 'Sociedad Civil Organizada',
        'sector_privado' => 'Sector Privado',
        'internacional' => 'Internacional',
    ];

    /** RTN o identificador fiscal en texto libre; el único límite es el tamaño de la columna. */
    public static function reglasRtn(bool $requerido = false): array
    {
        return [$requerido ? 'required' : 'nullable', 'string', 'max:50'];
    }

    protected $fillable = [
        'rtn',
        'nombre',
        'tipo_entidad',
        'nombre_contacto',
        'cargo_contacto',
        'correo',
        'telefono',
    ];

    protected static $logAttributes = ['id', 'rtn', 'nombre', 'telefono', 'correo', 'nombre_contacto', 'tipo_entidad'];
    protected static $logName = 'EntidadContraparte';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'rtn', 'nombre', 'telefono', 'correo', 'nombre_contacto', 'tipo_entidad'])
            ->setDescriptionForEvent(fn (string $eventName) => "La contraparte {$this->nombre} ha sido {$eventName}");
    }

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(
            Proyecto::class,
            'entidad_contraparte_proyecto',
            'entidad_contraparte_id',
            'proyecto_id'
        )->using(EntidadContraparteProyecto::class)
         ->withPivot(['rtn', 'descripcion_acuerdos'])
         ->withTimestamps();
    }

    /** Registros de la contraparte en proyectos (sin los eliminados). */
    public function vinculacionesProyecto(): HasMany
    {
        return $this->hasMany(EntidadContraparteProyecto::class, 'entidad_contraparte_id');
    }

    public function getNombreConRtnAttribute(): string
    {
        return $this->rtn
            ? "{$this->nombre} (RTN: {$this->rtn})"
            : $this->nombre;
    }
}
