<?php

namespace App\Services\Pasantias;

use App\Models\Estado\TipoEstado;
use App\Models\Pasantia;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PasantiaWorkflowService
{
    public function enviarARevision(Pasantia $registro, int $userId): Pasantia
    {
        return DB::transaction(function () use ($registro, $userId) {
            $registro = Pasantia::lockForUpdate()->findOrFail($registro->id);
            if (! $registro->perteneceAlUsuario($userId) || ! in_array($registro->estado, ['borrador', 'subsanacion'], true)) {
                throw new RuntimeException('El registro no puede enviarse a revisión en su estado actual.');
            }
            $faltantes = $this->camposFaltantes($registro);
            if ($faltantes) throw new RuntimeException('Complete los campos obligatorios: '.implode(', ', $faltantes));
            $flujo = $registro->resolveFlujoAprobacion();
            if (! $flujo || $flujo->codigo !== 'PASANTIAS_FORM_DVUS_013') throw new RuntimeException('El flujo de Pasantías no está activo.');
            $etapas = $registro->etapasActivasDelFlujo($flujo);
            if ($etapas->isEmpty()) throw new RuntimeException('El flujo no tiene etapas activas.');
            $rejected = $registro->firmasDeEtapa()->where('estado_revision', 'Rechazado')->latest('revision_ciclo')->first();
            if ($rejected) {
                $base = $registro->firmasDeEtapa()->where('revision_ciclo', $rejected->revision_ciclo)->get();
                $map = $base->mapWithKeys(fn ($f) => [$f->flujo_aprobacion_etapa_id => $f->empleado_id])->all();
                $registro->crearNuevoCicloDesdeFirmaRechazada($rejected, $map);
            } else {
                $empleados = $etapas->mapWithKeys(fn ($e) => [$e->id => $e->usuario_responsable?->empleado?->id])->all();
                foreach ($empleados as $id => $empleado) if (! $empleado) throw new RuntimeException('Cada etapa debe tener un responsable configurado.');
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
            return $registro->fresh(['flujoAprobacion', 'etapaActual']);
        });
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
        $registro->forceFill(['updated_by' => $userId])->saveQuietly();
        return $registro->fresh(['flujoAprobacion', 'etapaActual']);
    }

    private function resolverFirma(Pasantia $registro, int $userId, string $estado): Pasantia
    {
        return DB::transaction(function () use ($registro, $userId, $estado) {
            $registro = Pasantia::lockForUpdate()->findOrFail($registro->id);
            $firma = $this->firmaParaUsuario($registro, $userId); $actor = User::find($userId)?->empleado;
            if (! $actor) throw new RuntimeException('El revisor no tiene un empleado activo asociado.');
            $firma->update(['estado_revision' => $estado, 'fecha_firma' => now()]);
            $next = $registro->siguienteFirmaDeEtapa($firma);
            if ($next) { $registro->forceFill(['etapa_actual_id' => $next->flujo_aprobacion_etapa_id])->saveQuietly(); $tipo = $next->cargo_firma()->value('tipo_estado_id'); $comentario = 'Registro avanzado a la siguiente etapa.'; }
            else { $tipo = TipoEstado::where('nombre', 'Aprobado')->value('id'); $comentario = 'Registro aprobado en etapa final.'; }
            if ($tipo) $registro->agregarEstado($actor, $tipo, $comentario);
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
    private function camposFaltantes(Pasantia $r): array
    {
        return collect(['nombre_estudiante' => 'estudiante', 'numero_cuenta' => 'número de cuenta', 'carrera' => 'carrera', 'nombre_institucion' => 'institución', 'total_horas' => 'total de horas', 'modalidad_ejecucion' => 'modalidad'])->filter(fn ($label, $field) => blank($r->{$field}))->values()->all();
    }
}
