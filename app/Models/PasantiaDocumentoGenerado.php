<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PasantiaDocumentoGenerado extends Model { protected $table='pasantia_documentos_generados'; protected $fillable=['pasantia_id','tipo','archivo','nombre_original','version','generado_por','generado_en']; protected $casts=['generado_en'=>'datetime']; public function pasantia(){return $this->belongsTo(Pasantia::class); } }
