<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Pasantia;
use App\Services\Pasantias\PasantiaWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasantiaFlujoNotificacion;
use App\Services\Pasantias\PasantiaPdfGenerator;
use Livewire\Component;

class ShowPasantia extends Component
{
    public Pasantia $registro;
    public bool $detalleCompleto = true;
    public string $comentarioRechazo = '';

    public function mount(int $id): void
    {
        $this->registro = Pasantia::with(['flujoAprobacion', 'etapaActual', 'historialEstados.tipoestado'])->findOrFail($id);
        abort_unless($this->registro->created_by === Auth::id() || Auth::user()?->can('proyectos.historial') || Auth::user()?->can('docente.proyectos'), 403);
    }

    public function iniciarSubsanacion(): void
    {
        $this->registro->refresh();
        abort_unless($this->registro->created_by === Auth::id(), 403);
        try { $this->registro = app(PasantiaWorkflowService::class)->iniciarSubsanacion($this->registro, (int) Auth::id()); $this->notificar('enviado a subsanación'); } catch (\Throwable $e) { $this->addError('flujo', $e->getMessage()); }
    }

    public function volverABorrador(): void
    {
        $this->registro->refresh();
        abort_unless($this->registro->created_by === Auth::id(), 403);
        if ($this->registro->estado !== 'subsanacion') {
            return;
        }

        $this->registrarEstado('Borrador', 'Subsanación guardada; registro devuelto a borrador.');
        $this->registro->refresh();
    }

    public function enviarRevision(): void
    {
        try { $this->registro = app(PasantiaWorkflowService::class)->enviarARevision($this->registro, (int) Auth::id()); $this->notificar('enviado a revisión'); } catch (\Throwable $e) { $this->addError('flujo', $e->getMessage()); }
    }

    public function aprobar(): void
    {
        try { $this->registro = app(PasantiaWorkflowService::class)->aprobar($this->registro, (int) Auth::id()); $this->notificar('aprobado'); } catch (\Throwable $e) { $this->addError('flujo', $e->getMessage()); }
    }

    public function rechazar(): void
    {
        $this->validate(['comentarioRechazo' => 'required|string|min:5|max:5000'], [], ['comentarioRechazo' => 'comentario de rechazo']);
        try { $this->registro = app(PasantiaWorkflowService::class)->rechazar($this->registro, (int) Auth::id(), $this->comentarioRechazo); $this->notificar('rechazado'); $this->comentarioRechazo = ''; } catch (\Throwable $e) { $this->addError('flujo', $e->getMessage()); }
    }

    public function generarDocumento(string $tipo): void { try { app(PasantiaPdfGenerator::class)->generar($this->registro, (int) Auth::id(), $tipo); } catch (\Throwable $e) { $this->addError('pdf', $e->getMessage()); } }
    private function notificar(string $evento): void { try { $email=$this->registro->etapaActual?->usuarioResponsable?->email; if($email) Mail::to($email)->queue(new PasantiaFlujoNotificacion($this->registro,$evento)); } catch (\Throwable $e) { Log::warning('Notificación de Pasantía fallida',['registro_id'=>$this->registro->id,'evento'=>$evento,'error'=>$e->getMessage()]); } }

    protected function registrarEstado(string $nombre, string $comentario): void
    {
        $tipoEstadoId = DB::table('tipo_estado')->where('nombre', $nombre)->value('id');
        $empleadoId = Auth::user()?->empleado?->id ?? DB::table('empleado')->value('id');
        if (! $tipoEstadoId || ! $empleadoId) {
            return;
        }

        DB::transaction(function () use ($tipoEstadoId, $comentario, $empleadoId): void {
            DB::table('estado_proyecto')
                ->where('estadoable_type', Pasantia::class)
                ->where('estadoable_id', $this->registro->id)
                ->update(['es_actual' => false]);
            DB::table('estado_proyecto')->insert([
                'empleado_id' => $empleadoId,
                'tipo_estado_id' => $tipoEstadoId,
                'fecha' => now(),
                'comentario' => $comentario,
                'es_actual' => true,
                'estadoable_id' => $this->registro->id,
                'estadoable_type' => Pasantia::class,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.show-pasantia', ['secciones' => $this->secciones()]);
    }

    private function secciones(): array
    {
        return [
            'Estudiante' => ['facultad_centro','escuela_departamento','carrera','numero_cuenta','nombre_estudiante','celular_estudiante','correo_institucional','correo_personal'],
            'Información de la pasantía' => ['tipo_pasantia','fecha_inicio','fecha_finalizacion','duracion_semanas','total_horas','horas_semanales','pasantia_obligatoria','otorga_creditos','cantidad_creditos','modalidad_ejecucion'],
            'Experiencia' => ['descripcion_experiencia','descripcion_cargo','resumen_responsabilidades','area_departamento','area_conocimiento','asignaturas','codigo_asignatura','nombre_asignatura','descripcion_conocimientos_teoricos','habilidades_desarrollar','pasantia_remunerada','monto_remuneracion'],
            'Institución' => ['nombre_institucion','direccion_institucion','ciudad_institucion','pais_institucion','representante_legal','telefono_representante','correo_rrhh','tipo_institucion','sector_institucion','compromisos_institucion'],
            'Contacto directo' => ['nombre_contacto_directo','celular_contacto_directo','correo_contacto_directo','cargo_contacto_directo','grado_academico_contacto_directo','tipo_instrumento'],
            'Supervisor' => ['nombre_docente_supervisor','numero_empleado_docente','celular_docente','correo_docente','categoria_docente','departamento_docente','jornada_laboral_docente','ubicacion_cubiculo_docente'],
            'Firmas' => ['nombre_firma_coordinador','firma_coordinador','nombre_firma_supervisor','firma_supervisor','nombre_firma_estudiante','firma_estudiante'],
            'Adjuntos' => ['adjunta_carta_formalizacion','archivo_carta_formalizacion','adjunta_convenio_marco','archivo_convenio_marco'],
        ];
    }
}
