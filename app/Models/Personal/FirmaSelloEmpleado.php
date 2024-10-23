<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FirmaSelloEmpleado extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'empleado_id',
        'tipo',
        'ruta_storage',
        'estado',
    ];

    // Relación con el modelo Empleado
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    protected $table = 'firma_sello_empleado';
}
