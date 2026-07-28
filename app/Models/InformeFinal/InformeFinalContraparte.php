<?php
namespace App\Models\InformeFinal;
use App\Models\Proyecto\EntidadContraparte;
use App\Models\Proyecto\EntidadContraparteProyecto;
use Illuminate\Database\Eloquent\Model;
class InformeFinalContraparte extends Model
{
    protected $table = 'informe_final_contrapartes';
    protected $guarded = ['id'];
    protected $casts = [
        'existe_apoyo' => 'boolean',
        'aporte_monetario' => 'decimal:2',
        'aporte_especie' => 'decimal:2',
    ];

    public function informe()
    {
        return $this->belongsTo(InformeFinalProyecto::class, 'informe_final_proyecto_id');
    }

    public function entidad()
    {
        return $this->belongsTo(EntidadContraparte::class, 'entidad_contraparte_id');
    }

    public function presupuestoDetalles()
    {
        return $this->hasMany(InformeFinalPresupuestoDetalle::class);
    }
}
