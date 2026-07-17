<?php
namespace App\Models\InformeFinal;
use App\Models\Personal\Empleado;
use Illuminate\Database\Eloquent\Model;
class InformeFinalEquipoDocente extends Model { protected $table = 'informe_final_equipo_docente'; protected $guarded = ['id']; protected $casts = ['es_coordinador' => 'boolean', 'horas_dedicadas' => 'decimal:2']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function empleado(){return $this->belongsTo(Empleado::class);} }
