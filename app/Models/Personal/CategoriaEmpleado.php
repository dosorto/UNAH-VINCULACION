<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaEmpleado extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'categoria';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'categoria_id');
    }
}
