<?php

namespace App\Models\Comentarios;

use App\Models\Personal\Empleado;
use Illuminate\Database\Eloquent\Model;

class RespuestaComentario extends Model
{
    //

    protected $table = 'respuesta_comentario';

    protected $fillable = [
        'contenido',
        'empleado_id',
        'comentario_id'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    } 

    public function comentario()
    {
        return $this->belongsTo(Comentario::class);
    }

    public function getContenidoAttribute($value)
    {
        return ucfirst($value);
    }
}
