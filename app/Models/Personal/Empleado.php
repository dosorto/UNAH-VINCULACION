<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Models\User;
use App\Models\UnidadAcademica\DepartamentoAcademico;

use App\Models\Personal\CategoriaEmpleado;
use App\Models\UnidadAcademica\FacultadCentro;

class Empleado extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = [
        'nombre_completo',
        'numero_empleado',
        'celular',
        'categoria',
        'user_id',
        'campus_id',
        'departamento_academico_id'
    ];

    protected static $logName = 'Empleado';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'fecha_contratacion', 'salario', 'supervisor', 'jornada'])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }

    protected $fillable = [
        'nombre_completo',
        'numero_empleado',
        'celular',
        'categoria_id',
        'user_id',
        'centro_facultad_id',
        'departamento_academico_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function departamento_academico()
    {
        return $this->belongsTo(DepartamentoAcademico::class, 'departamento_academico_id');
    }

    //  relacion uno a muchos inversa con el modelo CategoriaEmpleado

    public function categoria()
    {
        return $this->belongsTo(CategoriaEmpleado::class, 'categoria_id');
    }

    //  relacion uno a muchos inversa con el modelo FacultadCentro

    public function centro_facultad()
    {
        return $this->belongsTo(FacultadCentro::class, 'centro_facultad_id');
    }

   



    protected $table = 'empleado';
}
