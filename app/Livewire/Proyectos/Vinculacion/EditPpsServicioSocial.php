<?php

namespace App\Livewire\Proyectos\Vinculacion;

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
    public string $comentarioRevisor = '';
    public ?string $archivo_carta_formalizacion_actual = null;
    public ?string $archivo_convenio_marco_actual = null;

    public function mount(int $id): void
    {
        $this->bloquearNavegacionPasos = false;

        $registro = PpsServicioSocial::findOrFail($id);
        $this->registro = $registro;
        $this->registroId = $registro->id;
        $this->registroGuardado = true;

        if (!$this->estadoPermiteEdicion($registro) && !$this->esEdicionRevisor($registro)) {
            Notification::make()
                ->title('Edición no disponible')
                ->body('Solo los registros en estado borrador o subsanación editable pueden editarse.')
                ->warning()
                ->send();

            $this->redirectRoute('pps-servicio-social.show', ['id' => $registro->id]);

            return;
        }

        abort_unless($this->canEditRecord($registro), 403);

        $this->fillFromRegistro($registro);
    }

    public function autoGuardarBorrador(): bool
    {
        $this->registro->refresh();

        // Los revisores deben guardar explícitamente y dejar un comentario;
        // el autoguardado no puede modificar un registro en revisión.
        if ($this->esEdicionRevisor($this->registro)) {
            return true;
        }

        if (!$this->estadoPermiteEdicion($this->registro) && !$this->esEdicionRevisor($this->registro)) {
            $this->estadoAutoGuardado = 'error';

            Notification::make()
                ->title('Edición bloqueada')
                ->body('El registro ya no esta en una etapa editable y no puede modificarse.')
                ->danger()
                ->send();

            $this->redirectRoute('pps-servicio-social.show', ['id' => $this->registro->id]);

            return false;
        }

        abort_unless($this->canEditRecord($this->registro), 403);

        return parent::autoGuardarBorrador();
    }

    public function abrirModalEnviar(): void
    {
        if ($this->esEdicionRevisor($this->registro)) {
            Notification::make()
                ->title('Envío no disponible')
                ->body('El revisor debe guardar sus cambios con un comentario; la etapa y sus responsables se conservan.')
                ->warning()
                ->send();

            return;
        }

        parent::abrirModalEnviar();
    }

    public function confirmarEnvio(): void
    {
        if ($this->esEdicionRevisor($this->registro)) {
            Notification::make()
                ->title('Envío no disponible')
                ->body('Los cambios del revisor no reinician el flujo ni solicitan nuevos responsables.')
                ->warning()
                ->send();

            return;
        }

        parent::confirmarEnvio();
    }

    public function guardar(): void
    {
        $this->registro->refresh();

        if (!$this->estadoPermiteEdicion($this->registro) && !$this->esEdicionRevisor($this->registro)) {
            Notification::make()
                ->title('Edición bloqueada')
                ->body('El registro ya no esta en una etapa editable y no puede modificarse.')
                ->danger()
                ->send();

            $this->redirectRoute('pps-servicio-social.show', ['id' => $this->registro->id]);

            return;
        }

        abort_unless($this->canEditRecord($this->registro), 403);

        $this->resetErrorBag();
        $this->validate($this->rules(), $this->messages(), $this->validationAttributes());

        $esRevisor = $this->esEdicionRevisor($this->registro);
        if ($esRevisor) {
            $this->validate([
                'comentarioRevisor' => 'required|string|min:5|max:5000',
            ], [], ['comentarioRevisor' => 'comentario de revisión']);
        }

        if (!$this->autoGuardarBorrador()) {
            return;
        }

        try {
            $this->registro = PpsServicioSocial::findOrFail($this->registroId);
            $payload = $this->payloadParcial();

            $payload['archivo_carta_formalizacion'] = $this->archivo_carta_formalizacion_actual;
            if ($this->carta_formalizacion_archivo) {
                $payload['archivo_carta_formalizacion'] = $this->carta_formalizacion_archivo->store('pps-servicio-social/documentos', 'public');
                $this->carta_formalizacion_aplica = 'Si';
            } elseif ($this->carta_formalizacion_aplica === 'No') {
                $payload['archivo_carta_formalizacion'] = null;
            }

            $payload['archivo_convenio_marco'] = $this->archivo_convenio_marco_actual;
            if ($this->convenio_marco_archivo) {
                $payload['archivo_convenio_marco'] = $this->convenio_marco_archivo->store('pps-servicio-social/documentos', 'public');
                $this->convenio_marco_aplica = 'Si';
            } elseif ($this->convenio_marco_aplica === 'No') {
                $payload['archivo_convenio_marco'] = null;
            }

            $payload['adjunta_carta_formalizacion'] = $this->carta_formalizacion_aplica === 'Si'
                || filled($payload['archivo_carta_formalizacion']);
            $payload['adjunta_convenio_marco'] = $this->convenio_marco_aplica === 'Si'
                || filled($payload['archivo_convenio_marco']);

            $this->registro->update($payload);

            if ($esRevisor) {
                $empleado = auth()->user()?->empleado;
                $tipoEstadoId = $this->registro->estadoActual?->tipo_estado_id;
                if (!$empleado || !$tipoEstadoId) {
                    throw new \RuntimeException('No se pudo identificar al revisor para registrar el comentario.');
                }

                $this->registro->agregarEstado(
                    $empleado,
                    (int) $tipoEstadoId,
                    'Edición de revisor: '.trim($this->comentarioRevisor)
                );
                $this->comentarioRevisor = '';
            }
            $this->estadoAutoGuardado = 'guardado';
        } catch (\Throwable $e) {
            report($e);
            $this->estadoAutoGuardado = 'error';

            Notification::make()
                ->title('Error')
                ->body('No se pudo actualizar el registro. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Registro actualizado')
            ->body('El FORM-DVUS-014 fue actualizado correctamente.')
            ->success()
            ->send();

        $this->redirectRoute('pps-servicio-social.show', ['id' => $this->registro->id]);
    }

    protected function ensureRegistroBorrador(): PpsServicioSocial
    {
        $registro = PpsServicioSocial::findOrFail($this->registroId);

        if (!$this->estadoPermiteEdicion($registro)) {
            throw new \RuntimeException('Solo los registros en borrador o subsanación editable pueden autoguardarse.');
        }

        return $registro;
    }

    protected function canEditRecord(PpsServicioSocial $registro): bool
    {
        $esCreador = $registro->created_by !== null
            && auth()->id() !== null
            && (int) $registro->created_by === (int) auth()->id();

        return $esCreador || $this->esEdicionRevisor($registro);
    }

    public function esEdicionRevisor(?PpsServicioSocial $registro = null): bool
    {
        $registro ??= $this->registro;
        $user = auth()->user();

        return in_array($registro->estado, ['enviado', 'en_revision'], true)
            && $user !== null
            && $registro->usuarioPuedeRevisar($user);
    }

    private function estadoPermiteEdicion(PpsServicioSocial $registro): bool
    {
        return in_array($registro->estado, ['borrador', 'subsanacion'], true);
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
        $this->modalidad_ejecucion = $registro->modalidad_ejecucion;
        $this->region = $registro->region ?? '';
        $this->pais = $registro->pais ?? '';
        $this->departamento_provincia = $registro->departamento_provincia ?? '';

        $this->departamento_id = $this->findIdByName(Departamento::class, $registro->departamento);
        $this->municipio_id = $this->findIdByName(Municipio::class, $registro->municipio, [
            'departamento_id' => $this->departamento_id,
        ]);
        $this->municipio_texto = $this->territorio_ejecucion === 'Internacional' ? ($registro->municipio ?? '') : '';
        $this->fillAldeaCiudad($registro->aldea_ciudad);
        $this->caserio = $registro->caserio ?? '';
        $this->pais_sede_principal = $registro->pais_sede_principal ?? '';
        $this->departamento_provincia_sede_principal = $registro->departamento_provincia_sede_principal ?? '';
        $this->municipio_sede_principal = $registro->municipio_sede_principal ?? '';
        $this->aldea_ciudad_sede_principal = $registro->aldea_ciudad_sede_principal ?? '';
        $this->horas_presenciales = $registro->horas_presenciales === null ? '' : (string) $registro->horas_presenciales;
        $this->horas_teletrabajo = $registro->horas_teletrabajo === null ? '' : (string) $registro->horas_teletrabajo;

        $this->descripcion_tipo_pps = $registro->descripcion_tipo_pps ?? '';
        $this->descripcion_horas_tipo_pps_ss = $registro->descripcion_horas_tipo_pps_ss ?? '';
        $this->total_horas = (string) $registro->total_horas;
        $this->area_realizacion = $registro->area_realizacion ?? '';
        $this->resumen_responsabilidades = $registro->resumen_responsabilidades ?? '';

        $this->institucion_nombre = $registro->nombre_institucion;
        $this->institucion_nacionalidad = $registro->institucion_nacionalidad ?? '';
        $this->institucion_pais = $registro->institucion_pais ?? '';
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

        $this->archivo_carta_formalizacion_actual = $registro->archivo_carta_formalizacion;
        $this->archivo_convenio_marco_actual = $registro->archivo_convenio_marco;
        $this->carta_formalizacion_aplica = ($registro->adjunta_carta_formalizacion || filled($this->archivo_carta_formalizacion_actual)) ? 'Si' : 'No';
        $this->convenio_marco_aplica = ($registro->adjunta_convenio_marco || filled($this->archivo_convenio_marco_actual)) ? 'Si' : 'No';
    }

    protected function fillAldeaCiudad(?string $aldeaCiudad): void
    {
        if (!$aldeaCiudad) {
            return;
        }

        $this->aldea_ciudad = $aldeaCiudad;

        [$aldea, $ciudad] = array_pad(array_map('trim', explode('/', $aldeaCiudad, 2)), 2, '');

        $this->aldea = $aldea;
        $this->ciudad = $ciudad;
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
