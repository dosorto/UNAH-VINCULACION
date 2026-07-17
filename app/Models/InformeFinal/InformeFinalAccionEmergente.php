<?php
namespace App\Models\InformeFinal;
use Illuminate\Database\Eloquent\Model;
class InformeFinalAccionEmergente extends Model { protected $table = 'informe_final_acciones_emergentes'; protected $guarded = ['id']; protected $casts = ['fecha'=>'date','horas'=>'decimal:2']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function resultado(){return $this->belongsTo(InformeFinalResultado::class,'informe_final_resultado_id');} }
