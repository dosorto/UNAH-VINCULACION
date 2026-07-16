<?php
namespace App\Models\InformeFinal;
use Illuminate\Database\Eloquent\Model;
class InformeFinalPresupuestoDetalle extends Model { protected $table = 'informe_final_presupuesto_detalles'; protected $guarded = ['id']; protected $casts = ['cantidad'=>'decimal:2','costo_unitario'=>'decimal:2']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function contraparte(){return $this->belongsTo(InformeFinalContraparte::class,'informe_final_contraparte_id');} public function getCostoTotalAttribute(): float{return round((float)$this->cantidad*(float)$this->costo_unitario,2);} }
