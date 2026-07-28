<?php

namespace Tests\Feature;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
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

class ProyectoWorkflowStageNewCycleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_crea_nuevo_ciclo_desde_firma_rechazada_reanudando_desde_la_misma_etapa(): void
    {
        $context = $this->crearContexto(3);
        [$responsableUser, $responsableEmpleado] = $this->crearUsuarioEmpleado();
        $nuevoEmpleadoPosterior = $this->crearEmpleado();
        $empleadoAnterior = $this->crearEmpleado();
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $empleadoAnterior, [
            'estado_revision' => 'Aprobado',
            'firma_id' => 111,
            'sello_id' => 222,
            'fecha_firma' => now()->subDay(),
        ]);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $responsableEmpleado, [
            'estado_revision' => 'Rechazado',
            'rol_requerido' => 'Rol snapshot',
            'responsable_usuario_id' => $responsableUser->id,
            'fecha_firma' => now(),
            'hash' => 'hash-rechazada',
        ]);
        $firmaTres = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][2], $this->crearEmpleado(), [
            'hash' => 'hash-posterior',
        ]);
        $duplicadoAnulado = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), [
            'estado_revision' => 'Anulado',
        ]);
        $estadoActualId = $context['proyecto']->estado->id;
        $firmasAntes = FirmaProyecto::count();
        $snapshotRechazada = $firmaDos->only($this->camposSnapshot());
        $snapshotPosterior = $firmaTres->only($this->camposSnapshot());

        $context['etapas'][1]->update([
            'orden' => 99,
            'codigo' => 'CAMBIO_VIVO',
            'nombre' => 'Cambio vivo no debe copiarse',
        ]);

        $creadas = $context['proyecto']->crearNuevoCicloDesdeFirmaRechazada($firmaDos, [
            $context['etapas'][0]->id => $empleadoAnterior->id,
            $context['etapas'][1]->id => $responsableEmpleado->id,
            $context['etapas'][2]->id => $nuevoEmpleadoPosterior->id,
        ]);

        $this->assertCount(2, $creadas);
        $this->assertSame([2, 3], $creadas->pluck('orden_revision')->all());
        $this->assertSame($context['etapas'][1]->id, $creadas[0]->flujo_aprobacion_etapa_id);
        $this->assertSame($context['etapas'][2]->id, $creadas[1]->flujo_aprobacion_etapa_id);
        $this->assertSame(2, $creadas[0]->revision_ciclo);
        $this->assertSame(2, $creadas[1]->revision_ciclo);
        $this->assertSame('Pendiente', $creadas[0]->estado_revision);
        $this->assertSame('Pendiente', $creadas[1]->estado_revision);
        $this->assertNull($creadas[0]->firma_id);
        $this->assertNull($creadas[0]->sello_id);
        $this->assertNull($creadas[0]->fecha_firma);
        $this->assertNotSame($firmaDos->hash, $creadas[0]->hash);
        $this->assertNotSame($firmaTres->hash, $creadas[1]->hash);
        $this->assertSame($snapshotRechazada, $creadas[0]->only($this->camposSnapshot()));
        $this->assertSame($snapshotPosterior, $creadas[1]->only($this->camposSnapshot()));
        $this->assertSame($responsableEmpleado->id, $creadas[0]->empleado_id);
        $this->assertSame($nuevoEmpleadoPosterior->id, $creadas[1]->empleado_id);
        $this->assertSame($responsableUser->id, $creadas[0]->responsable_usuario_id);
        $this->assertSame($firmasAntes + 2, FirmaProyecto::count());
        $this->assertSame('Aprobado', $firmaUno->refresh()->estado_revision);
        $this->assertSame('Rechazado', $firmaDos->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaTres->refresh()->estado_revision);
        $this->assertSame('Anulado', $duplicadoAnulado->refresh()->estado_revision);
        $this->assertSame(1, $firmaUno->revision_ciclo);
        $this->assertSame(1, $firmaDos->revision_ciclo);
        $this->assertSame(111, $firmaUno->firma_id);
        $this->assertSame(222, $firmaUno->sello_id);
        $this->assertSame($estadoActualId, $context['proyecto']->estado->id);
        $this->assertSame($creadas[0]->id, $context['proyecto']->firmaActualDeEtapasDelFlujo($context['flujo']->id, 2)?->id);
        $this->assertFalse($context['proyecto']->firmaEsActualEnFlujoPorEtapa($creadas[1]));
        $this->assertFalse($context['proyecto']->firmasDeEtapasCompletadas($context['flujo']->id, 2));
    }

    public function test_validacion_de_asignaciones_y_responsable_fijo(): void
    {
        $context = $this->crearContexto(2);
        [$responsableUser, $responsableEmpleado] = $this->crearUsuarioEmpleado();
        $otroEmpleado = $this->crearEmpleado();
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $responsableEmpleado, [
            'estado_revision' => 'Rechazado',
            'responsable_usuario_id' => $responsableUser->id,
        ]);
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $otroEmpleado);

        $this->assertFallaNuevoCiclo(
            $context['proyecto'],
            $firmaRechazada,
            [$context['etapas'][1]->id => $otroEmpleado->id],
            'No se indicó un empleado para la etapa'
        );
        $this->assertFallaNuevoCiclo(
            $context['proyecto'],
            $firmaRechazada,
            [$context['etapas'][0]->id => 999999999, $context['etapas'][1]->id => $otroEmpleado->id],
            'El empleado indicado para la etapa'
        );

        $empleadoEliminado = $this->crearEmpleado();
        $empleadoEliminado->delete();
        $this->assertFallaNuevoCiclo(
            $context['proyecto'],
            $firmaRechazada,
            [$context['etapas'][0]->id => $empleadoEliminado->id, $context['etapas'][1]->id => $otroEmpleado->id],
            'El empleado indicado para la etapa'
        );

        $this->assertFallaNuevoCiclo(
            $context['proyecto'],
            $firmaRechazada,
            [$context['etapas'][0]->id => $responsableEmpleado->id, $context['etapas'][1]->id => $otroEmpleado->id, 999999 => $otroEmpleado->id],
            'Se indicó una asignación para una etapa que no pertenece al nuevo ciclo.'
        );
        $this->assertFallaNuevoCiclo(
            $context['proyecto'],
            $firmaRechazada,
            [$context['etapas'][0]->id => $otroEmpleado->id, $context['etapas'][1]->id => $otroEmpleado->id],
            'El empleado indicado no corresponde al responsable fijo'
        );

        $creadas = $context['proyecto']->crearNuevoCicloDesdeFirmaRechazada($firmaRechazada, [
            $context['etapas'][0]->id => $responsableEmpleado->id,
            $context['etapas'][1]->id => $otroEmpleado->id,
        ]);

        $this->assertCount(2, $creadas);
    }

    public function test_valida_firma_origen_y_ciclo_rechazado(): void
    {
        foreach (['Pendiente', 'Aprobado', 'Anulado'] as $estado) {
            $context = $this->crearContexto();
            $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => $estado]);
            $this->assertFallaNuevoCiclo($context['proyecto'], $firma, [$context['etapas'][0]->id => $firma->empleado_id], 'La firma indicada no corresponde a una etapa rechazada.');
        }

        $context = $this->crearContexto();
        $legacy = $this->crearFirmaLegacy($context['proyecto'], $context['cargos'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->assertFallaNuevoCiclo($context['proyecto'], $legacy, [$context['etapas'][0]->id => $legacy->empleado_id], 'La firma indicada no corresponde a una etapa rechazada.');

        foreach (['flujo_aprobacion_etapa_id' => null, 'revision_ciclo' => null, 'orden_revision' => null] as $campo => $valor) {
            $context = $this->crearContexto();
            $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
            $firma->forceFill([$campo => $valor])->save();
            $this->assertFallaNuevoCiclo($context['proyecto'], $firma, [$context['etapas'][0]->id => $firma->empleado_id], 'La firma indicada no corresponde a una etapa rechazada.');
        }

        $contextA = $this->crearContexto();
        $contextB = $this->crearContexto();
        $firmaOtroProyecto = $this->crearFirmaDeEtapa($contextA['proyecto'], $contextA['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->assertFallaNuevoCiclo($contextB['proyecto'], $firmaOtroProyecto, [$contextA['etapas'][0]->id => $firmaOtroProyecto->empleado_id], 'La firma no pertenece al proyecto indicado.');

        $context = $this->crearContexto();
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['revision_ciclo' => 2]);
        $this->assertFallaNuevoCiclo($context['proyecto'], $firmaRechazada, [$context['etapas'][0]->id => $firmaRechazada->empleado_id], 'Ya existe el siguiente ciclo de revisión para este registro.');

        $context = $this->crearContexto();
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), [
            'estado_revision' => 'Rechazado',
            'revision_ciclo' => 1,
        ]);
        $this->assertFallaNuevoCiclo($context['proyecto'], $firmaRechazada, [$context['etapas'][0]->id => $firmaRechazada->empleado_id], 'El ciclo de revisión contiene más de una etapa rechazada.');
    }

    public function test_valida_estados_inconsistentes_y_firmas_activas_duplicadas(): void
    {
        foreach (['Aprobado', 'Rechazado'] as $estadoPosterior) {
            $context = $this->crearContexto(2);
            $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
            $posterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), ['estado_revision' => $estadoPosterior]);
            $this->assertFallaNuevoCiclo(
                $context['proyecto'],
                $firmaRechazada,
                [$context['etapas'][0]->id => $firmaRechazada->empleado_id, $context['etapas'][1]->id => $posterior->empleado_id],
                $estadoPosterior === 'Rechazado'
                    ? 'El ciclo de revisión contiene más de una etapa rechazada.'
                    : 'El ciclo rechazado contiene estados inconsistentes en las etapas posteriores.'
            );
        }

        $context = $this->crearContexto(2);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $posterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());
        $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());
        $this->assertFallaNuevoCiclo(
            $context['proyecto'],
            $firmaRechazada,
            [$context['etapas'][0]->id => $firmaRechazada->empleado_id, $context['etapas'][1]->id => $posterior->empleado_id],
            'El ciclo contiene más de una firma activa para la misma etapa.'
        );
    }

    public function test_funciona_para_documento_sin_mezclar_proyecto_otros_documentos_flujos_o_ciclos(): void
    {
        $context = $this->crearContexto(2);
        $documentoA = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $documentoB = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $firmaA1 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado'], $documentoA);
        $firmaA2 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), [], $documentoA);
        $firmaB1 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), [], $documentoB);
        $otroFlujo = FlujoAprobacion::create(['codigo' => 'OTRO_'.uniqid(), 'nombre' => 'Otro flujo', 'proceso' => 'PROYECTO', 'activo' => true]);
        $otraEtapa = FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $otroFlujo->id,
            'orden' => 1,
            'codigo' => 'OTRO_ETAPA',
            'nombre' => 'Otra etapa',
            'cargo_firma_id' => $context['cargos'][0]->id,
            'activo' => true,
        ]);
        $firmaOtroFlujo = $this->crearFirmaDeEtapa($context['proyecto'], $otraEtapa, $this->crearEmpleado(), [], $documentoA);
        $firmaOtroCiclo = $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), ['revision_ciclo' => 3], $documentoB, 3);

        $estadoProyectoId = $context['proyecto']->estado->id;
        $estadoDocumentoId = $documentoA->estado->id;
        $creadas = $context['proyecto']->crearNuevoCicloDesdeFirmaRechazada($firmaA1, [
            $context['etapas'][0]->id => $firmaA1->empleado_id,
            $context['etapas'][1]->id => $firmaA2->empleado_id,
        ]);

        $this->assertCount(2, $creadas);
        $this->assertSame(DocumentoProyecto::class, $creadas[0]->firmable_type);
        $this->assertSame($documentoA->id, $creadas[0]->firmable_id);
        $this->assertSame('Pendiente', $firmaB1->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaOtroFlujo->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaOtroCiclo->refresh()->estado_revision);
        $this->assertSame($estadoProyectoId, $context['proyecto']->estado->id);
        $this->assertSame($estadoDocumentoId, $documentoA->estado->id);
        $this->assertSame($creadas[0]->id, $context['proyecto']->firmaActualDeEtapasDelFlujo($context['flujo']->id, 2, $documentoA)?->id);
    }

    public function test_falla_con_documento_ajeno(): void
    {
        $contextA = $this->crearContexto();
        $contextB = $this->crearContexto();
        $documentoA = $this->crearDocumentoConEstado($contextA['proyecto'], $contextA['estados'][0]->id, 'Informe Intermedio');
        $firma = $this->crearFirmaDeEtapa($contextA['proyecto'], $contextA['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado'], $documentoA);

        $this->assertFallaNuevoCiclo($contextB['proyecto'], $firma, [$contextA['etapas'][0]->id => $firma->empleado_id], 'La firma no pertenece al proyecto indicado.');
    }

    public function test_tres_etapas_con_mismo_cargo_permanecen_independientes(): void
    {
        $context = $this->crearContexto(3, mismoCargo: true);
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Aprobado']);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $firmaTres = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][2], $this->crearEmpleado());

        $creadas = $context['proyecto']->crearNuevoCicloDesdeFirmaRechazada($firmaDos, [
            $context['etapas'][0]->id => $firmaUno->empleado_id,
            $context['etapas'][1]->id => $firmaDos->empleado_id,
            $context['etapas'][2]->id => $firmaTres->empleado_id,
        ]);

        $this->assertCount(2, $creadas);
        $this->assertSame($context['cargos'][0]->id, $creadas[0]->cargo_firma_id);
        $this->assertSame($context['cargos'][0]->id, $creadas[1]->cargo_firma_id);
        $this->assertSame($context['etapas'][1]->id, $creadas[0]->flujo_aprobacion_etapa_id);
        $this->assertSame($context['etapas'][2]->id, $creadas[1]->flujo_aprobacion_etapa_id);
        $this->assertSame('Aprobado', $firmaUno->refresh()->estado_revision);
    }

    public function test_no_crea_duplicados_si_siguiente_ciclo_ya_existe_o_no_es_ultimo(): void
    {
        $context = $this->crearContexto();
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $context['proyecto']->crearNuevoCicloDesdeFirmaRechazada($firma, [$context['etapas'][0]->id => $firma->empleado_id]);

        $this->assertFallaNuevoCiclo(
            $context['proyecto'],
            $firma,
            [$context['etapas'][0]->id => $firma->empleado_id],
            'Ya existe el siguiente ciclo de revisión para este registro.'
        );
    }

    public function test_rollback_no_deja_ciclo_parcial_ni_modifica_ciclo_anterior(): void
    {
        $context = $this->crearContexto(2);
        [$responsableUser] = $this->crearUsuarioEmpleado();
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), [
            'estado_revision' => 'Rechazado',
        ]);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), [
            'responsable_usuario_id' => $responsableUser->id,
        ]);
        $firmasAntes = FirmaProyecto::count();
        $snapshotUno = $firmaUno->fresh()->toArray();
        $snapshotDos = $firmaDos->fresh()->toArray();

        $this->assertFallaNuevoCiclo(
            $context['proyecto'],
            $firmaUno,
            [$context['etapas'][0]->id => $firmaUno->empleado_id, $context['etapas'][1]->id => $firmaDos->empleado_id],
            'El empleado indicado no corresponde al responsable fijo'
        );

        $this->assertSame($firmasAntes, FirmaProyecto::count());
        $this->assertSame($snapshotUno['estado_revision'], $firmaUno->refresh()->estado_revision);
        $this->assertSame($snapshotDos['empleado_id'], $firmaDos->refresh()->empleado_id);
        $this->assertSame(0, $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2)->count());
    }

    private function assertFallaNuevoCiclo(Proyecto $proyecto, FirmaProyecto $firma, array $empleadosPorEtapa, string $mensaje): void
    {
        try {
            $proyecto->crearNuevoCicloDesdeFirmaRechazada($firma, $empleadosPorEtapa);
            $this->fail('La creación del nuevo ciclo debió fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString($mensaje, $exception->getMessage());
        }
    }

    private function camposSnapshot(): array
    {
        return [
            'cargo_firma_id',
            'flujo_aprobacion_id',
            'flujo_aprobacion_etapa_id',
            'orden_revision',
            'etapa_codigo',
            'etapa_nombre',
            'rol_requerido',
            'responsable_usuario_id',
        ];
    }

    private function crearContexto(int $cantidadEtapas = 1, bool $mismoCargo = false): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto ciclo '.uniqid(),
            'codigo_proyecto' => 'CIC-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_CIC_'.uniqid(),
            'nombre' => 'Flujo ciclo',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $empleadoEstado = $this->crearEmpleado();
        $estados = [];
        $cargos = [];
        $etapas = [];
        $cargoCompartido = null;

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $estado = $this->crearTipoEstado('Estado ciclo '.$orden);

            if ($mismoCargo) {
                $cargoCompartido = $cargoCompartido ?: $this->crearCargoFirma($estado->id);
                $cargo = $cargoCompartido;
            } else {
                $cargo = $this->crearCargoFirma($estado->id);
            }

            $etapa = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'CIC_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa ciclo '.$orden,
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
            'hash' => 'hash-test-'.uniqid(),
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

    private function crearFirmaLegacy(Proyecto $proyecto, CargoFirma $cargo, Empleado $empleado, array $attributes = []): FirmaProyecto
    {
        return $proyecto->firma_proyecto()->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
        ], $attributes));
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

    private function crearUsuarioEmpleado(): array
    {
        $role = Role::create([
            'name' => 'Rol ciclo '.uniqid(),
            'guard_name' => 'web',
        ]);
        $user = User::create([
            'name' => 'Usuario ciclo',
            'email' => 'ciclo-'.uniqid().'@unah.test',
        ]);
        $empleado = $this->crearEmpleado($user);
        $user->assignRole($role);
        $user->forceFill(['active_role_id' => $role->id])->save();

        return [$user->fresh(), $empleado, $role];
    }

    private function crearEmpleado(?User $user = null): Empleado
    {
        $user = $user ?: User::create([
            'name' => 'Usuario empleado ciclo',
            'email' => 'empleado-ciclo-'.uniqid().'@unah.test',
        ]);

        return Empleado::create([
            'nombre_completo' => 'Empleado ciclo',
            'numero_empleado' => 'CIC-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
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
            'nombre' => 'Cargo ciclo '.uniqid(),
        ]);

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $tipoEstadoId,
        ]);
    }
}
