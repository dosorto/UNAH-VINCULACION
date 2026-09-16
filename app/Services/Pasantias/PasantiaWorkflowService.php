<?php

namespace App\Services\Pasantias;

use App\Models\Estado\TipoEstado;
use App\Models\Pasantia;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use App\Mail\PasantiaFlujoNotificacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;
use RuntimeException;
use Spatie\Permission\Models\Role;

class PasantiaWorkflowService
{
    public function enviarARevision(Pasantia $registro, int $userId): Pasantia
    {
        return DB::transaction(function () use ($registro, $userId) {
            $registro = Pasantia::lockForUpdate()->findOrFail($registro->id);
            if (! $registro->perteneceAlUsuario($userId) || ! in_array($registro->estado, ['borrador', 'subsanacion'], true)) {
                throw new RuntimeException('El registro no puede enviarse a revisión en su estado actual.');
            }
            $faltantes = $this->camposFaltantesParaEnvio($registro);
            if ($faltantes) throw new RuntimeException('Complete los campos obligatorios: '.implode(', ', $faltantes));
            $flujo = $this->resolverFlujoActivoParaEnvio($registro);
            $etapas = $registro->etapasActivasDelFlujo($flujo);
            if ($etapas->isEmpty()) throw new RuntimeException('El flujo PASANTIAS_FORM_DVUS_013 no tiene etapas activas. Configure el flujo desde Configuración → Flujos.');
            $rejected = $registro->firmasDeEtapa()->where('estado_revision', 'Rechazado')->latest('revision_ciclo')->first();
            if ($rejected) {
                $base = $registro->firmasDeEtapa()->where('revision_ciclo', $rejected->revision_ciclo)->get();
                $map = $base->mapWithKeys(fn ($f) => [$f->flujo_aprobacion_etapa_id => $f->empleado_id])->all();
                $registro->crearNuevoCicloDesdeFirmaRechazada($rejected, $map);
            } else {
                $empleados = $this->resolverEmpleadosPorEtapa($etapas, $registro->destinatarios_emisor ?? []);
                $registro->flujo_aprobacion_id = $flujo->id;
                $registro->sincronizarFirmasDeEtapasDelFlujo($empleados, $flujo, 1);
            }
            $firma = $registro->firmaActualDeEtapasDelFlujo($flujo->id, $rejected ? $rejected->revision_ciclo + 1 : 1);
            if (! $firma) throw new RuntimeException('No se pudo determinar la etapa inicial.');
            $actor = User::find($userId)?->empleado;
            if (! $actor) throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            $tipo = $firma->cargo_firma()->value('tipo_estado_id') ?: TipoEstado::where('nombre', 'Enviado')->value('id');
            if (! $tipo) throw new RuntimeException('No existe estado para iniciar revisión.');
            $registro->agregarEstado($actor, $tipo, $rejected ? 'Reenvío posterior a subsanación.' : 'Registro enviado a revisión.');
            $registro->forceFill(['etapa_actual_id' => $firma->flujo_aprobacion_etapa_id, 'fecha_envio' => now(), 'enviado_por' => $userId, 'motivo_rechazo' => null, 'updated_by' => $userId])->saveQuietly();
            $this->notificar($registro, $firma, $rejected ? 'reenvio_subsanacion' : 'envio_revision');
            return $registro->fresh(['flujoAprobacion', 'etapaActual']);
        });
    }

