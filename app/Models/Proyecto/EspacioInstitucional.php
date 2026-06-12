<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EspacioInstitucional extends Model
{
    protected $table = 'proyecto_espacio_institucional';

    protected $fillable = [
        'proyecto_id',
        'descripcion',
        'ubicacion',
        'unidad_gestora',
        'tiempo_uso_horas',
    ];

    protected $casts = [
        'tiempo_uso_horas' => 'decimal:2',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
}
