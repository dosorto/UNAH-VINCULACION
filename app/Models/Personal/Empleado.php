<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Models\User;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\DepartamentoAcademico;

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
        'categoria',
        'user_id',
        'campus_id',
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

    protected $table = 'empleado';
}
