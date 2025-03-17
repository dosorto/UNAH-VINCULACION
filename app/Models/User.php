<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Models\Personal\Empleado;
use App\Models\Estudiante\Estudiante;
use Spatie\Permission\Models\Role;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'email', 'password'])
        ->setDescriptionForEvent(fn (string $eventName) => "El usuario {$this->name} ha sido {$eventName}");
    }



    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active_role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get Inicials of User
     * 
     * @return string
     */
    public function getInitials(): string
    {
        $name = explode(' ', $this->name);
        $initials = '';
        foreach ($name as $n) {
            if (empty($n)) {
                continue;
            }
            $initials .= $n[0];
        }
        return $initials;
    }

    // relacion uno a uno con el empleado
    public function empleado()
    {
        return $this->hasOne(Empleado::class);
    }

    // relacion uno a uno con el estudiante
    public function estudiante()
    {
        return $this->hasOne(Estudiante::class);
    }


    public function getActiveRoleAttribute()
    {
        return \Spatie\Permission\Models\Role::find($this->active_role_id);
    }



}
