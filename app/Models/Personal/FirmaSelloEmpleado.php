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

    public static function boot()
    {
        parent::boot();

        // Asegurarse de que el campo tipo siempre tenga un valor
        static::creating(function ($model) {
            FirmaSelloEmpleado::where('empleado_id', $model->empleado_id)
            ->where('tipo', $model->tipo)
            ->update(['estado' => false]);
        });
    }

    // Relación con el modelo Empleado
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    protected $table = 'firma_sello_empleado';
}
