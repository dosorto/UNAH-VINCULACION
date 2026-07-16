<?php
namespace App\Models\InformeFinal;
use Illuminate\Database\Eloquent\Model;
class InformeFinalAnexo extends Model { protected $table = 'informe_final_anexos'; protected $guarded = ['id']; protected $casts = ['fecha'=>'date']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function resultado(){return $this->belongsTo(InformeFinalResultado::class,'informe_final_resultado_id');} public function actividad(){return $this->belongsTo(InformeFinalActividad::class,'informe_final_actividad_id');} }
