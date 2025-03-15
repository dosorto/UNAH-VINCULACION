<?php

namespace App\Models\Estudiante;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\User;

class Estudiante extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'user_id', 'nombre', 'apellido', 'cuenta'];

    protected static $logName = 'Estudiante';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'user_id', 'nombre', 'apellido', 'cuenta'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'user_id',
        'nombre', 
        'apellido', 
        'cuenta'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id',);
    }

    protected $table = 'estudiante';
}
