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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProyectosPorFirmarWorkflowStageRejectionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_autorizado_rechaza_firma_actual_bloquea_recorrido_y_envia_proyecto_a_subsanacion(): void
    {
        $context = $this->crearContexto(3);
        $this->crearTipoEstado('Subsanacion');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol rechazo');
        $firmaEmpleado = $this->crearFirmaSello($empleado, 'firma');
        $selloEmpleado = $this->crearFirmaSello($empleado, 'sello');
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, [
            'estado_revision' => 'Aprobado',
            'rol_requerido' => $role->name,
            'fecha_firma' => now()->subDay(),
        ]);
        $firmaUnoAnulada = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $empleado, [
            'estado_revision' => 'Anulado',
        ]);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, [
            'rol_requerido' => $role->name,
            'firma_id' => $firmaEmpleado->id,
            'sello_id' => $selloEmpleado->id,
        ]);
        $firmaTres = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][2], $empleado, ['rol_requerido' => $role->name]);
        $firmasAntes = FirmaProyecto::count();
        $context['proyecto']->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $context['estados'][1]->id,
            'fecha' => now(),
        ]);
        $estadoAnteriorId = $context['proyecto']->estado->id;
        $snapshots = $firmaDos->only(['empleado_id', 'cargo_firma_id', 'flujo_aprobacion_id', 'flujo_aprobacion_etapa_id', 'revision_ciclo', 'orden_revision', 'etapa_codigo', 'etapa_nombre', 'rol_requerido']);

        $rechazada = $this->componenteRechazo()->rechazarPorEtapa($firmaDos, $user, 'Corregir observaciones');

        $this->assertSame($firmaDos->id, $rechazada->id);
        $this->assertSame('Rechazado', $rechazada->estado_revision);
        $this->assertNotNull($rechazada->fecha_firma);
        $this->assertNull($rechazada->firma_id);
        $this->assertNull($rechazada->sello_id);
        $this->assertSame($snapshots, $rechazada->only(array_keys($snapshots)));
        $this->assertSame('Aprobado', $firmaUno->refresh()->estado_revision);
        $this->assertSame('Anulado', $firmaUnoAnulada->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaTres->refresh()->estado_revision);
        $this->assertFalse($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmaTres->refresh()));
        $this->assertNull($context['proyecto']->firmaActualDeEtapasDelFlujo($context['flujo']->id));
        $this->assertFalse($context['proyecto']->firmasDeEtapasCompletadas($context['flujo']->id));
        $this->assertSame('Subsanacion', $context['proyecto']->estado->tipoestado->nombre);
        $this->assertSame('Corregir observaciones', $context['proyecto']->estado->comentario);
        $this->assertSame($empleado->id, $context['proyecto']->estado->empleado_id);
        $this->assertDatabaseHas('estado_proyecto', ['id' => $estadoAnteriorId]);
        $this->assertSame($firmasAntes, FirmaProyecto::count());
        $this->assertSame(1, $rechazada->revision_ciclo);
    }

    public function test_no_autorizado_posterior_resuelta_y_legacy_no_pueden_rechazarse(): void
    {
        foreach (['Aprobado', 'Anulado', 'Rechazado'] as $estado) {
            $context = $this->crearContexto();
            $this->crearTipoEstado('Subsanacion');
            [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol rechazo '.$estado);
            $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, [
                'estado_revision' => $estado,
                'rol_requerido' => $role->name,
            ]);

            $this->assertRechazoNoDisponible($firma, $user);
            $this->assertSame($estado, $firma->refresh()->estado_revision);
        }

        $context = $this->crearContexto(2);
        $this->crearTipoEstado('Subsanacion');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol rechazo posterior');
        $primera = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $posterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name]);
        $legacy = $this->crearFirmaLegacy($context['proyecto'], $context['cargos'][0], $empleado);

        $this->assertRechazoNoDisponible($posterior, $user);
        $this->assertRechazoNoDisponible($legacy, $user);

        [$otroUser] = $this->crearUsuarioEmpleadoConRol($role->name, $role);
        $this->assertRechazoNoDisponible($primera, $otroUser);
        $this->assertSame('Pendiente', $primera->refresh()->estado_revision);
        $this->assertSame('Pendiente', $posterior->refresh()->estado_revision);
        $this->assertSame('Pendiente', $legacy->refresh()->estado_revision);
    }

    public function test_rechazo_anula_duplicados_de_misma_etapa_y_posteriores_sin_tocar_otro_ciclo(): void
    {
        $context = $this->crearContexto(2, mismoCargo: true);
        $this->crearTipoEstado('Subsanacion');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol rechazo duplicados');
        $principal = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);
        $duplicadoMismaEtapa = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $empleado);
        $duplicadoAprobado = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $empleado, ['estado_revision' => 'Aprobado']);
        $duplicadoOtraEtapa = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][1], $empleado);
        $otroCiclo = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][1], $empleado, ['revision_ciclo' => 2]);

        $this->componenteRechazo()->rechazarPorEtapa($principal, $user, 'Rechazo de etapa');

        $this->assertSame('Rechazado', $principal->refresh()->estado_revision);
        $this->assertSame('Anulado', $duplicadoMismaEtapa->refresh()->estado_revision);
        $this->assertSame('Aprobado', $duplicadoAprobado->refresh()->estado_revision);
        $this->assertSame('Pendiente', $duplicadoOtraEtapa->refresh()->estado_revision);
        $this->assertSame('Pendiente', $otroCiclo->refresh()->estado_revision);
    }

    public function test_rechazo_de_documento_envia_solo_documento_a_subsanacion(): void
    {
        $context = $this->crearContexto(2);
        $this->crearTipoEstado('Subsanacion');
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol rechazo documento');
        $documentoA = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $documentoB = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $firmaA1 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name], $documentoA);
        $firmaA2 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name], $documentoA);
        $firmaB1 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name], $documentoB);
        $duplicadoDocumentoB = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $empleado, [], $documentoB);

        $this->componenteRechazo()->rechazarPorEtapa($firmaA1, $user, 'Documento con observaciones');

        $this->assertSame('Rechazado', $firmaA1->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaA2->refresh()->estado_revision);
        $this->assertFalse($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmaA2->refresh()));
        $this->assertSame('Pendiente', $firmaB1->refresh()->estado_revision);
        $this->assertSame('Pendiente', $duplicadoDocumentoB->refresh()->estado_revision);
        $this->assertNull($context['proyecto']->firmaActualDeEtapasDelFlujo($context['flujo']->id, 1, $documentoA));
        $this->assertFalse($context['proyecto']->firmasDeEtapasCompletadas($context['flujo']->id, 1, $documentoA));
        $this->assertSame('Subsanacion', $documentoA->estado->tipoestado->nombre);
        $this->assertSame($context['estados'][0]->id, $documentoB->estado->tipo_estado_id);
        $this->assertSame($context['estados'][0]->id, $context['proyecto']->estado->tipo_estado_id);
    }

    public function test_comentario_vacio_no_modifica_datos(): void
    {
        foreach (['', '   '] as $comentario) {
            $context = $this->crearContexto();
            $this->crearTipoEstado('Subsanacion');
            [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol rechazo comentario');
            $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, ['rol_requerido' => $role->name]);

            try {
                $this->componenteRechazo()->rechazarPorEtapa($firma, $user, $comentario);
                $this->fail('El comentario vacío debió fallar.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Debe indicar el motivo de la subsanación.', $exception->getMessage());
                $this->assertSame('Pendiente', $firma->refresh()->estado_revision);
                $this->assertNull($firma->fecha_firma);
            }
        }
    }

    public function test_fallo_posterior_revierte_rechazo_duplicados_y_estado_de_subsanacion(): void
    {
        $context = $this->crearContexto(2);
        [$user, $empleado, $role] = $this->crearUsuarioEmpleadoConRol('Rol rechazo rollback');
        $firmaEmpleado = $this->crearFirmaSello($empleado, 'firma');
        $selloEmpleado = $this->crearFirmaSello($empleado, 'sello');
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleado, [
            'rol_requerido' => $role->name,
            'firma_id' => $firmaEmpleado->id,
            'sello_id' => $selloEmpleado->id,
        ]);
        $duplicado = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $empleado);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleado, ['rol_requerido' => $role->name]);
        TipoEstado::query()->where('nombre', 'Subsanacion')->update(['nombre' => 'Subsanacion temporal']);
        $estadoCount = $context['proyecto']->estado_proyecto()->count();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No existe un estado de subsanación configurado.');

        try {
            $this->componenteRechazo()->rechazarPorEtapa($firmaUno, $user, 'Debe corregir');
        } finally {
            $this->assertSame('Pendiente', $firmaUno->refresh()->estado_revision);
            $this->assertSame($firmaEmpleado->id, $firmaUno->firma_id);
            $this->assertSame($selloEmpleado->id, $firmaUno->sello_id);
            $this->assertNull($firmaUno->fecha_firma);
            $this->assertSame('Pendiente', $duplicado->refresh()->estado_revision);
            $this->assertSame('Pendiente', $firmaDos->refresh()->estado_revision);
            $this->assertSame($estadoCount, $context['proyecto']->estado_proyecto()->count());
        }
    }

    public function test_compatibilidad_legacy_sigue_intacta(): void
    {
        $context = $this->crearContexto();
        [$user, $empleado] = $this->crearUsuarioEmpleadoConRol('Rol legacy rechazo');
        $legacy = $this->crearFirmaLegacy($context['proyecto'], $context['cargos'][0], $empleado);
        $user->forceFill(['active_role_id' => null])->save();
        $this->actingAs($user->fresh());

        $method = new \ReflectionMethod(ProyectosPorFirmar::class, 'canActOnFirma');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(new ProyectosPorFirmar, $legacy));

        $this->assertFalse($this->componenteRechazo()->puedeRechazarPorEtapa($legacy, $user));
        $this->assertSame('Pendiente', $legacy->refresh()->estado_revision);
    }

    private function assertRechazoNoDisponible(FirmaProyecto $firma, User $user): void
    {
        try {
            $this->componenteRechazo()->rechazarPorEtapa($firma, $user, 'Observación');
            $this->fail('El rechazo debió rechazarse.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('La firma ya no se encuentra disponible para rechazo.', $exception->getMessage());
        }
    }

    private function componenteRechazo(): ProyectosPorFirmarWorkflowStageRejectionComponent
    {
        return new ProyectosPorFirmarWorkflowStageRejectionComponent;
    }

    private function crearContexto(int $cantidadEtapas = 1, bool $mismoCargo = false): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto rechazo '.uniqid(),
            'codigo_proyecto' => 'RECH-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_RECH_'.uniqid(),
            'nombre' => 'Flujo rechazo',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $empleadoEstado = $this->crearEmpleado();
        $estados = [];
        $cargos = [];
        $etapas = [];
        $cargoCompartido = null;

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $estado = $this->crearTipoEstado('Estado rechazo '.$orden);

            if ($mismoCargo) {
                $cargoCompartido = $cargoCompartido ?: $this->crearCargoFirma($estado->id);
                $cargo = $cargoCompartido;
            } else {
                $cargo = $this->crearCargoFirma($estado->id);
            }

            $etapa = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'RECH_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa rechazo '.$orden,
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
        array $attributes = [],
        ?DocumentoProyecto $documento = null,
        int $revisionCiclo = 1
    ): FirmaProyecto {
        return $this->crearFirmaDeEtapa($proyecto, $etapa, $empleado, $attributes, $documento, $revisionCiclo);
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
            'name' => 'Usuario rechazo',
            'email' => 'rech-'.uniqid().'@unah.test',
        ]);
        $empleado = $this->crearEmpleado($user);
        $user->assignRole($role);
        $user->forceFill(['active_role_id' => $role->id])->save();

        return [$user->fresh(), $empleado, $role];
    }

    private function crearEmpleado(?User $user = null): Empleado
    {
        $user = $user ?: User::create([
            'name' => 'Usuario empleado rechazo',
            'email' => 'estado-rech-'.uniqid().'@unah.test',
        ]);

        return Empleado::create([
            'nombre_completo' => 'Empleado rechazo',
            'numero_empleado' => 'RECH-'.uniqid(),
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
            'nombre' => 'Cargo rechazo '.uniqid(),
        ]);

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $tipoEstadoId,
        ]);
    }
}

class ProyectosPorFirmarWorkflowStageRejectionComponent extends ProyectosPorFirmar
{
    public function rechazarPorEtapa(FirmaProyecto $firma, User $user, string $comentario): FirmaProyecto
    {
        return $this->rechazarFirmaPorEtapa($firma, $user, $comentario);
    }

    public function puedeRechazarPorEtapa(FirmaProyecto $firma, User $user): bool
    {
        return $this->canActOnWorkflowStageFirma($firma, $user);
    }
}
