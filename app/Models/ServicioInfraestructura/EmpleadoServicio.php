<?php

namespace App\Models\ServicioInfraestructura;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ServicioInfraestructura\ServicioTecnologico;
use App\Models\Personal\Empleado;

use App\Models\ServicioInfraestructura\ActividadServicio;

use Illuminate\Support\Str;


class EmpleadoServicio extends Model
{
    use HasFactory;

    protected $table = 'empleado_servicio';

    protected $fillable = [
        'empleado_id',
        'servicio_tecnologico_id',
        'rol',
        'hash'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            do {
                $hash = Str::random(20);
            } while (EmpleadoServicio::where('hash', $hash)->exists());

            $model->hash = $hash;
        });
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function servicio()
    {
        return $this->belongsTo(ServicioTecnologico::class, 'servicio_tecnologico_id');
    }

    // relacion de uno a muchos con el modelo actividad
    public function actividades()
    {
        return $this->hasMany(ActividadServicio::class, 'empleado_servicio_id');
    }
}
