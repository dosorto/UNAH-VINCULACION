<?php
namespace App\Models\Estudiante;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\User;
use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;

class Estudiante extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected static $logAttributes = ['id', 'user_id', 'nombre', 'apellido','cuenta','proyecto_id', 'campus_id', 'centro_facultad_id', 'departamento_academico_id'];
    protected static $logName = 'Estudiante';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'user_id', 'nombre', 'apellido', 'cuenta'])
            ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'nombre', 
        'apellido', 
        'cuenta',
        'user_id',
        'centro_facultad_id',
        'departamento_academico_id'
    ];

    protected $table = 'estudiante';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    

    public function proyecto()
    {
        return $this->hasMany(Proyecto::class, 'id');
    }

    public function proyectosEstudiante()
    {
        return $this->hasMany(EstudianteProyecto::class, 'estudiante_id');
    }

    public function DepartamentoAcademico()
    {
        return $this->belongsTo(DepartamentoAcademico::class);
    }

    public function departamento_academico()
    {
        return $this->belongsTo(DepartamentoAcademico::class);
    }
    
    public function proyectos()
    {
        return $this->belongsToMany(Proyecto::class, 'estudiante_proyecto', 'estudiante_id', 'proyecto_id')
                    ->using(EstudianteProyecto::class)
                    ->withPivot('tipo_participacion_id')
                    ->withTimestamps();
    }

    public function tipoParticipaciones()
    {
        return $this->belongsToMany(TipoParticipacion::class, 'estudiante_proyecto', 'estudiante_id', 'tipo_participacion_id')
                    ->withPivot('proyecto_id');
    }


    public function participacionesProyectos()
    {
        return $this->hasMany(EstudianteProyecto::class, 'estudiante_id')
                    ->with(['proyecto', 'tipoParticipacion']);
    }
    
    public function centro_facultad()
    {
        return $this->belongsTo(FacultadCentro::class, 'centro_facultad_id');
    }
}