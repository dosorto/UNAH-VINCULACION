<?php

namespace App\Models\Estudiante;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ServicioInfraestructura\ServicioTecnologico;
use App\Models\Estudiante\Estudiante;

use App\Models\ServicioInfraestructura\ActividadServicio;

use Illuminate\Support\Str;


class EstudianteServicio extends Model
{
    use HasFactory;

    protected $table = 'estudiante_servicio';

    protected $fillable = [
        'estudiante_id',
        'servicio_tecnologico_id',
        'tipo_participacion_estudiante'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            do {
                $hash = Str::random(20);
            } while (EstudianteServicio::where('hash', $hash)->exists());

            $model->hash = $hash;
        });
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
    

    public function servicio()
    {
        return $this->belongsTo(ServicioTecnologico::class, 'servicio_tecnologico_id');
    }

 
}
