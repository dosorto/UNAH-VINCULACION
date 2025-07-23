<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EjesPrioritariosUnah extends Model
{
    use HasFactory;

    protected $table = 'ejes_prioritarios_unah';

    protected $fillable = [
        'nombre',
    ];

    // Relación muchos a muchos con Proyecto
    public function proyectos()
    {
        return $this->belongsToMany(
            Proyecto::class,
            'eje_prioritario_proyecto',
            'ejes_prioritarios_unah_id',
            'proyecto_id'
        );
    }
}
