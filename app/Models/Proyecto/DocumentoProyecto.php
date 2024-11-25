<?php

namespace App\Models\Proyecto;

use App\Models\Estado\EstadoProyecto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DocumentoProyecto extends Model
{
    use HasFactory;


    // tabla documento_proyecto
    protected $table = 'proyecto_documento';

    protected $fillable = [
        'proyecto_id',
        'tipo_documento',
        'documento_url',
    ];

    // relacion uno a muchos con el modelo FirmaProyecto
    public function firma_documento()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable');
    }

    // relacion uno a muchos con el modelo do_proyecto
    public function estado_documento()
    {
        return $this->morphMany(EstadoProyecto::class, 'estadoable');
    }

    // relacion uno a uno inversa con el modelo Proyecto
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id', 'id');
    }

    public function getEstadoAttribute()
    {
        return $this->estado_documento()
            ->where('es_actual', true)
            ->first();
    }
}
