<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;

class FirmaProyecto extends Model
{
    use HasFactory;


    protected $table = 'firma_proyecto';

    protected $fillable = [
        'proyecto_id',
        'empleado_id',
        'cargo_firma_id',
        'firma_id',
        'estado_revision',
        'hash',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function cargo_firma()
    {
        return $this->belongsTo(CargoFirma::class, 'cargo_firma_id');
    }


    // recuperar la firma del 
}
