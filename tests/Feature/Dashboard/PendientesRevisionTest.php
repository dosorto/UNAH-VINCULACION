<?php

namespace Tests\Feature\Dashboard;

use App\Clases\DataNavBar;
use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfRevision;
use App\Models\Estado\TipoEstado;
use App\Models\Pasantia;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\Dashboard\PendientesRevisionService;
use App\Services\ENF\EnfWorkflowService;
use App\Support\Dashboard\EstadosProyecto;
use App\Support\Proyecto\EstadoGeneralProyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "Esperan tu revisión" del panel, la bandeja de tareas y el contador de la
 * barra deben contar lo mismo.
 *
 * El caso que lo motivó: el informe final de un proyecto FORM-DVUS-015 llegaba
 * a la bandeja y al contador, pero el panel solo miraba las firmas del
 * proyecto y lo daba por inexistente. Las pasantías no aparecían en ninguno de
 * los tres, y ENF tenía tres consultas distintas.
 */
class PendientesRevisionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);
    }

    public function test_el_informe_pendiente_aparece_en_el_panel_igual_que_en_la_bandeja(): void
    {
        [$user, $empleado, $rol] = $this->revisor('Revisor informe');
        [$proyecto, $flujo, $etapas] = $this->proyectoConFlujo(['Revisión del informe']);
        $documento = $this->documento($proyecto, 'Informe Final', $etapas[0]->cargoFirma->tipo_estado_id);
        $firma = $this->firmaEtapa($documento->firma_documento(), $etapas[0], $empleado, $rol->name);
        $this->actingAs($user);

        $pendientes = app(PendientesRevisionService::class)->paraRolActivo($user);

        $this->assertCount(1, $pendientes);
        $this->assertSame('Informe Final', $pendientes->first()->tipo);
        $this->assertSame($proyecto->nombre_proyecto, $pendientes->first()->nombre);
        $this->assertSame('Revisión del informe', $pendientes->first()->etapa);
        $this->assertSame(1, DataNavBar::obtenerCantidadProyectosPorFirmar());
        $this->assertSame(['Informe Final' => 1], app(PendientesRevisionService::class)->porTipo($user));
        $this->assertSame('Pendiente', $firma->fresh()->estado_revision);
    }

    public function test_la_inscripcion_en_revision_muestra_su_etapa_y_la_espera_desde_la_aprobacion_anterior(): void
    {
        [$user, $empleado, $rol] = $this->revisor('Revisor segunda etapa');
        [$proyecto, , $etapas] = $this->proyectoConFlujo(['Primera revisión', 'Segunda revisión']);
        $this->ponerEstado($proyecto, EstadoGeneralProyecto::id('En revision'));
        $primera = $this->firmaEtapa($proyecto->firma_proyecto(), $etapas[0], $this->empleado(), $rol->name, [
            'estado_revision' => 'Aprobado',
            'fecha_firma' => now()->subDays(3),
        ]);
        $segunda = $this->firmaEtapa($proyecto->firma_proyecto(), $etapas[1], $empleado, $rol->name);
        // Todas las firmas del recorrido se crean al enviar.
        foreach ([$primera, $segunda] as $firma) {
            $firma->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();
        }
        $this->actingAs($user);

        $pendiente = app(PendientesRevisionService::class)->paraRolActivo($user)->sole();

        $this->assertSame('Proyecto', $pendiente->tipo);
        // Antes salía vacía: se leía el estado, que ahora es "En revision".
        $this->assertSame('Segunda revisión', $pendiente->etapa);
        // Antes contaba desde el envío (10 días), no desde que le llegó.
        $this->assertSame(3, $pendiente->dias_espera);
    }

    public function test_la_pasantia_en_revision_aparece_en_la_bandeja_el_contador_y_el_panel(): void
    {
        [$user, $empleado, $rol] = $this->revisor('Coordinador pasantias');
        [$otroUsuario] = $this->revisor('Coordinador pasantias ajeno', $rol);
        $pasantia = $this->pasantiaEnRevision($empleado, $rol->name);
        $this->actingAs($user);

        $this->assertSame([$pasantia->id], Pasantia::pendientesParaUsuario($user)->pluck('id')->all());
        $this->assertSame(1, DataNavBar::obtenerCantidadPasantiasPorRevisar());
        $this->assertSame(1, DataNavBar::obtenerCantidadProyectosPorFirmar());

        $item = app(PendientesRevisionService::class)->paraRolActivo($user)->sole();
        $this->assertSame('Pasantía', $item->tipo);
        $this->assertSame($pasantia->nombre_estudiante, $item->nombre);
        $this->assertSame(route('pasantias.show', $pasantia->id), $item->href);

        Livewire::actingAs($user)
            ->test(ProyectosPorFirmar::class, ['docente' => $empleado])
            ->assertSee($pasantia->nombre_estudiante)
            ->assertSee(route('pasantias.show', $pasantia->id));

        // La firma es de otro empleado: aunque comparta rol, no puede aprobarla.
        $this->assertSame(0, Pasantia::pendientesParaUsuario($otroUsuario)->count());
    }

    public function test_la_pasantia_aprobada_o_devuelta_deja_de_estar_pendiente(): void
    {
        [$user, $empleado, $rol] = $this->revisor('Coordinador pasantias cierre');
        $pasantia = $this->pasantiaEnRevision($empleado, $rol->name);

        $pasantia->historialEstados()->update(['es_actual' => false]);
        $this->ponerEstado($pasantia, $this->tipoEstado('Rechazado')->id, Pasantia::class);

        $this->assertSame(0, Pasantia::pendientesParaUsuario($user)->count());
    }

    public function test_enf_cuenta_lo_mismo_en_la_bandeja_el_contador_y_el_panel(): void
    {
        [$user, , $rol] = $this->revisor('Revisor ENF');
        // Un informe final se revisa con la acción ya aprobada: el contador de
        // la barra exigía EN_REVISION y lo dejaba fuera.
        $informe = $this->accionEnf('FORM-DVUS-016', 'APROBADO');
        $revisionInforme = $this->revisionEnf($informe, EnfAccion::PROCESO_INFORME_FINAL, 1, $rol->name);
        // Un formulario ENF distinto de 016/018: el panel lo dejaba fuera.
        $otroFormulario = $this->accionEnf('FORM-DVUS-017', 'EN_REVISION');
        $revisionOtro = $this->revisionEnf($otroFormulario, EnfAccion::PROCESO_INSCRIPCION, 1, $rol->name);
        $this->actingAs($user);

        $ids = EnfRevision::pendientesParaUsuario($user)->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$revisionInforme->id, $revisionOtro->id])->sort()->values()->all(), $ids);
        $this->assertSame(2, DataNavBar::obtenerCantidadEnfPorRevisar());
        $this->assertSame(['ENF' => 2], app(PendientesRevisionService::class)->porTipo($user));
        // ENF se revisa en el modal de la bandeja: el enlace lleva a ella.
        $this->assertSame(
            [route('SolicitudProyectosDocente')],
            app(PendientesRevisionService::class)->paraRolActivo($user)->pluck('href')->unique()->values()->all()
        );
        $enfDeLaBandeja = Livewire::actingAs($user)
            ->test(ProyectosPorFirmar::class)
            ->viewData('enfRevisiones')
            ->pluck('id')->sort()->values()->all();
        $this->assertSame($ids, $enfDeLaBandeja);
    }

    public function test_enf_devuelta_a_subsanacion_no_habilita_la_etapa_siguiente(): void
    {
        [$user, , $rol] = $this->revisor('Revisor ENF subsanacion');
        $accion = $this->accionEnf('FORM-DVUS-018', 'SUBSANACION');
        $this->revisionEnf($accion, EnfAccion::PROCESO_INSCRIPCION, 1, 'Otro rol', 'SUBSANACION');
        $siguiente = $this->revisionEnf($accion, EnfAccion::PROCESO_INSCRIPCION, 2, $rol->name);

        $this->assertSame(0, EnfRevision::pendientesParaUsuario($user)->count());
        $this->assertFalse(app(EnfWorkflowService::class)->puedeRevisar($siguiente, $user));

        // El reenvío abre un ciclo nuevo y la primera etapa vuelve a esperar.
        $reenvio = $this->revisionEnf($accion, EnfAccion::PROCESO_INSCRIPCION, 1, $rol->name, ciclo: 2);

        $this->assertSame([$reenvio->id], EnfRevision::pendientesParaUsuario($user)->pluck('id')->all());
        $this->assertTrue(app(EnfWorkflowService::class)->puedeRevisar($reenvio, $user));
    }

    public function test_enf_respeta_la_asignacion_antes_que_el_rol(): void
    {
        [$user, , $rol] = $this->revisor('Revisor ENF asignacion');
        [$asignado] = $this->revisor('Revisor ENF asignado', $rol);
        $accion = $this->accionEnf('FORM-DVUS-018', 'EN_REVISION');
        $revision = $this->revisionEnf($accion, EnfAccion::PROCESO_INSCRIPCION, 1, $rol->name, 'ASIGNADO', [
            'asignado_usuario_id' => $asignado->id,
            'responsable_usuario_id' => $user->id,
        ]);

        $this->assertSame(0, EnfRevision::pendientesParaUsuario($user)->count());
        $this->assertFalse(app(EnfWorkflowService::class)->puedeRevisar($revision, $user));
        $this->assertSame([$revision->id], EnfRevision::pendientesParaUsuario($asignado)->pluck('id')->all());
    }

    // ── Escenarios ──────────────────────────────────────────────────────────

    /** @return array{0:Proyecto,1:FlujoAprobacion,2:list<FlujoAprobacionEtapa>} */
    private function proyectoConFlujo(array $nombresEtapas, string $proceso = Proyecto::FLUJO_INSCRIPCION): array
    {
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto pendientes '.uniqid(),
            'codigo_proyecto' => 'PEN-'.uniqid(),
        ]);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_PEN_'.uniqid(),
            'nombre' => 'Flujo pendientes',
            'proceso' => $proceso,
            'activo' => true,
        ]);
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->saveQuietly();

        $etapas = [];
        foreach (array_values($nombresEtapas) as $i => $nombre) {
            $etapas[] = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $i + 1,
                'codigo' => 'PEN_'.($i + 1).'_'.uniqid(),
                'nombre' => $nombre,
                'cargo_firma_id' => $this->cargoFirma('Cargo '.$nombre)->id,
                'activo' => true,
            ]);
        }

        return [$proyecto, $flujo, $etapas];
    }

    private function firmaEtapa($relacion, FlujoAprobacionEtapa $etapa, Empleado $empleado, string $rol, array $atributos = []): FirmaProyecto
    {
        return $relacion->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-pendientes',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'rol_requerido' => $rol,
            'revision_ciclo' => 1,
        ], $atributos));
    }

    private function documento(Proyecto $proyecto, string $tipo, int $tipoEstadoId): DocumentoProyecto
    {
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => $tipo,
            'documento_url' => 'documentos/'.uniqid().'.pdf',
        ]);
        $this->ponerEstado($documento, $tipoEstadoId, DocumentoProyecto::class);

        return $documento;
    }

    private function pasantiaEnRevision(Empleado $revisor, string $rol): Pasantia
    {
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_PAS_'.uniqid(),
            'nombre' => 'Flujo pasantías de prueba',
            'proceso' => Pasantia::PROCESO_FLUJO,
            'activo' => true,
        ]);
        $cargo = $this->cargoFirma('Cargo pasantía '.uniqid());
        $etapa = FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => 1,
            'codigo' => 'PAS_'.uniqid(),
            'nombre' => 'Revisión de pasantía',
            'cargo_firma_id' => $cargo->id,
            'activo' => true,
        ]);
        $pasantia = Pasantia::create([
            'codigo_registro' => 'PAS-'.uniqid(),
            'proceso' => Pasantia::PROCESO_FLUJO,
            'estado' => 'en_revision',
            'nombre_estudiante' => 'Estudiante en pasantía '.uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
            'etapa_actual_id' => $etapa->id,
            'fecha_envio' => now()->subDays(2),
        ]);
        $this->ponerEstado($pasantia, $cargo->tipo_estado_id, Pasantia::class);
        $this->firmaEtapa($pasantia->firmasDeEtapa(), $etapa, $revisor, $rol);

        return $pasantia;
    }

    private function accionEnf(string $codigo, string $estadoFlujo): EnfAccion
    {
        return EnfAccion::create([
            'codigo_formulario' => $codigo,
            'nombre_accion' => 'Acción ENF '.uniqid(),
            'estado_flujo' => $estadoFlujo,
            'revision_ciclo' => 1,
            'creado_por_usuario_id' => User::factory()->create()->id,
        ]);
    }

    private function revisionEnf(
        EnfAccion $accion,
        string $proceso,
        int $orden,
        string $rol,
        string $estado = 'PENDIENTE',
        array $atributos = [],
        int $ciclo = 1
    ): EnfRevision {
        return $accion->revisiones()->create(array_merge([
            'proceso' => $proceso,
            'revision_ciclo' => $ciclo,
            'orden' => $orden,
            'etapa_codigo' => 'ENF_'.$orden,
            'etapa_nombre' => 'Etapa ENF '.$orden,
            'rol_requerido' => $rol,
            'estado' => $estado,
        ], $atributos));
    }

    private function ponerEstado($modelo, int $tipoEstadoId, string $tipo = Proyecto::class): void
    {
        \App\Models\Estado\EstadoProyecto::create([
            'estadoable_type' => $tipo,
            'estadoable_id' => $modelo->id,
            'empleado_id' => $this->empleado()->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'es_actual' => true,
        ]);
    }

    /** @return array{0:User,1:Empleado,2:Role} */
    private function revisor(string $nombreRol, ?Role $rol = null): array
    {
        $rol ??= Role::create(['name' => $nombreRol.' '.uniqid(), 'guard_name' => 'web']);
        $user = User::create(['name' => 'Revisor', 'email' => 'revisor-'.uniqid().'@unah.test']);
        $empleado = $this->empleado($user);
        $user->assignRole($rol);
        $user->forceFill(['active_role_id' => $rol->id])->save();

        return [$user->fresh(), $empleado, $rol];
    }

    private function empleado(?User $user = null): Empleado
    {
        $user ??= User::create(['name' => 'Empleado', 'email' => 'empleado-'.uniqid().'@unah.test']);

        return Empleado::create([
            'nombre_completo' => 'Empleado '.$user->id,
            'numero_empleado' => 'PEN-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);
    }

    private function tipoEstado(string $nombre): TipoEstado
    {
        return TipoEstado::firstOrCreate(['nombre' => $nombre]);
    }

    private function cargoFirma(string $nombre): CargoFirma
    {
        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => TipoCargoFirma::create(['nombre' => $nombre])->id,
            'tipo_estado_id' => TipoEstado::create(['nombre' => 'Estado '.$nombre])->id,
        ]);
    }
}
