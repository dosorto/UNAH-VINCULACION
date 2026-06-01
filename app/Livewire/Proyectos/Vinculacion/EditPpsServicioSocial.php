<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Demografia\Aldea;
use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Models\PpsServicioSocial;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use Illuminate\Contracts\View\View;

class EditPpsServicioSocial extends CreatePpsServicioSocial
{
    public PpsServicioSocial $registro;
    public ?string $archivo_carta_formalizacion_actual = null;
    public ?string $archivo_convenio_marco_actual = null;

    public function mount(int $id): void
    {
        $registro = PpsServicioSocial::findOrFail($id);
        $this->registro = $registro;

        if ($registro->estado !== 'borrador') {
            Notification::make()
                ->title('Edicion no disponible')
                ->body('Solo los registros en estado borrador pueden editarse.')
                ->warning()
                ->send();

            $this->redirectRoute('pps-servicio-social.show', ['id' => $registro->id]);

            return;
        }

        abort_unless($this->canEditRecord($registro), 403);

        $this->fillFromRegistro($registro);
    }

    public function guardar(): void
    {
        $this->registro->refresh();

        if ($this->registro->estado !== 'borrador') {
            Notification::make()
                ->title('Edicion bloqueada')
                ->body('El registro ya no esta en borrador y no puede modificarse.')
                ->danger()
                ->send();

            $this->redirectRoute('pps-servicio-social.show', ['id' => $this->registro->id]);

            return;
        }

        abort_unless($this->canEditRecord($this->registro), 403);

        $this->resetErrorBag();
        $this->validate($this->rules(), [], $this->validationAttributes());

        // TODO: Definir si se deben eliminar archivos antiguos cuando el usuario reemplaza un adjunto.
        $archivoCarta = $this->archivo_carta_formalizacion_actual;
        if ($this->carta_formalizacion_aplica === 'No') {
            $archivoCarta = null;
        } elseif ($this->carta_formalizacion_archivo) {
            $archivoCarta = $this->carta_formalizacion_archivo->store('pps-servicio-social/documentos', 'public');
        }

        $archivoConvenio = $this->archivo_convenio_marco_actual;
        if ($this->convenio_marco_aplica === 'No') {
            $archivoConvenio = null;
        } elseif ($this->convenio_marco_archivo) {
            $archivoConvenio = $this->convenio_marco_archivo->store('pps-servicio-social/documentos', 'public');
        }

        $this->registro->update([
            'facultad_centro' => $this->nombreFacultadCentro(),
            'carrera' => $this->nombreCarrera(),
            'numero_cuenta' => $this->numero_cuenta,
            'nombre_estudiante' => $this->estudiante_nombre_completo,
            'celular_estudiante' => $this->estudiante_celular,
            'correo_institucional' => $this->estudiante_correo_institucional,
            'correo_personal' => $this->estudiante_correo_personal ?: null,
            'tipo_pps_ss' => $this->tipo_pps_ss,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_finalizacion' => $this->fecha_finalizacion,
            'tipo_instrumento' => $this->instrumentoOpciones[$this->tipo_instrumento] ?? $this->tipo_instrumento,
            'territorio_ejecucion' => $this->territorio_ejecucion,
            'departamento' => $this->territorio_ejecucion === 'Nacional' ? $this->nombreDepartamento() : null,
            'municipio' => $this->territorio_ejecucion === 'Nacional' ? $this->nombreMunicipio() : null,
            'aldea_ciudad' => $this->territorio_ejecucion === 'Nacional' ? $this->nombreAldeaCiudad() : null,
            'caserio' => $this->territorio_ejecucion === 'Nacional' ? ($this->caserio ?: null) : null,
            'descripcion_tipo_pps' => $this->descripcion_tipo_pps ?: null,
            'total_horas' => (int) $this->total_horas,
            'area_realizacion' => $this->area_realizacion ?: null,
            'resumen_responsabilidades' => $this->resumen_responsabilidades ?: null,
            'modalidad_ejecucion' => $this->modalidad_ejecucion,
            'nombre_institucion' => $this->institucion_nombre,
            'compromisos_institucion' => $this->institucion_compromisos ?: null,
            'direccion_institucion' => $this->institucion_direccion ?: null,
            'representante_legal' => $this->institucion_representante ?: null,
            'telefono_representante' => $this->institucion_telefono ?: null,
            'correo_rrhh' => $this->institucion_correo_rrhh ?: null,
            'tipo_institucion' => $this->tipoInstitucionOpciones[$this->institucion_tipo] ?? ($this->institucion_tipo ?: null),
            'sector_institucion' => $this->sectorOpciones[$this->institucion_sector] ?? ($this->institucion_sector ?: null),
            'nombre_jefe_directo' => $this->jefe_directo_nombre,
            'celular_jefe_directo' => $this->jefe_directo_celular ?: null,
            'correo_jefe_directo' => $this->jefe_directo_correo ?: null,
            'cargo_jefe_directo' => $this->jefe_directo_cargo ?: null,
            'grado_academico_jefe_directo' => $this->jefe_directo_grado ?: null,
            'nombre_docente_supervisor' => $this->docente_supervisor_nombre,
            'numero_empleado_docente' => $this->docente_numero_empleado ?: null,
            'celular_docente' => $this->docente_celular ?: null,
            'correo_docente' => $this->docente_correo ?: null,
            'categoria_docente' => $this->docente_categoria ?: null,
            'departamento_docente' => $this->docente_departamento ?: null,
            'jornada_laboral_docente' => $this->docente_jornada ?: null,
            'ubicacion_cubiculo_docente' => $this->docente_cubiculo ?: null,
            'adjunta_carta_formalizacion' => $this->carta_formalizacion_aplica === 'Si',
            'archivo_carta_formalizacion' => $archivoCarta,
            'adjunta_convenio_marco' => $this->convenio_marco_aplica === 'Si',
            'archivo_convenio_marco' => $archivoConvenio,
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Registro actualizado')
            ->body('El FORM-DVUS-015/016 fue actualizado correctamente.')
            ->success()
            ->send();

        $this->redirectRoute('pps-servicio-social.show', ['id' => $this->registro->id]);
    }

    protected function canEditRecord(PpsServicioSocial $registro): bool
    {
        return $registro->created_by !== null
            && auth()->id() !== null
            && (int) $registro->created_by === (int) auth()->id();
    }

    protected function fillFromRegistro(PpsServicioSocial $registro): void
    {
        $this->facultad_centro_id = $this->findIdByName(FacultadCentro::class, $registro->facultad_centro);
        $this->carrera_id = $this->findIdByName(Carrera::class, $registro->carrera);

        $this->numero_cuenta = $registro->numero_cuenta;
        $this->estudiante_nombre_completo = $registro->nombre_estudiante;
        $this->estudiante_celular = $registro->celular_estudiante;
        $this->estudiante_correo_institucional = $registro->correo_institucional;
        $this->estudiante_correo_personal = $registro->correo_personal ?? '';

        $this->tipo_pps_ss = $registro->tipo_pps_ss;
        $this->fecha_inicio = $registro->fecha_inicio?->format('Y-m-d') ?? '';
        $this->fecha_finalizacion = $registro->fecha_finalizacion?->format('Y-m-d') ?? '';
        $this->tipo_instrumento = $this->optionKeyFromStoredValue($this->instrumentoOpciones, $registro->tipo_instrumento);
        $this->territorio_ejecucion = $registro->territorio_ejecucion ?: 'Nacional';

        $this->departamento_id = $this->findIdByName(Departamento::class, $registro->departamento);
        $this->municipio_id = $this->findIdByName(Municipio::class, $registro->municipio, [
            'departamento_id' => $this->departamento_id,
        ]);
        $this->fillAldeaCiudad($registro->aldea_ciudad);
        $this->caserio = $registro->caserio ?? '';

        $this->descripcion_tipo_pps = $registro->descripcion_tipo_pps ?? '';
        $this->total_horas = (string) $registro->total_horas;
        $this->area_realizacion = $registro->area_realizacion ?? '';
        $this->resumen_responsabilidades = $registro->resumen_responsabilidades ?? '';
        $this->modalidad_ejecucion = $registro->modalidad_ejecucion;

        $this->institucion_nombre = $registro->nombre_institucion;
        $this->institucion_compromisos = $registro->compromisos_institucion ?? '';
        $this->institucion_direccion = $registro->direccion_institucion ?? '';
        $this->institucion_representante = $registro->representante_legal ?? '';
        $this->institucion_telefono = $registro->telefono_representante ?? '';
        $this->institucion_correo_rrhh = $registro->correo_rrhh ?? '';
        $this->institucion_tipo = $this->optionKeyFromStoredValue($this->tipoInstitucionOpciones, $registro->tipo_institucion);
        $this->institucion_sector = $this->optionKeyFromStoredValue($this->sectorOpciones, $registro->sector_institucion);

        $this->jefe_directo_nombre = $registro->nombre_jefe_directo;
        $this->jefe_directo_celular = $registro->celular_jefe_directo ?? '';
        $this->jefe_directo_correo = $registro->correo_jefe_directo ?? '';
        $this->jefe_directo_cargo = $registro->cargo_jefe_directo ?? '';
        $this->jefe_directo_grado = $registro->grado_academico_jefe_directo ?? '';

        $this->docente_supervisor_nombre = $registro->nombre_docente_supervisor;
        $this->docente_numero_empleado = $registro->numero_empleado_docente ?? '';
        $this->docente_celular = $registro->celular_docente ?? '';
        $this->docente_correo = $registro->correo_docente ?? '';
        $this->docente_categoria = $registro->categoria_docente ?? '';
        $this->docente_departamento = $registro->departamento_docente ?? '';
        $this->docente_jornada = $registro->jornada_laboral_docente ?? '';
        $this->docente_cubiculo = $registro->ubicacion_cubiculo_docente ?? '';

        $this->carta_formalizacion_aplica = $registro->adjunta_carta_formalizacion ? 'Si' : 'No';
        $this->convenio_marco_aplica = $registro->adjunta_convenio_marco ? 'Si' : 'No';
        $this->archivo_carta_formalizacion_actual = $registro->archivo_carta_formalizacion;
        $this->archivo_convenio_marco_actual = $registro->archivo_convenio_marco;
    }

    protected function fillAldeaCiudad(?string $aldeaCiudad): void
    {
        if (!$aldeaCiudad || !$this->municipio_id) {
            return;
        }

        foreach (array_map('trim', explode('/', $aldeaCiudad)) as $nombre) {
            if (!$this->aldea_id) {
                $this->aldea_id = $this->findIdByName(Aldea::class, $nombre, [
                    'municipio_id' => $this->municipio_id,
                ]);
            }

            if (!$this->ciudad_id) {
                $this->ciudad_id = $this->findIdByName(Ciudad::class, $nombre, [
                    'municipio_id' => $this->municipio_id,
                ]);
            }
        }
    }

    protected function optionKeyFromStoredValue(array $options, ?string $storedValue): string
    {
        if (!$storedValue) {
            return '';
        }

        if (array_key_exists($storedValue, $options)) {
            return $storedValue;
        }

        $key = array_search($storedValue, $options, true);

        return $key === false ? $storedValue : (string) $key;
    }

    protected function findIdByName(string $modelClass, ?string $name, array $wheres = []): ?int
    {
        if (!$name) {
            return null;
        }

        $query = $modelClass::query()->where('nombre', $name);

        foreach ($wheres as $column => $value) {
            if ($value !== null) {
                $query->where($column, $value);
            }
        }

        $id = $query->value('id');

        return $id ? (int) $id : null;
    }

    public function render(): View
    {
        $view = parent::render();

        return view('livewire.proyectos.vinculacion.edit-pps-servicio-social', array_merge($view->getData(), [
            'modoEdicion' => true,
            'registroEdicion' => $this->registro,
        ]));
    }
}
