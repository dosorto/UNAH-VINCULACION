<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;


class AdministradorFacultadCentro extends Model
{
    use HasFactory;

    /*
        administrador_centro_facultad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_facultad_id')->constrained('centro_facultad');
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('es_director');
            $table->boolean('estado');
            $table->softDeletes();
            $table->timestamps();
    */

    protected $fillable = [
        'centro_facultad_id',
        'user_id',
        'es_director',
        'estado'
    ];

    public function centroFacultad()
    {
        return $this->belongsTo(FacultadCentro::class, 'centro_facultad_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    protected $table = 'administrador_centro_facultad';
  

}
