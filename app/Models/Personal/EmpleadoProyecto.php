<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Proyecto\Proyecto;
use App\Models\Personal\Empleado;

use App\Models\Proyecto\Actividad;

use Illuminate\Support\Str;


class EmpleadoProyecto extends Model
{
    use HasFactory;

    protected $table = 'empleado_proyecto';

    protected $fillable = [
        'empleado_id',
        'proyecto_id',
        'rol',
        'hash'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            do {
                $hash = Str::random(20);
            } while (EmpleadoProyecto::where('hash', $hash)->exists());

            $model->hash = $hash;
        });
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    // relacion de uno a muchos con el modelo actividad
    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'empleado_proyecto_id');
    }
}
