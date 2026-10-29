<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JornadaLaboral extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'jornadas_laborales';

    protected $fillable = [
        'hora_inicio',
        'hora_fin',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['hora_inicio', 'hora_fin', 'activo', 'orden'])
            ->setDescriptionForEvent(fn (string $eventName) => "La jornada laboral {$this->etiqueta} ha sido {$eventName}");
    }

    /**
     * Etiquetas de las jornadas activas, en el orden del catálogo. Es el valor que se guarda en
     * empleado.jornada_laboral («07:00 - 15:00»).
     *
     * @return list<string>
     */
    public static function etiquetasActivas(): array
    {
        return static::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get()
            ->pluck('etiqueta')
            ->all();
    }

    protected function etiqueta(): Attribute
    {
        return Attribute::get(fn () => substr((string) $this->hora_inicio, 0, 5).' - '.substr((string) $this->hora_fin, 0, 5));
    }
}
