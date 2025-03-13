<?php

namespace App\Models\Comentarios;

use App\Models\Personal\Empleado;
use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    //


    protected $table = 'comentario';

    protected $fillable = [
        'contenido',
        'empleado_id',
        'categoria_comentario_id',
        'visto'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    } 

    public function categoriaComentario()
    {
        return $this->belongsTo(CategoriaComentario::class);
    }

    public function respuestaComentario()
    {
        return $this->hasMany(RespuestaComentario::class);
    }

    public function getContenidoAttribute($value)
    {
        return ucfirst($value);
    }
}
