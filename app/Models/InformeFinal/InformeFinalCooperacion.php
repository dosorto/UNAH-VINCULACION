<?php
namespace App\Models\InformeFinal;
use Illuminate\Database\Eloquent\Model;
class InformeFinalCooperacion extends Model { protected $table = 'informe_final_cooperacion'; protected $guarded = ['id']; protected $casts = ['horas_dedicadas'=>'decimal:2']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} }
