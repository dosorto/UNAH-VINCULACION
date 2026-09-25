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
                $contenido = Pdf::loadView('pdf.pasantias.form-013', [
                    'registro' => $registro,
                    'secciones' => $this->seccionesFormulario(),
                ])
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

    /** Mapeo institucional único reutilizado por el detalle y el PDF. */
    public function seccionesFormulario(): array
    {
        return [
            'I. Información general del estudiante' => ['Fecha de registro'=>'fecha_registro','Facultad/Centro Universitario Regional/Instituto Tecnológico'=>'facultad_centro','Unidad académica, escuela o departamento'=>'escuela_departamento','Carrera'=>'carrera','Número de cuenta'=>'numero_cuenta','Nombre completo del estudiante'=>'nombre_estudiante','Número de celular'=>'celular_estudiante','Correo electrónico institucional'=>'correo_institucional','Correo electrónico personal'=>'correo_personal'],
            'II. Información de la pasantía' => ['Tipo de pasantía (nacional/internacional)'=>'tipo_pasantia','Fecha de inicio'=>'fecha_inicio','Fecha de finalización'=>'fecha_finalizacion','Duración de la pasantía en semanas'=>'duracion_semanas','Número total de horas programadas'=>'total_horas','Promedio de horas semanales programadas'=>'horas_semanales','Pasantía obligatoria (Sí/No)'=>'pasantia_obligatoria','Otorgamiento de créditos académicos (Sí/No)'=>'otorga_creditos','Cantidad de créditos académicos'=>'cantidad_creditos','Modalidad de ejecución'=>'modalidad_ejecucion'],
            'III. Descripción de la experiencia y resultados' => ['Descripción de la experiencia y resultados'=>'descripcion_experiencia','Descripción del cargo'=>'descripcion_cargo','Resumen de responsabilidades y tareas'=>'resumen_responsabilidades','Nombre del departamento o área'=>'area_departamento','Área de conocimiento que se aplicará'=>'area_conocimiento','Asignaturas que se aplicarán en la pasantía'=>'asignaturas','Código de asignatura'=>'codigo_asignatura','Nombre de asignatura'=>'nombre_asignatura','Descripción de conocimientos teóricos'=>'descripcion_conocimientos_teoricos','Habilidades por desarrollar'=>'habilidades_desarrollar','Pasantía remunerada (Sí/No)'=>'pasantia_remunerada','Monto de remuneración'=>'monto_remuneracion'],
            'IV. Institución u organización' => ['Nombre completo de la institución'=>'nombre_institucion','Dirección de la sede principal'=>'direccion_institucion','Ciudad de la institución'=>'ciudad_institucion','País de la institución'=>'pais_institucion','Representante legal'=>'representante_legal','Número de teléfono de la institución'=>'telefono_representante','Correo electrónico de recursos humanos'=>'correo_rrhh','Tipo de institución/organización'=>'tipo_institucion','Sector institucional'=>'sector_institucion','Nombre del contacto directo'=>'nombre_contacto_directo','Número de celular del contacto directo'=>'celular_contacto_directo','Correo electrónico del contacto directo'=>'correo_contacto_directo','Cargo del jefe directo'=>'cargo_contacto_directo','Grado académico del jefe directo'=>'grado_academico_contacto_directo','Tipo de instrumento que formaliza la pasantía'=>'tipo_instrumento','Compromisos asumidos por la institución'=>'compromisos_institucion'],
            'V. Docente supervisor' => ['Nombre completo del docente supervisor'=>'nombre_docente_supervisor','Número de empleado del docente'=>'numero_empleado_docente','Número de celular del docente'=>'celular_docente','Correo electrónico del docente'=>'correo_docente','Categoría del docente'=>'categoria_docente','Departamento del docente'=>'departamento_docente','Jornada laboral del docente'=>'jornada_laboral_docente','Ubicación del cubículo en la UNAH'=>'ubicacion_cubiculo_docente'],
            'VI. Firmas' => ['Coordinador de la carrera (nombre y firma)'=>'nombre_firma_coordinador','Firma del coordinador de la carrera'=>'firma_coordinador','Supervisor de la pasantía (nombre y firma)'=>'nombre_firma_supervisor','Firma del supervisor de la pasantía'=>'firma_supervisor','Estudiante que realiza la pasantía (nombre y firma)'=>'nombre_firma_estudiante','Firma del estudiante'=>'firma_estudiante'],
            'VII. Documentos adjuntos' => ['Carta formal de solicitud a la unidad académica'=>'archivo_carta_formalizacion','Convenio marco entre la UNAH y la entidad (cuando se tenga)'=>'archivo_convenio_marco'],
        ];
    }
}
