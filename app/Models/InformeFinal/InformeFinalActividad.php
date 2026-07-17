<?php
namespace App\Models\InformeFinal;
use App\Models\Proyecto\Actividad;
use Illuminate\Database\Eloquent\Model;
class InformeFinalActividad extends Model { protected $table = 'informe_final_actividades'; protected $guarded = ['id']; protected $casts = ['fecha_inicial'=>'date','fecha_final'=>'date','horas_dedicadas'=>'decimal:2']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function actividad(){return $this->belongsTo(Actividad::class);} }
