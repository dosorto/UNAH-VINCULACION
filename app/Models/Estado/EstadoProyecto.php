<?php

namespace App\Models\Estado;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EstadoProyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = [
        'id',
        'empleado_id',
        'tipo_estado_id',
        'fecha',
        'comentario',
        'es_actual',
        'estadoable_id',
        'estadoable_type'
    ];

    // relacion 

    public function estadable()
    {
        return $this->morphTo();
    }


    protected static $logName = 'EstadoProyecto';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'proyecto_id', 'empleado_id', 'tipo_estado_id', 'fecha', 'comentario'])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }

    protected $fillable = [
        'id',
        'proyecto_id',
        'empleado_id',
        'tipo_estado_id',
        'fecha',
        'comentario',
        'es_actual'
    ];


    /**
     * Cuando se está creando o actualizando un EstadoProyecto, aseguramos
     * que el nuevo estado se marque como 'es_actual' y el resto no.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $estadoProyecto
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($estadoProyecto) {
            self::where('estadoable_id', $estadoProyecto->estadoable_id)
                ->where('estadoable_type', $estadoProyecto->estadoable_type)
                ->update(['es_actual' => false]);
        });
    }


    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id',);
    }


    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }


    public function tipoestado()
    {
        return $this->belongsTo(TipoEstado::class, 'tipo_estado_id',);
    }
    protected $table = 'estado_proyecto';
}
