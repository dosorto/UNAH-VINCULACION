<?php
namespace App\Models\InformeFinal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class InformeFinalAnexo extends Model { use HasFactory; protected $table = 'informe_final_anexos'; protected $guarded = ['id']; protected $casts = ['fecha'=>'date','tamano_bytes'=>'integer']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function contraparte(){return $this->belongsTo(InformeFinalContraparte::class,'informe_final_contraparte_id');} public function instrumento(){return $this->belongsTo(\App\Models\Proyecto\InstrumenFormalizacion::class,'instrumento_formalizacion_id');} public function resultado(){return $this->belongsTo(InformeFinalResultado::class,'informe_final_resultado_id');} public function actividad(){return $this->belongsTo(InformeFinalActividad::class,'informe_final_actividad_id');} }
