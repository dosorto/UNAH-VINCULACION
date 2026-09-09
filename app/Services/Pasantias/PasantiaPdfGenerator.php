<?php

namespace App\Services\Pasantias;

use App\Models\Pasantia;
use App\Support\Fichas\FirmaImagen;
use App\Support\Pasantias\PasantiaDocumentoRequirements;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PasantiaPdfGenerator
{
    public function generar(Pasantia $registro, int $usuarioId, string $tipo = 'formulario'): \App\Models\PasantiaDocumentoGenerado
    {
        if ($tipo !== 'formulario') {
            PasantiaDocumentoRequirements::validate($registro, $tipo);
        } else {
            $required = ['nombre_estudiante' => 'estudiante', 'numero_cuenta' => 'número de cuenta', 'carrera' => 'carrera', 'facultad_centro' => 'centro', 'nombre_institucion' => 'institución', 'modalidad_ejecucion' => 'modalidad', 'total_horas' => 'horas'];
            $missing = collect($required)->filter(fn ($label, $field) => blank($registro->{$field}));
            if ($missing->isNotEmpty()) {
                throw new \RuntimeException('No se puede generar el formulario. Complete: '.implode(', ', $missing->values()->all()).'.');
            }
        }

        $version = ((int) $registro->documentosGenerados()->where('tipo', $tipo)->max('version')) + 1;
        $nombre = 'FORM-DVUS-013-'.$tipo.'-'.$registro->codigo_registro.'-v'.$version.'.pdf';
        $ruta = 'pasantias/generados/'.$registro->id.'/'.$nombre;

        try {
            if ($tipo === 'formulario') {
                $contenido = Pdf::loadView('pdf.pasantias.form-013', ['registro' => $registro])
                    ->setPaper('letter')->setOption('defaultFont', 'Arial')->output();
            } else {
                $contenido = Pdf::loadView('pdf.pps-servicio-social.generado', [
                    'pasantia' => $registro,
                    'tipo' => $tipo,
                    'formData' => $this->formData($registro),
                ])->setPaper('letter')->setOption('isRemoteEnabled', false)
                    ->setOption('isHtml5ParserEnabled', true)->setOption('defaultFont', 'Arial')
                    ->setOption('chroot', realpath(base_path()))->output();
            }

            Storage::disk('local')->put($ruta, $contenido);
        } catch (\Throwable $e) {
            Log::error('Error almacenando documento de Pasantía', ['registro_id' => $registro->id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('No se pudo almacenar el documento.');
        }

        return $registro->documentosGenerados()->create([
            'tipo' => $tipo, 'archivo' => $ruta, 'nombre_original' => $nombre,
            'version' => $version, 'generado_por' => $usuarioId, 'generado_en' => now(),
        ]);
    }

    private function formData(Pasantia $registro): array
    {
        $firma = FirmaImagen::resolver((string) $registro->firma_coordinador, true);

        return ['fields' => [
            'nombre_estudiante' => $registro->nombre_estudiante, 'numero_cuenta' => $registro->numero_cuenta,
            'carrera' => $registro->carrera, 'facultad_centro' => $registro->facultad_centro,
            'nombre_institucion' => $registro->nombre_institucion, 'modalidad_ejecucion' => $registro->modalidad_ejecucion,
            'total_horas' => $registro->total_horas, 'fecha_inicio' => $registro->fecha_inicio,
            'fecha_finalizacion' => $registro->fecha_finalizacion, 'nombre_contacto_directo' => $registro->nombre_contacto_directo,
            'cargo_contacto_directo' => $registro->cargo_contacto_directo, 'ciudad_institucion' => $registro->ciudad_institucion,
            'tipo_pasantia' => $registro->tipo_pasantia,
        ], 'firmas' => ['coordinador' => [
            'nombre' => $registro->nombre_firma_coordinador, 'src' => $firma['src'] ?? null,
        ]]];
    }
}
