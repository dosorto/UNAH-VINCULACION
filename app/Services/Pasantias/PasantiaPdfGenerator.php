<?php
namespace App\Services\Pasantias;
use App\Models\Pasantia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
class PasantiaPdfGenerator {
 public function generar(Pasantia $registro, int $usuarioId, string $tipo='formulario'): \App\Models\PasantiaDocumentoGenerado {
  $base=['nombre_estudiante'=>'estudiante','numero_cuenta'=>'número de cuenta','carrera'=>'carrera','facultad_centro'=>'centro','nombre_institucion'=>'institución','modalidad_ejecucion'=>'modalidad','total_horas'=>'horas','nombre_contacto_directo'=>'destinatario/contacto','cargo_contacto_directo'=>'cargo del contacto'];
  if($tipo==='autorizacion_pps') $base += ['fecha_inicio'=>'fecha de inicio','fecha_finalizacion'=>'fecha de finalización','nombre_firma_coordinador'=>'coordinador','firma_coordinador'=>'firma del coordinador'];
  $faltantes=collect($base)->filter(fn($l,$f)=>blank($registro->{$f})); if($faltantes->isNotEmpty()) throw new \RuntimeException('No se puede generar '.($tipo==='solicitud_practica'?'la Solicitud':'la Autorización').': faltan '.implode(', ',$faltantes->values()->all()));
  $version=((int)$registro->documentosGenerados()->where('tipo',$tipo)->max('version'))+1; $nombre='FORM-DVUS-013-'.$tipo.'-'.$registro->codigo_registro.'-v'.$version.'.pdf'; $ruta='pasantias/generados/'.$registro->id.'/'.$nombre;
  try { $contenido=Pdf::loadView('pdf.pasantias.documento',['registro'=>$registro,'tipo'=>$tipo])->setPaper('letter')->setOption('defaultFont','Arial')->output(); Storage::disk('local')->put($ruta,$contenido); } catch(\Throwable $e) { Log::error('Error almacenando documento de Pasantía',['registro_id'=>$registro->id,'tipo'=>$tipo,'error'=>$e->getMessage()]); throw new \RuntimeException('No se pudo almacenar el documento.'); }
  return $registro->documentosGenerados()->create(['tipo'=>$tipo,'archivo'=>$ruta,'nombre_original'=>$nombre,'version'=>$version,'generado_por'=>$usuarioId,'generado_en'=>now()]);
 }
}
