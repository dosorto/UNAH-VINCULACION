<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProyectosPorFirmarWorkflowStageApprovalTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autorizado_aprueba_firma_actual_y_avanza_al_siguiente_estado(): void
    {
        $context = $this->crearContexto(2);
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol aprobación');
        $this->crearFirmaSello($empleado, 'firma');
        $this->crearFirmaSello($empleado, 'sello');
        $firmaActual = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $firmaSiguiente = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name]);
        $snapshots = $firmaActual->only(['empleado_id', 'cargo_firma_id', 'flujo_aprobacion_id', 'flujo_aprobacion_etapa_id', 'revision_ciclo', 'orden_revision', 'etapa_codigo', 'etapa_nombre', 'rol_requerido']);

        $aprobada = $this->componenteAprobacion()->aprobarPorEtapa($firmaActual, $user);

        $this->assertSame($firmaActual->id, $aprobada->id);
        $this->assertSame('Aprobado', $aprobada->estado_revision);
        $this->assertNotNull($aprobada->fecha_firma);
        $this->assertSame($empleado->firma->id, $aprobada->firma_id);
        $this->assertSame($empleado->sello->id, $aprobada->sello_id);
        $this->assertSame($snapshots, $aprobada->only(array_keys($snapshots)));
        $this->assertSame('Pendiente', $firmaSiguiente->refresh()->estado_revision);
        $this->assertTrue($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmaSiguiente->refresh()));
        $this->assertSame($context['estados'][1]->id, $context['proyecto']->estado->tipo_estado_id);
    }

    public function test_usuario_no_autorizado_firma_posterior_y_firma_resuelta_no_pueden_aprobarse(): void
    {
        foreach (['Aprobado', 'Anulado', 'Rechazado'] as $estado) {
            $context = $this->crearContexto();
            [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol '.$estado);
            $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, [
                'estado_revision' => $estado,
                'rol_requerido' => $role->name,
            ]);

            $this->assertAprobacionNoDisponible($firma, $user);
            $this->assertSame($estado, $firma->refresh()->estado_revision);
        }

        $context = $this->crearContexto(2);
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol posterior');
        $primera = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $posterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name]);

        $this->assertAprobacionNoDisponible($posterior, $user);
        $this->assertSame('Pendiente', $primera->refresh()->estado_revision);
        $this->assertSame('Pendiente', $posterior->refresh()->estado_revision);

        [$otroUser] = $this->crearUsuarioEmpleadoConRol($role->name, $role);
        $this->assertAprobacionNoDisponible($primera, $otroUser);
        $this->assertSame('Pendiente', $primera->refresh()->estado_revision);
    }

    public function test_firma_legacy_y_segunda_solicitud_son_rechazadas(): void
    {
        $context = $this->crearContexto();
        $this->crearTipoEstado('En curso');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol doble');
        $legacy = $this->crearFirmaLegacy($context['proyecto'], $context['cargos'][0], $empleado);
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);

        $this->assertAprobacionNoDisponible($legacy, $user);

        $this->componenteAprobacion()->aprobarPorEtapa($firma, $user);
        $this->assertAprobacionNoDisponible($firma->refresh(), $user);
    }

    public function test_permite_firma_y_sello_null_sin_modificar_identidad(): void
    {
        $context = $this->crearContexto();
        $this->crearTipoEstado('En curso');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol sin firma');
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);

        $aprobada = $this->componenteAprobacion()->aprobarPorEtapa($firma, $user);

        $this->assertSame('Aprobado', $aprobada->estado_revision);
        $this->assertNull($aprobada->firma_id);
        $this->assertNull($aprobada->sello_id);
        $this->assertSame($empleado->id, $aprobada->empleado_id);
        $this->assertSame($context['cargos'][0]->id, $aprobada->cargo_firma_id);
        $this->assertSame(1, $aprobada->revision_ciclo);
    }

    public function test_callback_opcional_de_aprobacion_se_ejecuta_sin_variable_indefinida(): void
    {
        $context = $this->crearContexto();
        $this->crearTipoEstado('En curso');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol callback');
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $ejecutado = false;

        $this->componenteAprobacion()->aprobarPorEtapaConCallback($firma, $user, function () use (&$ejecutado): void {
            $ejecutado = true;
        });

        $this->assertTrue($ejecutado);
        $this->assertSame('Aprobado', $firma->refresh()->estado_revision);
    }

    public function test_aprobar_no_colapsa_etapas_con_mismo_cargo_y_anula_solo_duplicado_de_misma_etapa(): void
    {
        $context = $this->crearContexto(2, mismoCargo: true);
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol duplicados');
        $principal = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $duplicadoMismaEtapa = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $empleado);
        $duplicadoOtraEtapa = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][1], $empleado);
        $duplicadoOtroCiclo = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $empleado, ['revision_ciclo' => 2]);

        $this->componenteAprobacion()->aprobarPorEtapa($principal, $user);

        $this->assertSame('Aprobado', $principal->refresh()->estado_revision);
        $this->assertSame('Anulado', $duplicadoMismaEtapa->refresh()->estado_revision);
        $this->assertSame('Pendiente', $duplicadoOtraEtapa->refresh()->estado_revision);
        $this->assertSame('Pendiente', $duplicadoOtroCiclo->refresh()->estado_revision);
        $this->assertSame($context['cargos'][0]->id, $duplicadoOtraEtapa->cargo_firma_id);
    }

    public function test_avanza_exactamente_por_etapas_e_ignora_intermedia_anulada(): void
    {
        $context = $this->crearContexto(3);
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol avance');
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, [
            'estado_revision' => 'Anulado',
            'rol_requerido' => $role->name,
        ]);
        $firmaTres = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][2], $empleado, ['rol_requerido' => $role->name]);

        $this->componenteAprobacion()->aprobarPorEtapa($firmaUno, $user);

        $this->assertSame($context['estados'][2]->id, $context['proyecto']->estado->tipo_estado_id);
        $this->assertTrue($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmaTres->refresh()));
        $this->assertSame('Anulado', $firmaDos->refresh()->estado_revision);
    }

    public function test_siguiente_rechazada_o_aprobada_inconsistente_revierte_aprobacion(): void
    {
        foreach (['Rechazado', 'Aprobado'] as $estadoFuturo) {
            $context = $this->crearContexto(2);
            [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol futuro '.$estadoFuturo);
            $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
            $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, [
                'estado_revision' => $estadoFuturo,
                'rol_requerido' => $role->name,
            ]);
            $estadoInicial = $context['proyecto']->estado->tipo_estado_id;

            $this->expectException(\RuntimeException::class);

            try {
                $this->componenteAprobacion()->aprobarPorEtapa($firmaUno, $user);
            } finally {
                $this->assertSame('Pendiente', $firmaUno->refresh()->estado_revision);
                $this->assertSame($estadoFuturo, $firmaDos->refresh()->estado_revision);
                $this->assertSame($estadoInicial, $context['proyecto']->estado->tipo_estado_id);
            }
        }
    }

    public function test_falta_de_cargo_o_tipo_estado_en_siguiente_firma_revierte(): void
    {
        foreach (['sin_cargo', 'sin_tipo_estado'] as $caso) {
            $context = $this->crearContexto(2);
            [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol '.$caso);
            $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
            $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name]);

            if ($caso === 'sin_cargo') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                FirmaProyecto::query()->whereKey($firmaDos->id)->update(['cargo_firma_id' => 999999999]);
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                $firmaDos->refresh();
            } else {
                $context['cargos'][1]->update(['tipo_estado_id' => null]);
            }

            try {
                $this->componenteAprobacion()->aprobarPorEtapa($firmaUno, $user);
                $this->fail('La aprobación debió revertirse.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Pendiente', $firmaUno->refresh()->estado_revision);
                $this->assertNull($firmaUno->firma_id);
                $this->assertNull($firmaUno->sello_id);
                $this->assertNull($firmaUno->fecha_firma);
            }
        }
    }

    public function test_ultima_firma_completa_flujo_de_inscripcion_con_estado_final_sin_id_fijo(): void
    {
        $context = $this->crearContexto();
        $this->crearTipoEstado('En curso');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol final');
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);

        $this->componenteAprobacion()->aprobarPorEtapa($firma, $user);

        $this->assertSame('Aprobado', $firma->refresh()->estado_revision);
        $this->assertSame('En curso', $context['proyecto']->estado->tipoestado->nombre);
        $this->assertSame('Todas las etapas del flujo de inscripción fueron aprobadas.', $context['proyecto']->estado->comentario);
    }

    public function test_finalizacion_acepta_mezcla_aprobado_y_anulado(): void
    {
        $context = $this->crearContexto(2);
        $this->crearTipoEstado('En curso');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol incompleto');
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, [
            'estado_revision' => 'Anulado',
            'rol_requerido' => $role->name,
        ]);
        $firmaUno->forceFill(['orden_revision' => 3])->save();

        $this->componenteAprobacion()->aprobarPorEtapa($firmaUno, $user);

        $this->assertSame('Aprobado', $firmaUno->refresh()->estado_revision);
        $this->assertSame('Anulado', $firmaDos->refresh()->estado_revision);
        $this->assertSame('En curso', $context['proyecto']->estado->tipoestado->nombre);
    }

    public function test_documento_avanza_sin_mezclar_proyecto_ni_otros_documentos(): void
    {
        $context = $this->crearContexto(2);
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol documento avance');
        $documentoA = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $documentoB = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $firmaA1 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name], $documentoA);
        $firmaA2 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name], $documentoA);
        $firmaB1 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name], $documentoB);

        $this->componenteAprobacion()->aprobarPorEtapa($firmaA1, $user);

        $this->assertSame($context['estados'][0]->id, $context['proyecto']->estado->tipo_estado_id);
        $this->assertSame($context['estados'][1]->id, $documentoA->estado->tipo_estado_id);
        $this->assertSame($context['estados'][0]->id, $documentoB->estado->tipo_estado_id);
        $this->assertTrue($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmaA2->refresh()));
        $this->assertSame('Pendiente', $firmaB1->refresh()->estado_revision);
    }

    public function test_ultima_firma_de_documento_usa_comportamiento_final_existente(): void
    {
        $context = $this->crearContexto();
        $this->crearTipoEstado('Aprobado');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol doc final');
        $documento = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name], $documento);
        $this->actingAs($user);

        $this->componenteAprobacion()->aprobarPorEtapa($firma, $user);

        $this->assertSame('Aprobado', $documento->estado->tipoestado->nombre);
        $this->assertSame($context['estados'][0]->id, $context['proyecto']->estado->tipo_estado_id);
    }

    public function test_informe_final_finaliza_tambien_el_proyecto(): void
    {
        $context = $this->crearContexto();
        $this->crearTipoEstado('Aprobado');
        $this->crearTipoEstado('Finalizado');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol informe final');
        $documento = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Final');
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name], $documento);
        $this->actingAs($user);

        $this->componenteAprobacion()->aprobarPorEtapa($firma, $user);

        $this->assertSame('Aprobado', $documento->estado->tipoestado->nombre);
        $this->assertSame('Finalizado', $context['proyecto']->estado->tipoestado->nombre);
    }

    public function test_documento_tipo_no_reconocido_y_documento_inexistente_revierten(): void
    {
        $context = $this->crearContexto();
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol doc error');
        $documentoDesconocido = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Documento desconocido');
        $firmaDesconocida = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name], $documentoDesconocido);
        $documentoEliminado = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $firmaDocumentoEliminado = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name], $documentoEliminado);
        $this->actingAs($user);

        try {
            $this->componenteAprobacion()->aprobarPorEtapa($firmaDesconocida, $user);
            $this->fail('El tipo de documento desconocido debió fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Pendiente', $firmaDesconocida->refresh()->estado_revision);
        }

        $documentoEliminado->delete();

        $this->assertAprobacionNoDisponible($firmaDocumentoEliminado->refresh(), $user);
    }

    public function test_rollback_revierte_firma_estado_y_anulaciones_de_duplicados(): void
    {
        $context = $this->crearContexto(2);
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol rollback');
        $this->crearFirmaSello($empleado, 'firma');
        $this->crearFirmaSello($empleado, 'sello');
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $duplicado = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $empleado);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, [
            'estado_revision' => 'Aprobado',
            'rol_requerido' => $role->name,
        ]);
        $estadoCount = $context['proyecto']->estado_proyecto()->count();

        try {
            $this->componenteAprobacion()->aprobarPorEtapa($firmaUno, $user);
            $this->fail('La aprobación debió revertirse.');
        } catch (\RuntimeException $exception) {
            $firmaUno->refresh();
            $this->assertSame('Pendiente', $firmaUno->estado_revision);
            $this->assertNull($firmaUno->firma_id);
            $this->assertNull($firmaUno->sello_id);
            $this->assertNull($firmaUno->fecha_firma);
            $this->assertSame('Pendiente', $duplicado->refresh()->estado_revision);
            $this->assertSame('Aprobado', $firmaDos->refresh()->estado_revision);
            $this->assertSame($estadoCount, $context['proyecto']->estado_proyecto()->count());
        }
    }

    public function test_compatibilidad_legacy_sigue_intacta(): void
    {
        $context = $this->crearContexto();
        [$user, $empleado] = $this->crearUsuarioEmpleadoConRol('Rol legacy aprobación');
        $legacy = $this->crearFirmaLegacy($context['proyecto'], $context['cargos'][0], $empleado);
        $user->forceFill(['active_role_id' => null])->save();
        $this->actingAs($user->fresh());

        $method = new \ReflectionMethod(ProyectosPorFirmar::class, 'canActOnFirma');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(new ProyectosPorFirmar, $legacy));

        $actualizada = $context['proyecto']->guardarFirmaDeCargo($context['cargos'][0]->id, $empleado);
        $this->assertSame($legacy->id, $actualizada->id);

        $context['proyecto']->sincronizarFirmasDelFlujo();
        $this->assertNotNull($context['proyecto']->firma_proyecto()->first());
    }

    private function assertAprobacionNoDisponible(FirmaProyecto $firma, User $user): void
    {
        try {
            $this->componenteAprobacion()->aprobarPorEtapa($firma, $user);
            $this->fail('La aprobación debió rechazarse.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('La firma ya no se encuentra disponible para aprobación.', $exception->getMessage());
        }
    }

    private function componenteAprobacion(): ProyectosPorFirmarWorkflowStageApprovalComponent
    {
        return new ProyectosPorFirmarWorkflowStageApprovalComponent;
    }

    private function crearContexto(int $cantidadEtapas = 1, bool $mismoCargo = false): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto aprobación '.uniqid(),
            'codigo_proyecto' => 'APR-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_APR_'.uniqid(),
            'nombre' => 'Flujo aprobación',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $empleadoEstado = $this->crearEmpleado();
        $estados = [];
        $cargos = [];
        $etapas = [];
        $cargoCompartido = null;

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $estado = $this->crearTipoEstado('Estado etapa '.$orden);

            if ($mismoCargo) {
                $cargoCompartido = $cargoCompartido ?: $this->crearCargoFirma($estado->id);
                $cargo = $cargoCompartido;
            } else {
                $cargo = $this->crearCargoFirma($estado->id);
            }

            $etapa = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'APR_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa aprobación '.$orden,
                'cargo_firma_id' => $cargo->id,
                'activo' => true,
            ]);

            $estados[] = $estado;
            $cargos[] = $cargo;
            $etapas[] = $etapa;
        }

        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleadoEstado->id,
            'tipo_estado_id' => $estados[0]->id,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        return compact('proyecto', 'flujo', 'estados', 'cargos', 'etapas');
    }

    private function crearFirmaDeEtapa(
        Proyecto $proyecto,
        FlujoAprobacionEtapa $etapa,
        Empleado $empleado,
        array $attributes = [],
        ?DocumentoProyecto $documento = null,
        int $revisionCiclo = 1
    ): FirmaProyecto {
        $relation = $documento ? $documento->firma_documento() : $proyecto->firma_proyecto();

        return $relation->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'rol_requerido' => null,
            'responsable_usuario_id' => null,
            'revision_ciclo' => $revisionCiclo,
        ], $attributes));
    }

    private function crearFirmaDeEtapaManual(
        Proyecto $proyecto,
        FlujoAprobacionEtapa $etapa,
        Empleado $empleado,
        array $attributes = []
    ): FirmaProyecto {
        return $this->crearFirmaDeEtapa($proyecto, $etapa, $empleado, $attributes);
    }

    private function crearFirmaLegacy(Proyecto $proyecto, CargoFirma $cargo, Empleado $empleado): FirmaProyecto
    {
        return $proyecto->firma_proyecto()->create([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
        ]);
    }

    private function crearDocumentoConEstado(Proyecto $proyecto, int $tipoEstadoId, string $tipoDocumento): DocumentoProyecto
    {
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => $tipoDocumento,
            'documento_url' => 'documentos/'.uniqid().'.pdf',
        ]);
        $documento->estado_documento()->create([
            'empleado_id' => $this->crearEmpleado()->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        return $documento;
    }

    private function crearUsuarioEmpleadoConRol(string $nombreRol, ?Role $role = null): array
    {
        $role = $role ?: $this->crearRol($nombreRol);
        $user = User::create([
            'name' => 'Usuario aprobación',
            'email' => 'apr-'.uniqid().'@unah.test',
        ]);
        $empleado = $this->crearEmpleado($user);
        $user->assignRole($role);
        $user->forceFill(['active_role_id' => $role->id])->save();

        return [$user->fresh(), $empleado, $role];
    }

    private function crearEmpleado(?User $user = null): Empleado
    {
        $user = $user ?: User::create([
            'name' => 'Usuario empleado estado',
            'email' => 'estado-'.uniqid().'@unah.test',
        ]);

        return Empleado::create([
            'nombre_completo' => 'Empleado aprobación',
            'numero_empleado' => 'APR-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);
    }

    private function crearFirmaSello(Empleado $empleado, string $tipo): FirmaSelloEmpleado
    {
        return FirmaSelloEmpleado::create([
            'empleado_id' => $empleado->id,
            'tipo' => $tipo,
            'ruta_storage' => $tipo.'/test.png',
            'estado' => true,
        ]);
    }

    private function crearRol(string $nombre): Role
    {
        return Role::create([
            'name' => $nombre.' '.uniqid(),
            'guard_name' => 'web',
        ]);
    }

    private function crearTipoEstado(string $nombre): TipoEstado
    {
        return TipoEstado::create([
            'nombre' => $nombre,
        ]);
    }

    private function crearCargoFirma(?int $tipoEstadoId): CargoFirma
    {
        $tipoCargo = TipoCargoFirma::create([
            'nombre' => 'Cargo aprobación '.uniqid(),
        ]);

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $tipoEstadoId,
        ]);
    }
}

class ProyectosPorFirmarWorkflowStageApprovalComponent extends ProyectosPorFirmar
{
    public function aprobarPorEtapa(FirmaProyecto $firma, User $user): FirmaProyecto
    {
        return $this->aprobarFirmaPorEtapa($firma, $user);
    }

    public function aprobarPorEtapaConCallback(FirmaProyecto $firma, User $user, \Closure $callback): FirmaProyecto
    {
        return $this->aprobarFirmaPorEtapa($firma, $user, $callback);
    }
}
