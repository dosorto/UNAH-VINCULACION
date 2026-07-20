<?php
namespace App\Models\InformeFinal;
use App\Models\Personal\Empleado;
use Illuminate\Database\Eloquent\Model;
class InformeFinalVoluntario extends Model { protected $table = 'informe_final_voluntarios'; protected $guarded = ['id']; protected $casts = ['horas_dedicadas'=>'decimal:2']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function empleado(){return $this->belongsTo(Empleado::class);} }
