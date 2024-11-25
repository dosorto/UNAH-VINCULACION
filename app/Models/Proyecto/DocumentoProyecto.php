<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoProyecto extends Model
{
    use HasFactory;


    // tabla documento_proyecto
    protected $table = 'documento_proyecto';

    protected $fillable = [
        'proyecto_id',
        'tipo_documento',
        'documento_url',
    ];

    // relacion uno a uno inversa con el modelo Proyecto
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id', 'id');
    }

}
