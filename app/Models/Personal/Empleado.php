<?php

namespace App\Models\Personal;

use App\Models\User;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\Actividad;
use Spatie\Activitylog\LogOptions;
use App\Models\Proyecto\FirmaProyecto;

use App\Models\UnidadAcademica\Campus;
use Illuminate\Database\Eloquent\Model;
use App\Models\Personal\CategoriaEmpleado;

use App\Models\Proyecto\EmpleadoActividad;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function DepartamentoAcademico()
    {
        return $this->belongsTo(DepartamentoAcademico::class);
    }
    public function departamento_academico()
    {
        return $this->belongsTo(DepartamentoAcademico::class);
    }

    public function firmaSellos()
    {
        return $this->hasMany(FirmaSelloEmpleado::class);
    }

    // Obtener solo la firma del empleado
    public function firma()
    {
        return $this->hasOne(FirmaSelloEmpleado::class)
            ->where('tipo', 'firma')
            ->where('estado', true);
    }

    // Obtener solo el sello del empleado
    public function sello()
    {
        return $this->hasOne(FirmaSelloEmpleado::class)
            ->where('tipo', 'sello')
            ->where('estado', true);
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

    // relacion muchos a muchos con el modelo Proyecto mediante la tabla intermedia empleado_proyecto
    public function proyectos()
    {
        return $this->belongsToMany(Proyecto::class, 'empleado_proyecto', 'empleado_id', 'proyecto_id');
        //  ->withPivot('id', 'fecha_inicio', 'fecha_fin', 'estado', 'horas_semanales', 'horas_totales', 'created_at', 'updated_at')
        //->withTimestamps();
    }


    public function firmaProyectoPendientes()
    {
        return $this->hasMany(FirmaProyecto::class, 'empleado_id')
            ->where('estado_revision', '!=', 'Aprobado')
            ->whereIn('id', $this->getIdValidos());
    }

    public function firmaProyectoAprobado()
    {
        return $this->hasMany(FirmaProyecto::class, 'empleado_id')
            ->where('estado_revision', 'Aprobado');
    }

    public function firmaProyectoRechazado()
    {
        return $this->hasMany(FirmaProyecto::class, 'empleado_id')
            ->where('estado_revision', 'Rechazado');
    }

    public function firmaProyecto()
    {
        return $this->hasMany(FirmaProyecto::class, 'empleado_id');
    }


    // relacion uno a muchos con EmpleadoActividad
    public function actividades()
    {
        return $this->belongsToMany(Actividad::class, 'actividad_empleado');
    }


    public function getIdValidos()
    {  
        // Mapear las firmas de los proyectos
        
        $proyectos = $this->firmaProyecto->map(function ($firma) {
            // validar que el proyecto se encuentre solamente en el estado en el cual se encuentra la firma
            return $firma->id;
            if ($firma->estado_actual->estado_siguiente_id == $firma->proyecto->estado->tipo_estado_id) {
                return $firma->id;
            }
        });

        return $proyectos;
    }

    protected $table = 'empleado';
}
