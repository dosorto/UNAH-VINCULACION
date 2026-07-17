<?php
namespace App\Models\InformeFinal;
use App\Models\Estudiante\Estudiante;
use Illuminate\Database\Eloquent\Model;
class InformeFinalEstudiante extends Model { protected $table = 'informe_final_estudiantes'; protected $guarded = ['id']; protected $casts = ['horas_dedicadas'=>'decimal:2']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function estudiante(){return $this->belongsTo(Estudiante::class);} }
