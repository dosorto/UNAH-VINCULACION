<?php
namespace App\Models\InformeFinal;
use App\Models\Proyecto\ResultadoEsperado;
use Illuminate\Database\Eloquent\Model;
class InformeFinalResultado extends Model { protected $table = 'informe_final_resultados'; protected $guarded = ['id']; protected $casts = ['meta_numerica'=>'decimal:2','valor_alcanzado'=>'decimal:2','porcentaje_cumplimiento'=>'decimal:2']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function resultadoEsperado(){return $this->belongsTo(ResultadoEsperado::class);} public function accionesEmergentes(){return $this->hasMany(InformeFinalAccionEmergente::class);} }