    /**
     * Devuelve únicamente las etapas en las que el emisor debe escoger el
     * destinatario. Las demás etapas se resuelven según la configuración del
     * flujo, igual que en FORM-DVUS-014.
     */
    public function etapasQueRequierenDestinatario(Pasantia $registro): Collection
    {
        // En un reenvío después de subsanación, el envío conserva los
        // responsables históricos y no debe pedir destinatarios nuevos.
        if ($registro->firmasDeEtapa()->where('estado_revision', 'Rechazado')->exists()) {
            return collect();
        }

        $flujo = $this->resolverFlujoActivoParaEnvio($registro);
        $flujo->loadMissing('etapas.rolRevisor');

        return $registro->etapasActivasDelFlujo($flujo)
            ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (bool) $etapa->emisor_define_destinatario)
            ->map(fn (FlujoAprobacionEtapa $etapa): array => [
                'id' => (int) $etapa->id,
                'orden' => (int) $etapa->orden,
                'nombre' => $etapa->nombre,
                'rol_requerido' => $etapa->rolRevisor?->name ?: $etapa->cargoFirma?->tipoCargoFirma?->nombre,
            ])
            ->values();
    }

    public function aprobar(Pasantia $registro, int $userId): Pasantia { return $this->resolverFirma($registro, $userId, 'Aprobado'); }

    public function rechazar(Pasantia $registro, int $userId, string $comentario): Pasantia
    {
        if (trim($comentario) === '') throw new RuntimeException('El comentario de rechazo es obligatorio.');
        return DB::transaction(function () use ($registro, $userId, $comentario) {
            $registro = Pasantia::lockForUpdate()->findOrFail($registro->id);
            $firma = $this->firmaParaUsuario($registro, $userId);
            $actor = User::find($userId)?->empleado;
            if (! $actor) throw new RuntimeException('El revisor no tiene un empleado activo asociado.');
            $firma->update(['estado_revision' => 'Rechazado', 'fecha_firma' => now()]);
            $registro->anularFirmasPendientesDuplicadasDeEtapa($firma->flujo_aprobacion_etapa_id, $firma->revision_ciclo, $firma->id);
            $tipo = TipoEstado::where('nombre', 'Rechazado')->value('id');
            if (! $tipo) throw new RuntimeException('No existe estado Rechazado.');
            $registro->agregarEstado($actor, $tipo, trim($comentario));
            $registro->forceFill(['motivo_rechazo' => trim($comentario), 'revisado_por' => $userId, 'fecha_revision' => now(), 'updated_by' => $userId])->saveQuietly();
            return $registro->fresh(['flujoAprobacion', 'etapaActual']);
        });
    }

    public function iniciarSubsanacion(Pasantia $registro, int $userId): Pasantia
    {
        if ($registro->estado !== 'rechazado' || ! $registro->perteneceAlUsuario($userId)) throw new RuntimeException('Solo el creador puede subsanar un registro rechazado.');
        $actor = User::find($userId)?->empleado;
        $tipo = TipoEstado::where('nombre', 'Subsanacion')->value('id');
        if (! $actor || ! $tipo) throw new RuntimeException('No se pudo registrar el inicio de subsanación.');
        $registro->agregarEstado($actor, $tipo, 'Inicio de subsanación.');
        $registro->forceFill([
            'estado' => 'subsanacion',
            'updated_by' => $userId,
        ])->saveQuietly();
        return $registro->fresh(['flujoAprobacion', 'etapaActual']);
    }

    private function resolverFirma(Pasantia $registro, int $userId, string $estado): Pasantia
    {
        return DB::transaction(function () use ($registro, $userId, $estado) {
            $registro = Pasantia::lockForUpdate()->findOrFail($registro->id);
            if ($registro->estado !== 'en_revision') {
                throw new RuntimeException('El registro no está en revisión.');
            }
            $firma = $this->firmaParaUsuario($registro, $userId); $actor = User::find($userId)?->empleado;
            if (! $actor) throw new RuntimeException('El revisor no tiene un empleado activo asociado.');
            $firma->update(['estado_revision' => $estado, 'fecha_firma' => now()]);
            $next = $registro->siguienteFirmaDeEtapa($firma);
            if ($next) { $registro->forceFill(['etapa_actual_id' => $next->flujo_aprobacion_etapa_id])->saveQuietly(); $tipo = $next->cargo_firma()->value('tipo_estado_id'); $comentario = 'Registro avanzado a la siguiente etapa.'; }
            else {
                if (! $registro->firmasDeEtapasCompletadas((int) $firma->flujo_aprobacion_id, (int) $firma->revision_ciclo)) {
                    throw new RuntimeException('El recorrido de firmas no está completo o contiene una etapa bloqueada.');
                }
                $tipo = TipoEstado::where('nombre', 'Aprobado')->value('id'); $comentario = 'Registro aprobado en etapa final.';
            }
            if ($tipo) $registro->agregarEstado($actor, $tipo, $comentario);
            if ($next) $this->notificar($registro, $next, 'avance_etapa');
            if (! $next) $registro->forceFill(['fecha_revision' => now(), 'revisado_por' => $userId])->saveQuietly();
            return $registro->fresh(['flujoAprobacion', 'etapaActual']);
        });
    }
    private function firmaParaUsuario(Pasantia $registro, int $userId): FirmaProyecto
    {
        $firma = $registro->firmaActualDeEtapasDelFlujo((int) $registro->flujo_aprobacion_id, (int) ($registro->firmasDeEtapa()->max('revision_ciclo') ?: 1));
        if (! $firma || (int) $firma->empleado?->user_id !== $userId) throw new RuntimeException('El usuario no es responsable de la etapa actual.');
        return $firma;
    }
    public function camposFaltantesParaEnvio(Pasantia $r): array
    {
        $required = [
            'fecha_registro' => 'fecha de registro', 'facultad_centro' => 'facultad o centro',
            'escuela_departamento' => 'escuela o departamento', 'carrera' => 'carrera',
            'numero_cuenta' => 'número de cuenta', 'nombre_estudiante' => 'estudiante',
            'celular_estudiante' => 'celular del estudiante', 'correo_institucional' => 'correo institucional',
            'tipo_pasantia' => 'tipo de pasantía', 'fecha_inicio' => 'fecha de inicio',
            'fecha_finalizacion' => 'fecha de finalización', 'duracion_semanas' => 'duración en semanas',
            'total_horas' => 'total de horas', 'horas_semanales' => 'promedio de horas semanales',
            'pasantia_obligatoria' => 'reconocimiento de la pasantía', 'otorga_creditos' => 'otorgamiento de créditos',
            'modalidad_ejecucion' => 'modalidad de ejecución', 'descripcion_experiencia' => 'experiencia y resultados',
            'descripcion_cargo' => 'descripción del cargo', 'resumen_responsabilidades' => 'responsabilidades y tareas',
            'area_departamento' => 'área o departamento', 'area_conocimiento' => 'área de conocimiento',
            'descripcion_conocimientos_teoricos' => 'conocimientos teóricos', 'habilidades_desarrollar' => 'habilidades',
            'pasantia_remunerada' => 'compensación', 'nombre_institucion' => 'institución',
            'direccion_institucion' => 'dirección de la institución', 'ciudad_institucion' => 'ciudad',
            'pais_institucion' => 'país', 'representante_legal' => 'representante legal',
            'telefono_representante' => 'teléfono de la institución', 'correo_rrhh' => 'correo de recursos humanos',
            'tipo_institucion' => 'tipo de institución', 'sector_institucion' => 'sector institucional',
            'compromisos_institucion' => 'compromisos institucionales', 'nombre_contacto_directo' => 'contacto directo',
            'celular_contacto_directo' => 'celular del contacto directo', 'correo_contacto_directo' => 'correo del contacto directo',
            'cargo_contacto_directo' => 'cargo del contacto directo', 'grado_academico_contacto_directo' => 'grado académico del contacto directo',
            'tipo_instrumento' => 'instrumento de formalización', 'nombre_docente_supervisor' => 'docente supervisor',
            'numero_empleado_docente' => 'número de empleado del supervisor', 'celular_docente' => 'celular del supervisor',
            'correo_docente' => 'correo del supervisor', 'categoria_docente' => 'categoría del supervisor',
            'departamento_docente' => 'departamento del supervisor', 'jornada_laboral_docente' => 'jornada laboral del supervisor',
            'ubicacion_cubiculo_docente' => 'ubicación del cubículo del supervisor',
        ];

        $missing = collect($required)
            ->filter(fn ($label, $field) => $this->campoSinValor($r->{$field}))
            ->values()->all();

        $formatos = [
            'numero_cuenta' => ['string', 'max:30', 'regex:/^[0-9]+$/'],
            'nombre_estudiante' => ['string', 'max:255', 'regex:/^[\pL\s.\'-]+$/u'],
            'celular_estudiante' => ['string', 'min:8', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
            'telefono_representante' => ['string', 'min:8', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
            'celular_contacto_directo' => ['string', 'min:8', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
            'celular_docente' => ['string', 'min:8', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
            'correo_institucional' => ['email', 'max:255'],
            'correo_personal' => ['email', 'max:255'],
            'total_horas' => ['integer', 'min:0', 'max:10000'],
            'horas_semanales' => ['integer', 'min:0', 'max:168'],
        ];
        $etiquetas = collect($required);

        foreach ($formatos as $campo => $reglas) {
            if ($this->campoSinValor($r->{$campo})) {
                continue;
            }

            if (validator([$campo => $r->{$campo}], [$campo => $reglas])->fails()) {
                $missing[] = ($etiquetas[$campo] ?? $campo).' (formato inválido)';
            }
        }

        $nombreEstudiante = mb_strtolower(trim((string) $r->nombre_estudiante));
        if (in_array($nombreEstudiante, ['codigo de asignatura', 'código de asignatura', 'nombre de asignatura'], true)) {
            $missing[] = 'estudiante (valor de otro campo)';
        }

        if (is_string($r->escuela_departamento)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($r->escuela_departamento)) === 1) {
            $missing[] = 'escuela o departamento (valor de fecha)';
        }

        if ($r->otorga_creditos && $this->campoSinValor($r->cantidad_creditos)) {
            $missing[] = 'cantidad de créditos académicos';
        }
        if ($r->pasantia_remunerada && $this->campoSinValor($r->monto_remuneracion)) {
            $missing[] = 'monto de la remuneración';
        }
        if ($r->fecha_inicio && $r->fecha_finalizacion && $r->fecha_finalizacion->lt($r->fecha_inicio)) {
            $missing[] = 'fecha de finalización posterior o igual a la fecha de inicio';
        }

        return $missing;
    }

    /**
     * En un formulario, false y 0 son respuestas válidas. `blank()` los
     * considera vacíos y hacía imposible enviar una pasantía que tuviera
     * cualquiera de las respuestas "No".
     */
    private function campoSinValor(mixed $valor): bool
    {
        return $valor === null || (is_string($valor) && trim($valor) === '');
    }

    private function resolverFlujoActivoParaEnvio(Pasantia $registro): FlujoAprobacion
    {
        $base = FlujoAprobacion::query()
            ->where('proceso', Pasantia::PROCESO_FLUJO)
            ->where('codigo_formulario', Pasantia::FORMULARIO)
            ->where('activo', true);

        if ($registro->flujo_aprobacion_id) {
            $flujoAsignado = (clone $base)->whereKey($registro->flujo_aprobacion_id)->first();

            if (! $flujoAsignado) {
                throw new RuntimeException('El flujo asignado al registro no está activo o no corresponde al FORM-DVUS-013.');
            }

            return $flujoAsignado->load('etapas.cargoFirma.tipoCargoFirma');
        }

        $flujo = $base
            ->where('codigo', 'PASANTIAS_FORM_DVUS_013')
            ->orderBy('id')
            ->first();

        if (! $flujo) {
            throw new RuntimeException('El flujo PASANTIAS_FORM_DVUS_013 está incompleto o inactivo. Configure el flujo desde Configuración → Flujos.');
        }

        return $flujo->load('etapas.cargoFirma.tipoCargoFirma');
    }

    private function resolverEmpleadosPorEtapa($etapas, array $destinatarios): array
    {
        $empleados = [];

        foreach ($etapas as $etapa) {
            $this->repararCargoInicialSiEsNecesario($etapa);

            if (! $etapa->cargo_firma_id) {
                throw new RuntimeException(sprintf('La etapa "%s" no tiene cargo de firma configurado. Configure el flujo desde Configuración → Flujos.', $etapa->nombre));
            }

            $usuario = $this->resolverUsuarioEtapa($etapa, $destinatarios);
            if (! $usuario || ! $usuario->empleado || $usuario->empleado->trashed()) {
                throw new RuntimeException(sprintf('La etapa "%s" no tiene un responsable con empleado activo.', $etapa->nombre));
            }

            $empleados[$etapa->id] = $usuario->empleado->id;
        }

        return $empleados;
    }

    /**
     * La migración del flujo puede ejecutarse antes de los seeders que crean
     * cargo_firma y roles. Solo se repara la etapa predeterminada de
     * FORM-DVUS-013 y únicamente para completar esa configuración base.
     */
    private function repararCargoInicialSiEsNecesario(FlujoAprobacionEtapa $etapa): void
    {
        if ($etapa->codigo !== 'PASANTIAS_ETAPA_01') {
            return;
        }

        $etapa->loadMissing(['cargoFirma.tipoCargoFirma', 'rolRevisor']);
        $cargo = $etapa->cargoFirma;

        if (! $cargo) {
            $cargo = CargoFirma::query()
                ->where('descripcion', 'Proyecto')
                ->whereHas('tipoCargoFirma', fn ($query) => $query->where('nombre', 'Coordinador Proyecto'))
                ->orderBy('id')
                ->first();
        }

        $rol = $etapa->rolRevisor;
        if (! $rol && ! $etapa->usuario_responsable_id && $cargo?->tipoCargoFirma?->nombre) {
            $rol = Role::query()
                ->where('name', $cargo->tipoCargoFirma->nombre)
                ->where('guard_name', 'web')
                ->first();
        }

        $actualizaciones = [];
        if (! $etapa->cargo_firma_id && $cargo) {
            $actualizaciones['cargo_firma_id'] = $cargo->id;
        }
        if (! $etapa->rol_revisor_id && $rol) {
            $actualizaciones['rol_revisor_id'] = $rol->id;
        }

        if ($actualizaciones === []) {
            return;
        }

        $etapa->forceFill($actualizaciones)->saveQuietly();
        if ($cargo) {
            $etapa->setRelation('cargoFirma', $cargo);
        }
        if ($rol) {
            $etapa->setRelation('rolRevisor', $rol);
        }
    }

    private function resolverUsuarioEtapa(FlujoAprobacionEtapa $etapa, array $destinatarios): ?User
    {
        $etapa->loadMissing(['usuarioResponsable.empleado', 'rolRevisor', 'cargoFirma.tipoCargoFirma']);

        if ($etapa->emisor_define_destinatario) {
            $usuarioId = $destinatarios[$etapa->id] ?? null;
            $usuario = $usuarioId ? User::with('empleado')->find((int) $usuarioId) : null;
            $rol = $this->rolRevisorDeEtapa($etapa);

            if (! $usuario || ! $rol || ! $usuario->hasRole($rol)) {
                throw new RuntimeException(sprintf('Debe seleccionar un destinatario válido para la etapa "%s".', $etapa->nombre));
            }

            return $usuario;
        }

        if ($etapa->usuarioResponsable) {
            return $etapa->usuarioResponsable;
        }

        if ($etapa->requiere_asignacion) {
            throw new RuntimeException(sprintf('La etapa "%s" requiere un responsable fijo válido antes de enviar.', $etapa->nombre));
        }

        $rol = $this->rolRevisorDeEtapa($etapa);

        return $rol
            ? User::role($rol)->whereHas('empleado')->with('empleado')->orderBy('name')->first()
            : null;
    }

    /**
     * Las etapas nuevas de FORM-DVUS-013 nacen con cargo de firma, mientras
     * que el rol puede no haber sido copiado todavía a rol_revisor_id. El tipo
     * del cargo es el respaldo explícito del rol para este formulario.
     */
    private function rolRevisorDeEtapa(FlujoAprobacionEtapa $etapa): ?string
    {
        return $etapa->rolRevisor?->name
            ?: $etapa->cargoFirma?->tipoCargoFirma?->nombre;
    }

    private function notificar(Pasantia $registro, $firma, string $evento): void
    {
        $firma->loadMissing('empleado.user', 'flujoEtapa');
        $user = $firma->empleado?->user;
        if (! $user || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('La etapa de Pasantías no tiene correo válido; el flujo continuará sin notificación.', [
                'registro_id' => $registro->id,
                'etapa_id' => $firma->flujo_aprobacion_etapa_id,
                'responsable_id' => $firma->empleado_id,
                'evento' => $evento,
            ]);

            return;
        }

        try {
            Mail::to($user->email)->queue(
                (new PasantiaFlujoNotificacion($registro->fresh(['flujoAprobacion', 'etapaActual']), $evento))->afterCommit()
            );
        } catch (\Throwable $e) {
            Log::error('No se pudo encolar notificación de Pasantías', ['registro_id' => $registro->id, 'etapa_id' => $firma->flujo_aprobacion_etapa_id, 'evento' => $evento, 'error' => $e->getMessage()]);
        }
    }
}
