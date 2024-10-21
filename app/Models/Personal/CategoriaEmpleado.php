<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaEmpleado extends Model
{
    use HasFactory;

    protected $table = 'categoria';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'categoria_id');
    }
}
