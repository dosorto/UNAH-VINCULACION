<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\HistorialProyecto;
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
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HistorialProyectoWorkflowStageResubmissionIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_subsanar_detecta_firma_rechazada_por_etapa_y_reenvia_por_el_camino_nuevo(): void
    {
        $context = $this->crearContexto(3);
        [$user, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->actingAs($user);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $firmaAnterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Aprobado']);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), [
            'estado_revision' => 'Rechazado',
            'responsable_usuario_id' => null,
        ]);
        $firmaPosterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][2], $this->crearEmpleado());
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), [
            'estado_revision' => 'Anulado',
        ]);
        $estadoSubsanacionId = $context['proyecto']->estado->id;
        $firmasAntes = FirmaProyecto::count();
        Mail::fake();

        $component = $this->componente($context['proyecto']);
        $component->subsanarModal = true;
        $component->subsanarComentario = 'Correcciones realizadas';
        $component->subsanar();

        $firmasNuevoCiclo = $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2);
        $this->assertCount(2, $firmasNuevoCiclo);
        $this->assertSame($context['etapas'][1]->id, $firmasNuevoCiclo[0]->flujo_aprobacion_etapa_id);
        $this->assertSame($context['etapas'][2]->id, $firmasNuevoCiclo[1]->flujo_aprobacion_etapa_id);
        $this->assertSame($firmaRechazada->empleado_id, $firmasNuevoCiclo[0]->empleado_id);
        $this->assertSame($firmaPosterior->empleado_id, $firmasNuevoCiclo[1]->empleado_id);
        $this->assertSame('Aprobado', $firmaAnterior->refresh()->estado_revision);
        $this->assertSame('Rechazado', $firmaRechazada->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaPosterior->refresh()->estado_revision);
        $this->assertSame($firmasAntes + 2, FirmaProyecto::count());
        $this->assertSame($estadoSubsanacionId, $context['proyecto']->estado_proyecto()->whereKey($estadoSubsanacionId)->value('id'));
        $this->assertSame($context['cargos'][1]->tipo_estado_id, $context['proyecto']->estado->tipo_estado_id);
        $this->assertSame($firmasNuevoCiclo[0]->id, $context['proyecto']->firmaActualDeEtapasDelFlujo($context['flujo']->id, 2)?->id);
        $this->assertFalse($context['proyecto']->firmaEsActualEnFlujoPorEtapa($firmasNuevoCiclo[1]));
        $this->assertFalse($component->subsanarModal);
        $this->assertSame('', $component->subsanarComentario);
        $this->assertNotEmpty(session('flash_notifications'));
        Mail::assertNothingSent();
    }

    public function test_subsanar_por_livewire_test_invoca_el_metodo_publico_en_camino_nuevo(): void
    {
        $context = $this->crearContexto(2);
        [$user, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto'], roles: ['admin']);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());

        Livewire::actingAs($user)
            ->test(HistorialProyecto::class, ['proyecto' => $context['proyecto']])
            ->set('subsanarComentario', 'Correcciones listas')
            ->call('subsanar')
            ->assertDispatched('notify');

        $this->assertSame(2, $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2)->count());
        $this->assertSame('Rechazado', $firmaRechazada->refresh()->estado_revision);
    }

    public function test_subsanar_conserva_el_camino_legacy_si_no_hay_firma_rechazada_por_etapa(): void
    {
        $context = $this->crearContexto();
        $this->crearTipoEstado('Subsanacion');
        [$user, $empleado] = $this->crearUsuarioEmpleado();
        $this->actingAs($user);
        $this->crearEstado($context['proyecto'], 'Revision legacy', $empleado);
        $cargoLegacy = $this->crearCargoFirma($context['proyecto']->estado->tipo_estado_id, 'Cargo legacy');
        $firmaLegacy = $this->crearFirmaLegacy($context['proyecto'], $cargoLegacy, $empleado, ['estado_revision' => 'Pendiente']);
        $firmaAprobada = $this->crearFirmaLegacy($context['proyecto'], $cargoLegacy, $this->crearEmpleado(), ['estado_revision' => 'Aprobado']);
        $estadoCount = $context['proyecto']->estado_proyecto()->count();

        $component = $this->componente($context['proyecto'], HistorialProyectoIntegrationNoDebeReenviarComponent::class);
        $component->subsanarComentario = 'Debe corregir';
        $component->subsanar();

        $this->assertFalse($component->reenviarInvocado);
        $this->assertSame('Pendiente', $firmaLegacy->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaAprobada->refresh()->estado_revision);
        $this->assertNull($firmaAprobada->firma_id);
        $this->assertSame('Subsanacion', $context['proyecto']->estado->tipoestado->nombre);
        $this->assertSame($estadoCount + 1, $context['proyecto']->estado_proyecto()->count());
    }

    public function test_firma_rechazada_legacy_o_cargo_no_activan_el_camino_nuevo(): void
    {
        $context = $this->crearContexto();
        $this->crearTipoEstado('Subsanacion');
        [$user, $empleado] = $this->crearUsuarioEmpleado();
        $this->actingAs($user);
        $this->crearEstado($context['proyecto'], 'Revision legacy con rechazo', $empleado);
        $cargoLegacy = $this->crearCargoFirma($context['proyecto']->estado->tipo_estado_id, 'Cargo legacy rechazado');
        $firmaPendiente = $this->crearFirmaLegacy($context['proyecto'], $cargoLegacy, $empleado);
        $firmaRechazadaLegacy = $this->crearFirmaLegacy($context['proyecto'], $cargoLegacy, $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);

        $component = $this->componente($context['proyecto'], HistorialProyectoIntegrationNoDebeReenviarComponent::class);
        $component->subsanarComentario = 'Correccion legacy';
        $component->subsanar();

        $this->assertFalse($component->reenviarInvocado);
        $this->assertSame('Pendiente', $firmaPendiente->refresh()->estado_revision);
        $this->assertSame('Pendiente', $firmaRechazadaLegacy->refresh()->estado_revision);
        $this->assertSame('Subsanacion', $context['proyecto']->estado->tipoestado->nombre);
    }

    public function test_varias_firmas_rechazadas_del_ultimo_ciclo_bloquean_sin_fallback_legacy(): void
    {
        $context = $this->crearContexto(2);
        [$user, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->actingAs($user);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $estadoActualId = $context['proyecto']->estado->id;
        $firmasAntes = FirmaProyecto::count();

        $component = $this->componente($context['proyecto']);
        $component->subsanarModal = true;
        $component->subsanarComentario = 'Correcciones listas';
        $component->subsanar();

        $this->assertSame($firmasAntes, FirmaProyecto::count());
        $this->assertSame($estadoActualId, $context['proyecto']->estado->id);
        $this->assertTrue($component->subsanarModal);
        $this->assertStringContainsString(
            'El último ciclo contiene más de una etapa rechazada',
            session('flash_notifications')[0]['body']
        );
    }

    public function test_asignaciones_para_reenvio_rechazan_duplicados_activos_y_empleados_invalidos(): void
    {
        $context = $this->crearContexto(2);
        [$user, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->actingAs($user);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado());
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());

        $component = $this->componente($context['proyecto']);
        $component->subsanarComentario = 'Correcciones listas';
        $component->subsanar();

        $this->assertSame(0, $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2)->count());
        $this->assertStringContainsString(
            'El ciclo contiene más de una asignación activa para la etapa',
            session('flash_notifications')[0]['body']
        );

        $context = $this->crearContexto(2);
        [$user, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->actingAs($user);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $empleadoEliminado = $this->crearEmpleado();
        $firmaPosterior = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $empleadoEliminado);
        $empleadoEliminado->delete();

        $component = $this->componente($context['proyecto']);
        $component->subsanarComentario = 'Correcciones listas';
        $component->subsanar();

        $this->assertSame('Pendiente', $firmaPosterior->refresh()->estado_revision);
        $this->assertSame(0, $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2)->count());
        $this->assertStringContainsString(
            'No existe un empleado válido para reenviar la etapa',
            session('flash_notifications')[1]['body']
        );
    }

    public function test_usuario_no_autorizado_en_camino_nuevo_no_cae_al_legacy(): void
    {
        $context = $this->crearContexto();
        [$coordinadorUser, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        [$ajenoUser] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->actingAs($ajenoUser);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $coordinador, ['estado_revision' => 'Rechazado']);
        $estadoActualId = $context['proyecto']->estado->id;

        $component = $this->componente($context['proyecto']);
        $component->subsanarComentario = 'Correcciones listas';
        $component->subsanar();

        $this->assertSame($estadoActualId, $context['proyecto']->estado->id);
        $this->assertSame('Rechazado', $firma->refresh()->estado_revision);
        $this->assertSame(0, $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2)->count());
        $this->assertStringContainsString(
            'No tiene autorización para reenviar este registro desde subsanación.',
            session('flash_notifications')[0]['body']
        );
        $this->assertNotSame($coordinadorUser->id, auth()->id());
    }

    public function test_error_posterior_del_camino_nuevo_revierte_ciclo_estado_y_no_ejecuta_legacy(): void
    {
        $context = $this->crearContexto(2);
        [$user, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->actingAs($user);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $firmaRechazada = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $this->crearEmpleado(), ['estado_revision' => 'Rechazado']);
        $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][1], $this->crearEmpleado());
        $estadoActualId = $context['proyecto']->estado->id;
        $firmasAntes = FirmaProyecto::count();

        $component = $this->componente($context['proyecto'], HistorialProyectoIntegrationFallaEstadoComponent::class);
        $component->subsanarComentario = 'Correcciones listas';
        $component->subsanar();

        $this->assertSame($firmasAntes, FirmaProyecto::count());
        $this->assertSame($estadoActualId, $context['proyecto']->estado->id);
        $this->assertSame('Rechazado', $firmaRechazada->refresh()->estado_revision);
        $this->assertSame(0, $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2)->count());
        $this->assertStringContainsString('Fallo controlado al crear estado.', session('flash_notifications')[0]['body']);
    }

    public function test_proyecto_fuera_de_subsanacion_no_reenvia_por_etapa(): void
    {
        $context = $this->crearContexto();
        [$user, $coordinador] = $this->crearUsuarioEmpleado(permisos: ['docente.crear-proyecto']);
        $this->actingAs($user);
        $this->vincularCoordinador($context['proyecto'], $coordinador);
        $this->crearEstado($context['proyecto'], 'Subsanacion', $coordinador);
        $this->crearEstado($context['proyecto'], 'Revision posterior', $coordinador);
        $firma = $this->crearFirmaDeEtapa($context['proyecto'], $context['etapas'][0], $coordinador, ['estado_revision' => 'Rechazado']);
        $estadoActualId = $context['proyecto']->estado->id;

        $component = $this->componente($context['proyecto']);
        $component->subsanarComentario = 'Correcciones listas';
        $component->subsanar();

        $this->assertSame($estadoActualId, $context['proyecto']->estado->id);
        $this->assertSame('Rechazado', $firma->refresh()->estado_revision);
        $this->assertSame(0, $context['proyecto']->firmasDeEtapasDelFlujo($context['flujo']->id, 2)->count());
        $this->assertStringContainsString(
            'El registro no se encuentra en estado de Subsanación.',
            session('flash_notifications')[0]['body']
        );
    }

    private function componente(Proyecto $proyecto, string $class = HistorialProyecto::class): HistorialProyecto
    {
        $component = new $class;
        $component->proyecto = $proyecto;

        return $component;
    }

    private function crearContexto(int $cantidadEtapas = 1): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto integracion '.uniqid(),
            'codigo_proyecto' => 'INT-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_INT_'.uniqid(),
            'nombre' => 'Flujo integracion',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $estados = [];
        $cargos = [];
        $etapas = [];

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $estado = $this->crearTipoEstado('Estado integracion '.$orden.' '.uniqid());
            $cargo = $this->crearCargoFirma($estado->id, 'Cargo integracion '.$orden.' '.uniqid());
            $etapa = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'INT_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa integracion '.$orden,
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
        int $revisionCiclo = 1
    ): FirmaProyecto {
        return $proyecto->firma_proyecto()->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-int-'.uniqid(),
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

    private function crearFirmaLegacy(Proyecto $proyecto, CargoFirma $cargo, Empleado $empleado, array $attributes = []): FirmaProyecto
    {
        return $proyecto->firma_proyecto()->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-legacy-'.uniqid(),
        ], $attributes));
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
            'name' => 'Usuario integracion',
            'email' => 'integracion-'.uniqid().'@unah.test',
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

    private function crearEmpleado(?User $user = null): Empleado
    {
        $user = $user ?: User::create([
            'name' => 'Usuario empleado integracion',
            'email' => 'empleado-integracion-'.uniqid().'@unah.test',
        ]);

        return Empleado::create([
            'nombre_completo' => 'Empleado integracion',
            'numero_empleado' => 'INT-'.uniqid(),
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

    private function crearCargoFirma(?int $tipoEstadoId, string $nombreTipoCargo): CargoFirma
    {
        $tipoCargo = TipoCargoFirma::create([
            'nombre' => $nombreTipoCargo,
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

class HistorialProyectoIntegrationNoDebeReenviarComponent extends HistorialProyecto
{
    public bool $reenviarInvocado = false;

    protected function reenviarDesdeSubsanacionPorEtapa(
        FirmaProyecto $firmaRechazada,
        User $user,
        array $empleadosPorEtapa
    ): \Illuminate\Support\Collection {
        $this->reenviarInvocado = true;

        throw new \RuntimeException('No debió invocar el reenvío por etapa.');
    }
}

class HistorialProyectoIntegrationFallaEstadoComponent extends HistorialProyecto
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
