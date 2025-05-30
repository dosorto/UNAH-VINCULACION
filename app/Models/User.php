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
/*
      $table->id();
            $table->string('name')->nullable();
            $table->string('microsoft_id')->nullable()->unique();
            $table->string('given_name')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->foreignId('active_role_id')->nullable()->index();
            $table->string('password')->nullable();
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
*/
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
        'microsoft_id',
        'given_name',
        'surname',
    
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
        $words = array_values(array_filter(explode(' ', trim($this->name))));
        $inicial_nombre = isset($words[0]) ? mb_substr($words[0], 0, 1) : '';
        $inicial_segundo = isset($words[1]) ? mb_substr($words[1], 0, 1) : '';
        return $inicial_nombre . $inicial_segundo;
    }

    // relacion uno a uno con el empleado
    public function empleado()
    {
        return $this->hasOne(Empleado::class);
    }

    // relacion uno a uno con el estudiante
    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'user_id');
    }


    public function getActiveRoleAttribute()
    {
        return \Spatie\Permission\Models\Role::find($this->active_role_id);
    }



}
