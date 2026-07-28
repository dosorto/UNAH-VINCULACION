<?php

namespace App\Models\Proyecto;

use App\Models\Personal\Empleado;
use Spatie\Activitylog\LogOptions;
use App\Models\Proyecto\CargoFirma;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmpleadoProyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'empleado_id', 'proyecto_id'];

    protected static $logName = 'EmpleadoProyecto';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'empleado_id', 'proyecto_id'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->empleado_id} ha sido {$eventName}");
    }

    protected $fillable = [
        'id',
        'empleado_id',
        'proyecto_id',
        'rol',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id',);
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->rol !== 'Coordinador') {
                return;
            }

            $cargo = CargoFirma::query()
                ->where('descripcion', 'Proyecto')
                ->whereHas('tipoCargoFirma', fn($q) => $q->where('nombre', 'Coordinador Proyecto'))
                ->first();

            if (!$cargo) {
                return;
            }

            $empleado = Empleado::find($model->empleado_id);
            $esPropietario = $empleado && auth()->check() && auth()->id() == $empleado->user_id;

            $model->proyecto->firma_proyecto()->create([
                'empleado_id' => $model->empleado_id,
                'cargo_firma_id' => $cargo->id,
                'estado_revision' => $esPropietario ? 'Aprobado' : 'Pendiente',
                'hash' => 'hash',
            ]);
        });
    }

    protected $table = 'empleado_proyecto';
}
