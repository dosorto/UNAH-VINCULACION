<?php

namespace App\Models\Comentarios;

use Illuminate\Database\Eloquent\Model;

class CategoriaComentario extends Model
{
    //

    protected $table = 'categoria_comentario';

    protected $fillable = [
        'nombre_categoria'
    ];

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'categoria_comentario_id');
    }   

    public function getNombreCategoriaAttribute($value)
    {
        return ucfirst($value);
    }
}
