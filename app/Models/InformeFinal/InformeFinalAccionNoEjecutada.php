<?php
namespace App\Models\InformeFinal;
use Illuminate\Database\Eloquent\Model;
class InformeFinalAccionNoEjecutada extends Model { protected $table = 'informe_final_acciones_no_ejecutadas'; protected $guarded = ['id']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} }
