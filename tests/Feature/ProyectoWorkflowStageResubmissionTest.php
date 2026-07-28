<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\HistorialProyecto;
use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProyectoWorkflowStageResubmissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_coordinador_autorizado_reenvia_proyecto_desde_subsanacion_y_reanuda_la_etapa_rechazada(): void
    {
        $context = $this->crearContexto(3);
        [$coordinadorUser, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        [$primerRevisorUser, $primerRevisor, $primerRol] = $this->crearUsuarioEmpleadoConRol('Rol primera etapa');
        [$segundoRevisorUser, $segundoRevisor] = $this->crearUsuarioEmpleadoConRol('Rol segunda etapa');
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $firmaAnterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Aprobado']);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), [
            'estado_revision' => 'Rechazado',
            'rol_requerido' => $primerRol->name,
        ]);
        $firmaPosterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][2], $this->crearEmpleado());
        $estadoSubsanacionId = $context['proyecto']->estado->id;
        $estadoCount = $context['proyecto']->estado_proyecto()->count();
        $firmasAntes = FirmaProyecto::count();
        $component = $this->componente($context['proyecto']);
        Mail::fake();

        $creadas = $component->reenviarPorEtapa($firmaRechazada, $coordinadorUser, [
            $context['etapas'][0]->id => $firmaAnterior->empleado_id,
            $context['etapas'][1]->id => $primerRevisor->id,
            $context['etapas'][2]->id => $segundoRevisor->id,
        ]);

        $this->assertCount(2, $creadas);
        $this->assertSame(2, $creadas[0]->revision_ciclo);
        $this->assertSame($context['etapas'][1]->id, $creadas[0]->flujo_aprobacion_etapa_id);
        $this->assertSame($context['etapas'][2]->id, $creadas[1]->flujo_aprobacion_etapa_id);
        $this->assertSame('Pendiente', $creadas[0]->estado_revision);
        $this->assertSame('Pendiente', $creadas[1]->estado_revision);
        $this->assertNull($creadas[0]->firma_id);
        $this->assertNull($creadas[0]->sello_id);
        $this->assertNull($creadas[0]->fecha_firma);
        $this->assertSame($primerRevisor->id, $creadas[0]->empleado_id);
        $this->assertSame($segundoRevisor->id, $creadas[1]->empleado_id);
        $this->assertSame('Aprobado', $firmaAnterior->refresh()->estado_revision);
        $this->assertSame('Rechazado', $firmaRechazada->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaPosterior->refresh()->estado_revision);
        $this->assertSame($firmasAntes + 2, FirmaProyecto::count());
        $this->assertDatabaseHas('estado_proyecto', ['id' => $estadoSubsanacionId]);
        $this->assertSame($estadoCount + 1, $context['proyecto']->estado_proyecto()->count());
        $this->assertSame($context['cargos'][1]->tipo_estado_id, $context['proyecto']->estado->tipo_estado_id);
        $this->assertSame($coordinador->id, $context['proyecto']->estado->empleado_id);
        $this->assertStringContainsString($creadas[0]->etapa_nombre, $context['proyecto']->estado->comentario);
        $this->assertSame($creadas[0]->id, $context['proyecto']->firmaActualDeEtapasDelFlujo($context['flujo']->id, 2)?->id);
        $this->assertFalse($context['proyecto']->firmaEsActualEnFlujoPorEtapa($creadas[1]));
        $this->assertFalse($context['proyecto']->firmasDeEtapasCompletadas($context['flujo']->id, 2));
        $this->assertFalse($this->componenteAutorizacion()->puedeActuar($creadas[1]->fresh(), $segundoRevisorUser));
        $this->assertFalse($component->subsanarModal);
        Mail::assertNothingSent();
    }

    public function test_autorizacion_requiere_coordinador_permiso_y_empleado_sin_bypass_admin(): void
    {
        $context = $this->crearContexto(2);
        [$coordinadorUser, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $firmaPosterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());

        [$ajenoUser] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->assertFallaReenvio($context['proyecto'], $firmaRechazada, $ajenoUser, [
            $context['etapas'][0]->id => $firmaRechazada->empleado_id,
            $context['etapas'][1]->id => $firmaPosterior->empleado_id,
        ], 'No tiene autorización para reenviar este registro desde subsanación.');

        [$sinPermisoUser, $sinPermisoEmpleado] = $this->crearUsuarioEmpleado();
        $this->vincularCoordinador($context['proyecto'], $sinPermisoEmpleado);
        $this->assertFallaReenvio($context['proyecto'], $firmaRechazada, $sinPermisoUser, [
            $context['etapas'][0]->id => $firmaRechazada->empleado_id,
            $context['etapas'][1]->id => $firmaPosterior->empleado_id,
        ], 'No tiene autorización para reenviar este registro desde subsanación.');

        [$adminUser] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto'], roles: ['admin']);
        $this->assertFallaReenvio($context['proyecto'], $firmaRechazada, $adminUser, [
            $context['etapas'][0]->id => $firmaRechazada->empleado_id,
            $context['etapas'][1]->id => $firmaPosterior->empleado_id,
        ], 'No tiene autorización para reenviar este registro desde subsanación.');

        $usuarioSinEmpleado = User::create([
            'name' => 'Sin empleado',
            'email' => 'sin-empleado-'.uniqid().'@unah.test',
        ]);
        $usuarioSinEmpleado->givePermissionTo($this->permiso('docente.crear-proyecto'));
        $this->assertFallaReenvio($context['proyecto'], $firmaRechazada, $usuarioSinEmpleado, [
            $context['etapas'][0]->id => $firmaRechazada->empleado_id,
            $context['etapas'][1]->id => $firmaPosterior->empleado_id,
        ], 'No tiene autorización para reenviar este registro desde subsanación.');

        $creadas = $this->componente($context['proyecto'])->reenviarPorEtapa($firmaRechazada, $coordinadorUser, [
            $context['etapas'][0]->id => $firmaRechazada->empleado_id,
            $context['etapas'][1]->id => $firmaPosterior->empleado_id,
        ]);
        $this->assertCount(2, $creadas);
    }

    public function test_estado_subsanacion_actual_es_obligatorio_y_no_basta_historial(): void
    {
        $context = $this->crearContexto(2);
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $empleado);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $empleado);
        $this->crearEstado($context['proyecto'], 'Estado actual no subsanacion', $empleado);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $firmaPosterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());

        $this->assertFallaReenvio($context['proyecto'], $firmaRechazada, $user, [
            $context['etapas'][0]->id => $firmaRechazada->empleado_id,
            $context['etapas'][1]->id => $firmaPosterior->empleado_id,
        ], 'El registro no se encuentra en estado de Subsanación.');

        $context = $this->crearContexto(2);
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $empleado);
        $this->crearEstado($context['proyecto'], 'Estado inicial', $empleado);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $firmaPosterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());

        $this->assertFallaReenvio($context['proyecto'], $firmaRechazada, $user, [
            $context['etapas'][0]->id => $firmaRechazada->empleado_id,
            $context['etapas'][1]->id => $firmaPosterior->empleado_id,
        ], 'El registro no se encuentra en estado de Subsanación.');
    }

    public function test_documento_en_subsanacion_reenvia_sin_cambiar_estado_del_proyecto_ni_otro_documento(): void
    {
        $context = $this->crearContexto(2);
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $empleado);
        $this->crearEstado($context['proyecto'], 'En curso', $empleado);
        $documentoA = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $documentoB = $this->crearDocumentoConEstado($context['proyecto'], $context['estados'][0]->id, 'Informe Intermedio');
        $this->crearEstado($documentoA, 'Subsanacion', $empleado);
        $firmaA1 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado'], $documentoA);
        $firmaA2 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), [], $documentoA);
        $firmaB1 = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), [], $documentoB);
        $estadoProyectoId = $context['proyecto']->estado->id;
        $estadoDocumentoBId = $documentoB->estado->id;

        $creadas = $this->componente($context['proyecto'])->reenviarPorEtapa($firmaA1, $user, [
            $context['etapas'][0]->id => $firmaA1->empleado_id,
            $context['etapas'][1]->id => $firmaA2->empleado_id,
        ]);

        $this->assertCount(2, $creadas);
        $this->assertSame(DocumentoProyecto::class, $creadas[0]->firmable_type);
        $this->assertSame($documentoA->id, $creadas[0]->firmable_id);
        $this->assertSame($context['cargos'][0]->tipo_estado_id, $documentoA->estado->tipo_estado_id);
        $this->assertSame($estadoProyectoId, $context['proyecto']->estado->id);
        $this->assertSame($estadoDocumentoBId, $documentoB->estado->id);
        $this->assertSame('Pendiente', $firmaB1->refresh()->estado_revision);
    }

    public function test_documento_fuera_de_subsanacion_y_firmas_ajenas_fallan(): void
    {
        $contextA = $this->crearContexto(2);
        $contextB = $this->crearContexto(2);
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($contextA['proyecto'], $empleado);
        $this->crearEstado($contextA['proyecto'], 'Subsanacion', $empleado);
        $documento = $this->crearDocumentoConEstado($contextA['proyecto'], $contextA['estados'][0]->id, 'Informe Intermedio');
        $firmaDoc = $this->crearFirmaDeEtapa($contextA['proyecto'], $contextA['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado'], $documento);

        $this->assertFallaReenvio($contextA['proyecto'], $firmaDoc, $user, [
            $contextA['etapas'][0]->id => $firmaDoc->empleado_id,
        ], 'El registro no se encuentra en estado de Subsanación.');

        $firmaOtroProyecto = $this->crearFirmaDeEtapa($contextB['proyecto'], $contextB['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->assertFallaReenvio($contextA['proyecto'], $firmaOtroProyecto, $user, [
            $contextB['etapas'][0]->id => $firmaOtroProyecto->empleado_id,
        ], 'La firma no pertenece al proyecto indicado.');
    }

    public function test_valida_firma_rechazada_y_no_crea_dos_ciclos(): void
    {
        foreach (['Pendiente', 'Aprobado', 'Anulado'] as $estado) {
            $context = $this->crearContexto();
            [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
            $this->vincularCoordinador($context['proyecto'], $empleado);
            $this->crearEstado($context['proyecto'], 'Subsanacion', $empleado);
            $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => $estado]);

            $this->assertFallaReenvio($context['proyecto'], $firma, $user, [
                $context['etapas'][0]->id => $firma->empleado_id,
            ], 'La firma indicada no corresponde a una etapa rechazada.');
        }

        $context = $this->crearContexto();
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $empleado);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $empleado);
        $legacy = $this->crearFirmaLegacy($context['proyecto'], $context['cargos'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->assertFallaReenvio($context['proyecto'], $legacy, $user, [
            $context['etapas'][0]->id => $legacy->empleado_id,
        ], 'La firma indicada no corresponde a una etapa rechazada.');

        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $firma->forceFill(['flujo_aprobacion_etapa_id' => null])->save();
        $this->assertFallaReenvio($context['proyecto'], $firma, $user, [
            $context['etapas'][0]->id => $firma->empleado_id,
        ], 'La firma indicada no corresponde a una etapa rechazada.');

        $context = $this->crearContexto();
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $empleado);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $empleado);
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->crearFirmaDeEtapaManual($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['revision_ciclo' => 2]);
        $this->assertFallaReenvio($context['proyecto'], $firma, $user, [
            $context['etapas'][0]->id => $firma->empleado_id,
        ], 'Ya existe el siguiente ciclo de revisión para este registro.');
    }

    public function test_rollback_externo_revierte_ciclo_si_falla_estado_o_validacion_posterior(): void
    {
        $context = $this->crearContexto(2);
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $empleado);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $empleado);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $firmaPosterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());
        $estadoActualId = $context['proyecto']->estado->id;
        $firmasAntes = FirmaProyecto::count();

        try {
            $this->componente($context['proyecto'], HistorialProyectoResubmissionEstadoFallaComponent::class)
                ->reenviarPorEtapa($firmaRechazada, $user, [
                    $context['etapas'][0]->id => $firmaRechazada->empleado_id,
                    $context['etapas'][1]->id => $firmaPosterior->empleado_id,
                ]);
            $this->fail('El fallo de estado debió revertir.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fallo controlado al crear estado.', $exception->getMessage());
            $this->assertSame($firmasAntes, FirmaProyecto::count());
            $this->assertSame($estadoActualId, $context['proyecto']->estado->id);
            $this->assertSame(0, $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2)->count());
        }

        try {
            $this->componente($context['proyecto'], HistorialProyectoResubmissionValidacionFallaComponent::class)
                ->reenviarPorEtapa($firmaRechazada, $user, [
                    $context['etapas'][0]->id => $firmaRechazada->empleado_id,
                    $context['etapas'][1]->id => $firmaPosterior->empleado_id,
                ]);
            $this->fail('El fallo posterior debió revertir.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fallo controlado posterior.', $exception->getMessage());
            $this->assertSame($firmasAntes, FirmaProyecto::count());
            $this->assertSame($estadoActualId, $context['proyecto']->estado->id);
            $this->assertSame('Rechazado', $firmaRechazada->refresh()->estado_revision);
        }
    }

    public function test_primera_firma_invalida_revierte_y_no_deja_estado_sin_firmas(): void
    {
        $context = $this->crearContexto();
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $empleado);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $empleado);
        $context['cargos'][0]->update(['tipo_estado_id' => null]);
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $estadoActualId = $context['proyecto']->estado->id;
        $firmasAntes = FirmaProyecto::count();

        $this->assertFallaReenvio($context['proyecto'], $firma, $user, [
            $context['etapas'][0]->id => $firma->empleado_id,
        ], 'No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');

        $this->assertSame($firmasAntes, FirmaProyecto::count());
        $this->assertSame($estadoActualId, $context['proyecto']->estado->id);
    }

    public function test_no_mezcla_flujos_ciclos_documentos_ni_etapas_con_mismo_cargo(): void
    {
        $context = $this->crearContexto(3, mismoCargo: true);
        [$user, $empleado] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->vincularCoordinador($context['proyecto'], $empleado);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $empleado);
        $firmaUno = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Aprobado']);
        $firmaDos = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $firmaTres = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][2], $this->crearEmpleado());
        $otroFlujo = FlujoAprobacion::create(['codigo' => 'REENVIO_OTRO_'.uniqid(), 'nombre' => 'Otro flujo', 'proceso' => 'PROYECTO', 'activo' => true]);
        $otraEtapa = FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $otroFlujo->id,
            'orden' => 1,
            'codigo' => 'OTRA',
            'nombre' => 'Otra etapa',
            'cargo_firma_id' => $context['cargos'][0]->id,
            'activo' => true,
        ]);
        $firmaOtroFlujo = $this->crearFirmaDeEtapa($context['proyecto'], $otraEtapa, $this->crearEmpleado());

        $creadas = $this->componente($context['proyecto'])->reenviarPorEtapa($firmaDos, $user, [
            $context['etapas'][0]->id => $firmaUno->empleado_id,
            $context['etapas'][1]->id => $firmaDos->empleado_id,
            $context['etapas'][2]->id => $firmaTres->empleado_id,
        ]);

        $this->assertCount(2, $creadas);
        $this->assertSame($context['etapas'][1]->id, $creadas[0]->flujo_aprobacion_etapa_id);
        $this->assertSame($context['etapas'][2]->id, $creadas[1]->flujo_aprobacion_etapa_id);
        $this->assertSame($context['cargos'][0]->id, $creadas[0]->cargo_firma_id);
        $this->assertSame($context['cargos'][0]->id, $creadas[1]->cargo_firma_id);
        $this->assertSame('Aprobado', $firmaUno->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaTres->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaOtroFlujo->refresh()->estado_revision);
    }

    private function assertFallaReenvio(Proyecto $proyecto, FirmaProyecto $firma, User $user, array $empleadosPorEtapa, string $mensaje): void
    {
        try {
            $this->componente($proyecto)->reenviarPorEtapa($firma, $user, $empleadosPorEtapa);
            $this->fail('El reenvío debió fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString($mensaje, $exception->getMessage());
        }
    }

    private function componente(Proyecto $proyecto, string $class = HistorialProyectoResubmissionComponent::class): HistorialProyectoResubmissionComponent
    {
        $component = new $class;
        $component->proyecto = $proyecto;

        return $component;
    }

    private function componenteAutorizacion(): ProyectosPorFirmarResubmissionAuthorizationComponent
    {
        return new ProyectosPorFirmarResubmissionAuthorizationComponent;
    }

    private function crearContexto(int $cantidadEtapas = 1, bool $mismoCargo = false): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto reenvio '.uniqid(),
            'codigo_proyecto' => 'REEN-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_REEN_'.uniqid(),
            'nombre' => 'Flujo reenvio',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $estados = [];
        $cargos = [];
        $etapas = [];
        $cargoCompartido = null;

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $estado = $this->crearTipoEstado('Estado reenvio '.$orden);

            if ($mismoCargo) {
                $cargoCompartido = $cargoCompartido ?: $this->crearCargoFirma($estado->id);
                $cargo = $cargoCompartido;
            } else {
                $cargo = $this->crearCargoFirma($estado->id);
            }

            $etapa = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'REEN_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa reenvio '.$orden,
                'cargo_firma_id' => $cargo->id,
                'activo' => true,
            ]);

            $estados[] = $estado;
            $cargos[] = $cargo;
            $etapas[] = $etapa;
        }

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

    private function crearEstado(Proyecto|DocumentoProyecto $registro, string $estadoNombre, Empleado $empleado): void
    {
        $tipoEstado = $this->crearTipoEstado($estadoNombre);
        $relation = $registro instanceof DocumentoProyecto
            ? $registro->estado_documento()
            : $registro->estado_proyecto();

        $relation->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $tipoEstado->id,
            'fecha' => now(),
            'comentario' => $estadoNombre === 'Subsanacion' ? 'Motivo de subsanación de prueba.' : null,
            'es_actual' => true,
        ]);
    }

    private function vincularCoordinador(Proyecto $proyecto, Empleado $empleado): void
    {
        DB::table('empleado_proyecto')->insert([
            'empleado_id' => $empleado->id,
            'proyecto_id' => $proyecto->id,
            'rol' => 'Coordinador',
            'hash' => 'hash-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearUsuarioEmpleado(array $permisos = [], array $roles = []): array
    {
        $user = User::create([
            'name' => 'Usuario reenvio',
            'email' => 'reenvio-'.uniqid().'@unah.test',
        ]);
        $empleado = $this->crearEmpleado($user);

        foreach ($permisos as $permiso) {
            $user->givePermissionTo($this->permiso($permiso));
        }

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return [$user->fresh(), $empleado];
    }

    private function crearUsuarioEmpleadoConRol(string $nombreRol): array
    {
        $role = Role::create([
            'name' => $nombreRol.' '.uniqid(),
            'guard_name' => 'web',
        ]);
        $user = User::create([
            'name' => 'Usuario revisor reenvio',
            'email' => 'revisor-reenvio-'.uniqid().'@unah.test',
        ]);
        $empleado = $this->crearEmpleado($user);
        $user->assignRole($role);
        $user->forceFill(['active_role_id' => $role->id])->save();

        return [$user->fresh(), $empleado, $role];
    }

    private function crearEmpleado(?User $user = null): Empleado
    {
        $user = $user ?: User::create([
            'name' => 'Usuario empleado reenvio',
            'email' => 'empleado-reenvio-'.uniqid().'@unah.test',
        ]);

        return Empleado::create([
            'nombre_completo' => 'Empleado reenvio',
            'numero_empleado' => 'REEN-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);
    }

    private function crearTipoEstado(string $nombre): TipoEstado
    {
        return TipoEstado::firstOrCreate([
            'nombre' => $nombre,
        ]);
    }

    private function crearCargoFirma(?int $tipoEstadoId): CargoFirma
    {
        $tipoCargo = TipoCargoFirma::create([
            'nombre' => 'Cargo reenvio '.uniqid(),
        ]);

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
            'tipo_estado_id' => $tipoEstadoId,
        ]);
    }

    private function permiso(string $nombre): Permission
    {
        return Permission::firstOrCreate([
            'name' => $nombre,
            'guard_name' => 'web',
        ]);
    }
}

class HistorialProyectoResubmissionComponent extends HistorialProyecto
{
    public function reenviarPorEtapa(FirmaProyecto $firma, User $user, array $empleadosPorEtapa)
    {
        return $this->reenviarDesdeSubsanacionPorEtapa($firma, $user, $empleadosPorEtapa);
    }
}

class HistorialProyectoResubmissionEstadoFallaComponent extends HistorialProyectoResubmissionComponent
{
    protected function registrarEstadoDeReenvioPorEtapa(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        int $tipoEstadoId,
        int $empleadoId,
        FirmaProyecto $primeraFirma
    ): void {
        throw new \RuntimeException('Fallo controlado al crear estado.');
    }
}

class HistorialProyectoResubmissionValidacionFallaComponent extends HistorialProyectoResubmissionComponent
{
    protected function validarReenvioPorEtapaCompletado(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        FirmaProyecto $firmaRechazada,
        FirmaProyecto $primeraFirma
    ): void {
        throw new \RuntimeException('Fallo controlado posterior.');
    }
}

class ProyectosPorFirmarResubmissionAuthorizationComponent extends ProyectosPorFirmar
{
    public function puedeActuar(FirmaProyecto $firma, User $user): bool
    {
        return $this->canActOnWorkflowStageFirma($firma, $user);
    }
}
