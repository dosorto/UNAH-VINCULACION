<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Pasantia;
use App\Services\Pasantias\PasantiaPdfGenerator;
use App\Services\Pasantias\PasantiaWorkflowService;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ShowPasantia extends Component
{
    public Pasantia $registro;
    public array $camposFaltantesEnvio = [];
    public bool $subsanarModal = false;
    public string $subsanarComentario = '';

    public function mount(int $id): void
    {
        $registro = Pasantia::with(['flujoAprobacion', 'etapaActual'])->findOrFail($id);
        abort_unless($this->canViewRecord($registro), 403);
        $this->registro = $registro;
    }

    public function enviarRevision(): void
    {
        $this->registro->refresh();
        $this->resetErrorBag(['flujo', 'pdf']);
        $this->camposFaltantesEnvio = [];

        if (!in_array($this->registro->estado, ['borrador', 'subsanacion'], true) || !$this->registro->perteneceAlUsuario(auth()->id())) {
            $this->addError('flujo', 'Solo el creador puede enviar un borrador o un registro en subsanación a revisión.'); return;
        }
        $faltantes = $this->registro->camposFaltantesParaEnvio();
        if ($faltantes !== []) {
            $this->camposFaltantesEnvio = $faltantes;
            Notification::make()->title('Formulario incompleto')->body('Complete los campos obligatorios antes de enviar a revisión.')->warning()->send(); return;
        }
        try {
            $this->registro = app(PasantiaWorkflowService::class)->enviarARevision($this->registro, (int) auth()->id());
            $this->camposFaltantesEnvio = [];
            Notification::make()->title('Registro enviado')->body('El FORM-DVUS-013 fue enviado a revisión correctamente.')->success()->send();
        } catch (\RuntimeException $e) {
            $this->addError('flujo', $e->getMessage());
            Notification::make()
                ->title('Flujo de revisión no disponible')
                ->body($e->getMessage())
                ->warning()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Error enviando Pasantía a revisión', [
                'registro_id' => $this->registro->id,
                'estado' => $this->registro->estado,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
            $this->addError('flujo', 'No se pudo iniciar el flujo de revisión. Intente nuevamente o contacte a administración.');
            Notification::make()
                ->title('No se pudo enviar a revisión')
                ->body('No se pudo iniciar el flujo de revisión. Revise el mensaje mostrado en el formulario.')
                ->warning()
                ->send();
        }
    }

    public function aprobar(): void
    {
        $this->registro->refresh(); abort_unless($this->registro->puedeAprobarse(auth()->id(), auth()->user()), 403);
        try { $this->registro = app(PasantiaWorkflowService::class)->aprobar($this->registro, (int) auth()->id()); Notification::make()->title('Registro aprobado')->body('La etapa fue aprobada correctamente.')->success()->send(); }
        catch (\Throwable $e) { $this->addError('flujo', $e->getMessage()); }
    }

    public function abrirModalSubsanacion(): void
    {
        $this->registro->refresh(); abort_unless($this->registro->puedeRechazarse(auth()->id(), auth()->user()), 403);
        $this->resetErrorBag('subsanarComentario'); $this->subsanarComentario = ''; $this->subsanarModal = true;
    }

    public function cerrarModalSubsanacion(): void
    {
        $this->subsanarModal = false; $this->subsanarComentario = ''; $this->resetErrorBag('subsanarComentario');
    }

    public function enviarASubsanar(): void
    {
        $this->registro->refresh(); abort_unless($this->registro->puedeRechazarse(auth()->id(), auth()->user()), 403);
        $this->validate(['subsanarComentario' => 'required|string|min:5|max:5000'], [], ['subsanarComentario' => 'observaciones']);
        try {
            $this->registro = app(PasantiaWorkflowService::class)->rechazar($this->registro, (int) auth()->id(), $this->subsanarComentario);
            $this->cerrarModalSubsanacion(); Notification::make()->title('Registro enviado a subsanación')->body('El FORM-DVUS-013 fue devuelto para correcciones.')->warning()->send();
        } catch (\Throwable $e) { $this->addError('flujo', $e->getMessage()); }
    }

    public function iniciarSubsanacion(): void
    {
        $this->registro->refresh(); abort_unless($this->registro->puedeSubsanarse(auth()->id()), 403);
        try {
            $this->registro = app(PasantiaWorkflowService::class)->iniciarSubsanacion($this->registro, (int) auth()->id());
            Notification::make()->title('Subsanación iniciada')->body('El registro volvió a borrador para corregirlo.')->success()->send();
            $this->redirectRoute('pasantias.edit', ['id' => $this->registro->id]);
        } catch (\Throwable $e) { $this->addError('flujo', $e->getMessage()); }
    }

    public function eliminarBorrador(): void
    {
        $this->registro->refresh(); abort_unless($this->registro->puedeEliminarBorrador(Auth::id()), 403);
        DB::transaction(function (): void { activity('Pasantías')->performedOn($this->registro)->causedBy(Auth::user())->withProperties(['accion' => 'eliminacion_logica', 'estado' => 'borrador'])->log('Borrador eliminado lógicamente'); $this->registro->delete(); });
        Notification::make()->title('Borrador eliminado')->body('El borrador de Pasantías fue eliminado.')->success()->send(); $this->redirectRoute($this->historialRouteName());
    }

    public function generarDocumento(string $tipo): void
    {
        try { app(PasantiaPdfGenerator::class)->generar($this->registro, (int) auth()->id(), $tipo); Notification::make()->title('Documento generado')->body('El documento quedó disponible para descarga.')->success()->send(); }
        catch (\Throwable $e) { $this->addError('pdf', $e->getMessage()); }
    }

    public function render(): View
    {
        $this->registro->loadMissing(['flujoAprobacion', 'etapaActual.usuarioResponsable', 'documentosGenerados', 'historialEstados' => fn ($q) => $q->with(['empleado', 'tipoestado'])->orderByDesc('created_at')]);
        return view('livewire.proyectos.vinculacion.show-pasantia', [
            'historialRouteName' => $this->historialRouteName(),
            'movimientos' => $this->registro->historialEstados,
            'anexos' => $this->anexosRegistrados(),
            'secciones' => $this->datosDetalle(),
        ]);
    }

    private function canViewRecord(Pasantia $registro): bool
    {
        $user = auth()->user(); return $registro->perteneceAlUsuario(auth()->id()) || $user?->can('proyectos.historial') || $user?->can('docente.proyectos') || $registro->usuarioPuedeRevisar($user);
    }

    private function historialRouteName(): string
    {
        $role = auth()->user()?->activeRole;
        if ($role?->hasPermissionTo('docente.proyectos')) return 'proyectosDocente';
        if ($role?->hasPermissionTo('director.proyectos')) return 'proyectosCentroFacultad';
        if ($role?->hasPermissionTo('proyectos.historial')) return 'listarProyectosVinculacion';
        return 'inicio';
    }

    private function anexosRegistrados(): array
    {
        return collect([
            ['titulo' => 'Carta de formalización', 'path' => $this->registro->archivo_carta_formalizacion, 'marcado' => (bool) $this->registro->adjunta_carta_formalizacion],
            ['titulo' => 'Convenio marco', 'path' => $this->registro->archivo_convenio_marco, 'marcado' => (bool) $this->registro->adjunta_convenio_marco],
        ])->filter(fn (array $a) => filled($a['path']) || $a['marcado'])->map(function (array $a): array { $path = filled($a['path']) ? ltrim((string) $a['path'], '/') : null; $exists = $path ? Storage::disk('public')->exists($path) : false; return $a + ['archivo' => $path ? basename($path) : null, 'exists' => $exists, 'url' => $exists ? Storage::disk('public')->url($path) : null]; })->values()->all();
    }

    /**
     * Datos de la ficha de detalle. Cada valor se toma directamente de la
     * propiedad nombrada del modelo; no depende de índices ni de toArray().
     */
    private function datosDetalle(): array
    {
        return [
            'I. Información general del estudiante' => [
                ['etiqueta' => 'Fecha de registro', 'campo' => 'fecha_registro', 'valor' => $this->registro->fecha_registro],
                ['etiqueta' => 'Facultad / Centro Universitario Regional / Instituto Tecnológico', 'campo' => 'facultad_centro', 'valor' => $this->registro->facultad_centro],
                ['etiqueta' => 'Escuela / Departamento Académico', 'campo' => 'escuela_departamento', 'valor' => $this->registro->escuela_departamento],
                ['etiqueta' => 'Carrera', 'campo' => 'carrera', 'valor' => $this->registro->carrera],
                ['etiqueta' => 'Número de cuenta', 'campo' => 'numero_cuenta', 'valor' => $this->registro->numero_cuenta],
                ['etiqueta' => 'Nombre completo del estudiante', 'campo' => 'nombre_estudiante', 'valor' => $this->registro->nombre_estudiante],
                ['etiqueta' => 'Número de celular', 'campo' => 'celular_estudiante', 'valor' => $this->registro->celular_estudiante],
                ['etiqueta' => 'Correo electrónico institucional', 'campo' => 'correo_institucional', 'valor' => $this->registro->correo_institucional],
                ['etiqueta' => 'Correo electrónico personal', 'campo' => 'correo_personal', 'valor' => $this->registro->correo_personal],
            ],
            'II. Información de la pasantía' => [
                ['etiqueta' => 'Tipo de pasantía', 'campo' => 'tipo_pasantia', 'valor' => $this->registro->tipo_pasantia],
                ['etiqueta' => 'Fecha de inicio', 'campo' => 'fecha_inicio', 'valor' => $this->registro->fecha_inicio],
                ['etiqueta' => 'Fecha de finalización', 'campo' => 'fecha_finalizacion', 'valor' => $this->registro->fecha_finalizacion],
                ['etiqueta' => 'Duración de la pasantía en semanas', 'campo' => 'duracion_semanas', 'valor' => $this->registro->duracion_semanas],
                ['etiqueta' => 'Número total de horas programadas', 'campo' => 'total_horas', 'valor' => $this->registro->total_horas],
                ['etiqueta' => 'Promedio de horas semanales programadas', 'campo' => 'horas_semanales', 'valor' => $this->registro->horas_semanales],
                ['etiqueta' => 'Pasantía obligatoria', 'campo' => 'pasantia_obligatoria', 'valor' => $this->registro->pasantia_obligatoria],
                ['etiqueta' => 'Otorgamiento de créditos académicos', 'campo' => 'otorga_creditos', 'valor' => $this->registro->otorga_creditos],
                ['etiqueta' => 'Cantidad de créditos académicos', 'campo' => 'cantidad_creditos', 'valor' => $this->registro->cantidad_creditos],
                ['etiqueta' => 'Modalidad de ejecución', 'campo' => 'modalidad_ejecucion', 'valor' => $this->registro->modalidad_ejecucion],
            ],
            'III. Descripción de la experiencia y resultados' => [
                ['etiqueta' => 'Descripción de la experiencia y resultados', 'campo' => 'descripcion_experiencia', 'valor' => $this->registro->descripcion_experiencia],
                ['etiqueta' => 'Descripción del cargo', 'campo' => 'descripcion_cargo', 'valor' => $this->registro->descripcion_cargo],
                ['etiqueta' => 'Resumen de responsabilidades y tareas', 'campo' => 'resumen_responsabilidades', 'valor' => $this->registro->resumen_responsabilidades],
                ['etiqueta' => 'Nombre del departamento o área', 'campo' => 'area_departamento', 'valor' => $this->registro->area_departamento],
                ['etiqueta' => 'Área de conocimiento que se aplicará', 'campo' => 'area_conocimiento', 'valor' => $this->registro->area_conocimiento],
                ['etiqueta' => 'Código de asignatura', 'campo' => 'codigo_asignatura', 'valor' => $this->registro->codigo_asignatura],
                ['etiqueta' => 'Nombre de asignatura', 'campo' => 'nombre_asignatura', 'valor' => $this->registro->nombre_asignatura],
                ['etiqueta' => 'Conocimientos teóricos', 'campo' => 'descripcion_conocimientos_teoricos', 'valor' => $this->registro->descripcion_conocimientos_teoricos],
                ['etiqueta' => 'Habilidades por desarrollar', 'campo' => 'habilidades_desarrollar', 'valor' => $this->registro->habilidades_desarrollar],
                ['etiqueta' => 'Pasantía remunerada', 'campo' => 'pasantia_remunerada', 'valor' => $this->registro->pasantia_remunerada],
                ['etiqueta' => 'Monto de la remuneración', 'campo' => 'monto_remuneracion', 'valor' => $this->registro->monto_remuneracion],
            ],
            'IV. Información de la institución / organización' => [
                ['etiqueta' => 'Nombre completo', 'campo' => 'nombre_institucion', 'valor' => $this->registro->nombre_institucion],
                ['etiqueta' => 'Dirección de la sede principal', 'campo' => 'direccion_institucion', 'valor' => $this->registro->direccion_institucion],
                ['etiqueta' => 'Ciudad', 'campo' => 'ciudad_institucion', 'valor' => $this->registro->ciudad_institucion],
                ['etiqueta' => 'País', 'campo' => 'pais_institucion', 'valor' => $this->registro->pais_institucion],
                ['etiqueta' => 'Representante legal', 'campo' => 'representante_legal', 'valor' => $this->registro->representante_legal],
                ['etiqueta' => 'Número de teléfono', 'campo' => 'telefono_representante', 'valor' => $this->registro->telefono_representante],
                ['etiqueta' => 'Correo de recursos humanos', 'campo' => 'correo_rrhh', 'valor' => $this->registro->correo_rrhh],
                ['etiqueta' => 'Tipo de institución / organización', 'campo' => 'tipo_institucion', 'valor' => $this->registro->tipo_institucion],
                ['etiqueta' => 'Sector institucional', 'campo' => 'sector_institucion', 'valor' => $this->registro->sector_institucion],
                ['etiqueta' => 'Compromisos asumidos por la institución', 'campo' => 'compromisos_institucion', 'valor' => $this->registro->compromisos_institucion],
                ['etiqueta' => 'Contacto directo', 'campo' => 'nombre_contacto_directo', 'valor' => $this->registro->nombre_contacto_directo],
                ['etiqueta' => 'Celular del contacto directo', 'campo' => 'celular_contacto_directo', 'valor' => $this->registro->celular_contacto_directo],
                ['etiqueta' => 'Correo del contacto directo', 'campo' => 'correo_contacto_directo', 'valor' => $this->registro->correo_contacto_directo],
                ['etiqueta' => 'Cargo del contacto directo', 'campo' => 'cargo_contacto_directo', 'valor' => $this->registro->cargo_contacto_directo],
                ['etiqueta' => 'Grado académico del contacto directo', 'campo' => 'grado_academico_contacto_directo', 'valor' => $this->registro->grado_academico_contacto_directo],
                ['etiqueta' => 'Tipo de instrumento', 'campo' => 'tipo_instrumento', 'valor' => $this->registro->tipo_instrumento],
            ],
            'V. Docente supervisor' => [
                ['etiqueta' => 'Nombre completo', 'campo' => 'nombre_docente_supervisor', 'valor' => $this->registro->nombre_docente_supervisor],
                ['etiqueta' => 'Número de empleado', 'campo' => 'numero_empleado_docente', 'valor' => $this->registro->numero_empleado_docente],
                ['etiqueta' => 'Número de celular', 'campo' => 'celular_docente', 'valor' => $this->registro->celular_docente],
                ['etiqueta' => 'Correo electrónico', 'campo' => 'correo_docente', 'valor' => $this->registro->correo_docente],
                ['etiqueta' => 'Categoría', 'campo' => 'categoria_docente', 'valor' => $this->registro->categoria_docente],
                ['etiqueta' => 'Departamento', 'campo' => 'departamento_docente', 'valor' => $this->registro->departamento_docente],
                ['etiqueta' => 'Jornada laboral', 'campo' => 'jornada_laboral_docente', 'valor' => $this->registro->jornada_laboral_docente],
                ['etiqueta' => 'Ubicación del cubículo en la UNAH', 'campo' => 'ubicacion_cubiculo_docente', 'valor' => $this->registro->ubicacion_cubiculo_docente],
            ],
            'VI. Firmas' => [
                ['etiqueta' => 'Nombre del coordinador', 'campo' => 'nombre_firma_coordinador', 'valor' => $this->registro->nombre_firma_coordinador],
                ['etiqueta' => 'Firma del coordinador', 'campo' => 'firma_coordinador', 'valor' => $this->registro->firma_coordinador],
                ['etiqueta' => 'Nombre del supervisor', 'campo' => 'nombre_firma_supervisor', 'valor' => $this->registro->nombre_firma_supervisor],
                ['etiqueta' => 'Firma del supervisor', 'campo' => 'firma_supervisor', 'valor' => $this->registro->firma_supervisor],
                ['etiqueta' => 'Nombre del estudiante', 'campo' => 'nombre_firma_estudiante', 'valor' => $this->registro->nombre_firma_estudiante],
                ['etiqueta' => 'Firma del estudiante', 'campo' => 'firma_estudiante', 'valor' => $this->registro->firma_estudiante],
            ],
            'VII. Documentos adjuntos' => [
                ['etiqueta' => 'Carta formal de solicitud a la unidad académica', 'campo' => 'archivo_carta_formalizacion', 'valor' => $this->registro->archivo_carta_formalizacion],
                ['etiqueta' => 'Convenio marco entre la UNAH y la entidad', 'campo' => 'archivo_convenio_marco', 'valor' => $this->registro->archivo_convenio_marco],
            ],
        ];
    }

    /**
     * Compatibilidad con consumidores internos existentes. La vista de
     * detalle no utiliza este mapa; renderiza exclusivamente datosDetalle().
     */
    private function secciones(): array
    {
        return app(PasantiaPdfGenerator::class)->seccionesFormulario();
    }
}
