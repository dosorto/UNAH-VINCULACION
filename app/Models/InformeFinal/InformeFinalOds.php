<?php
namespace App\Models\InformeFinal;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\Od;
use Illuminate\Database\Eloquent\Model;
class InformeFinalOds extends Model { protected $table = 'informe_final_ods'; protected $guarded = ['id']; public function informe(){return $this->belongsTo(InformeFinalProyecto::class,'informe_final_proyecto_id');} public function ods(){return $this->belongsTo(Od::class,'ods_id');} public function meta(){return $this->belongsTo(MetaContribuye::class,'meta_contribuye_id');} }
