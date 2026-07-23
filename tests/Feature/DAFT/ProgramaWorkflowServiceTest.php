<?php

namespace Tests\Feature\DAFT;

use App\Clases\DataNavBar;
use App\Livewire\Configuracion\Flujos\ConfiguracionFlujosProyectos;
use App\Livewire\DAFT\Dashboard;
use App\Livewire\DAFT\Programas\ListBandejaRevision;
use App\Livewire\DAFT\Programas\ListTiposPrograma;
use App\Livewire\DAFT\Programas\ProgramaForm;
use App\Livewire\DAFT\Programas\ProgramaRevisionDetail;
use App\Livewire\Inicio\InicioAdmin;
use App\Models\Asignatura;
use App\Models\DAFT\ProgramaCertificacion;
use App\Models\DAFT\TipoPrograma;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\DAFT\ProgramaWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProgramaWorkflowServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_programa_recorre_el_mismo_ciclo_de_envio_aprobacion_y_cierre(): void
    {
        [$programa, $revisor] = $this->escenarioConDosEtapas();
        $service = app(ProgramaWorkflowService::class);

        $service->enviarARevision($programa, $revisor);
        $programa->refresh();

        $this->assertSame('EN_REVISION', $programa->estado_flujo);
        $this->assertSame(1, $programa->revision_ciclo);
        $this->assertCount(2, $programa->revisionesActuales());

        $service->aprobar($programa->etapaActual(), $revisor, 'Primera etapa aprobada');
        $programa->refresh();
        $this->assertSame('ETAPA_2', $programa->etapaActual()?->etapa_codigo);

        $service->aprobar($programa->etapaActual(), $revisor, 'Aprobación final');
        $programa->refresh();

        $this->assertSame('APROBADO', $programa->estado_flujo);
        $this->assertTrue((bool) $programa->versiones()->where('numero_version', 1)->value('vigente'));
    }

    public function test_subsanacion_crea_un_nuevo_ciclo_desde_la_etapa_rechazada(): void
    {
        [$programa, $revisor] = $this->escenarioConDosEtapas();
        $service = app(ProgramaWorkflowService::class);

        $service->enviarARevision($programa, $revisor);
        $programa->refresh();
        $service->aprobar($programa->etapaActual(), $revisor);
        $programa->refresh();
        $service->rechazar($programa->etapaActual(), $revisor, 'Debe corregir el contenido');
        $programa->refresh();

        $this->assertSame('SUBSANACION', $programa->estado_flujo);
        $this->assertSame(2, $programa->subsanacion_etapa_orden);

        $service->enviarARevision($programa, $revisor);
        $programa->refresh();

        $this->assertSame(2, $programa->revision_ciclo);
        $this->assertCount(1, $programa->revisionesActuales());
        $this->assertSame('ETAPA_2', $programa->etapaActual()?->etapa_codigo);
    }

    public function test_revision_asignada_solo_es_visible_para_el_usuario_asignado(): void
    {
        [$programa, $revisor] = $this->escenarioConDosEtapas();
        $otroRevisor = User::factory()->create(['active_role_id' => $revisor->active_role_id]);
        $otroRevisor->assignRole($revisor->activeRole);
        $service = app(ProgramaWorkflowService::class);

        $service->enviarARevision($programa, $revisor);
        $revision = $programa->fresh()->etapaActual();

        $this->assertSame('ASIGNADO', $revision?->estado);
        $this->assertTrue($service->usuarioPuedeVer($revision, $revisor));
        $this->assertFalse($service->usuarioPuedeVer($revision, $otroRevisor));

        $revisor->activeRole->givePermissionTo(Permission::findOrCreate('daft.acceso', 'web'));
        $this->actingAs($otroRevisor)
            ->get(route('daft.bandeja-revision.show', $revision))
            ->assertForbidden();
    }

    public function test_emisor_define_el_destinatario_de_una_etapa_daft(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $stage = $flujo->etapas()->orderBy('orden')->firstOrFail();
        $stage->update(['emisor_define_destinatario' => true]);
        $destinatario = User::factory()->create(['active_role_id' => $revisor->active_role_id]);
        $destinatario->assignRole($revisor->activeRole);
        $service = app(ProgramaWorkflowService::class);

        try {
            $service->enviarARevision($programa, $revisor);
            $this->fail('El flujo debía exigir el destinatario configurado por el emisor.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('Selecciona el destinatario', $exception->getMessage());
        }

        $this->assertSame('ELABORACION', $programa->fresh()->estado_flujo);
        $this->assertDatabaseMissing('programa_revisiones', [
            'programa_certificacion_id' => $programa->id,
        ]);

        $service->enviarARevision($programa->fresh(), $revisor, [$stage->id => $destinatario->id]);
        $revision = $programa->fresh()->etapaActual();

        $this->assertSame('ASIGNADO', $revision?->estado);
        $this->assertSame($destinatario->id, $revision?->asignado_usuario_id);
        $this->assertTrue($service->usuarioPuedeVer($revision, $destinatario));
        $this->assertFalse($service->usuarioPuedeVer($revision, $revisor));
    }

    public function test_formulario_daft_solicita_los_destinatarios_configurados_por_el_emisor(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $stages = $flujo->etapas()->orderBy('orden')->get();
        $stages->each->update(['emisor_define_destinatario' => true]);
        $programa->centrosPrograma()->create([
            'centro_facultad_id' => $programa->centro_facultad_id,
            'activo' => true,
        ]);
        $asignatura = Asignatura::create([
            'codigo' => 'MODAL-'.uniqid(),
            'nombre' => 'Asignatura para destinatarios',
            'creditos_academicos' => 3,
            'horas_academicas' => 40,
            'activa' => true,
        ]);
        $programa->asignaturasPrograma()->create([
            'asignatura_id' => $asignatura->id,
            'orden' => 1,
            'es_obligatoria' => true,
        ]);
        $destinatario = User::factory()->create(['active_role_id' => $revisor->active_role_id]);
        $destinatario->assignRole($revisor->activeRole);
        $this->actingAs($revisor);

        $component = Livewire::test(ProgramaForm::class, ['programa' => $programa->fresh()])
            ->call('openSendReviewModal')
            ->assertSet('showSendReviewModal', true)
            ->assertSee('Seleccionar destinatarios')
            ->assertSee($stages[0]->nombre)
            ->assertSee($stages[1]->nombre)
            ->assertSee($destinatario->email);

        foreach ($stages as $stage) {
            $component->set('reviewRecipients.'.$stage->id, $destinatario->id);
        }

        $component
            ->call('sendToReview')
            ->assertHasNoErrors()
            ->assertRedirect(route('daft.programas'));

        foreach ($stages as $stage) {
            $this->assertDatabaseHas('programa_revisiones', [
                'programa_certificacion_id' => $programa->id,
                'flujo_aprobacion_etapa_id' => $stage->id,
                'asignado_usuario_id' => $destinatario->id,
                'estado' => 'ASIGNADO',
            ]);
        }
    }

    public function test_dashboard_muestra_las_revisiones_disponibles_para_el_rol_activo(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $flujo->etapas()->update([
            'requiere_asignacion' => true,
            'usuario_responsable_id' => null,
        ]);

        app(ProgramaWorkflowService::class)->enviarARevision($programa->fresh(), $revisor);

        $this->actingAs($revisor);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSee('Panel de programas')
            ->assertSee('Revisiones pendientes')
            ->assertSee($programa->nombre)
            ->assertSee('Pendiente de asignación')
            ->assertSee('Sin responsable asignado');
    }

    public function test_menu_daft_muestra_la_notificacion_de_revisiones_visibles(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $revisor->activeRole->givePermissionTo(Permission::findOrCreate('daft.acceso', 'web'));
        $flujo->etapas()->update([
            'requiere_asignacion' => true,
            'usuario_responsable_id' => null,
        ]);
        $this->actingAs($revisor);

        $this->assertSame(0, DataNavBar::obtenerCantidadRevisionesDaft());

        app(ProgramaWorkflowService::class)->enviarARevision($programa, $revisor);

        $this->assertSame(1, DataNavBar::obtenerCantidadRevisionesDaft());

        app(ProgramaWorkflowService::class)->asignarAlUsuario($programa->fresh()->etapaActual(), $revisor);

        $this->assertSame(1, DataNavBar::obtenerCantidadRevisionesDaft());
    }

    public function test_usuario_del_rol_asigna_la_revision_a_otro_revisor_del_mismo_rol(): void
    {
        [$programa, $actor, $flujo] = $this->escenarioConDosEtapas();
        $flujo->etapas()->update([
            'requiere_asignacion' => true,
            'usuario_responsable_id' => null,
        ]);
        $destinatario = User::factory()->create(['active_role_id' => $actor->active_role_id]);
        $destinatario->assignRole($actor->activeRole);
        $usuarioSinRol = User::factory()->create();
        $usuarioInactivo = User::factory()->create(['active_role_id' => $actor->active_role_id]);
        $usuarioInactivo->assignRole($actor->activeRole);
        $usuarioInactivo->delete();
        $service = app(ProgramaWorkflowService::class);
        $service->enviarARevision($programa, $actor);
        $revision = $programa->fresh()->etapaActual();

        try {
            $service->asignarAUsuario($revision, $actor, $usuarioSinRol);
            $this->fail('No debía permitir asignar la revisión a un usuario sin el rol requerido.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('no pertenece al rol revisor', $exception->getMessage());
        }

        try {
            $service->asignarAUsuario($revision, $actor, $usuarioInactivo);
            $this->fail('No debía permitir asignar la revisión a un usuario inactivo.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('no está activo', $exception->getMessage());
        }

        $this->assertSame('PENDIENTE_ASIGNACION', $revision->fresh()->estado);
        $this->actingAs($actor);

        Livewire::test(ListBandejaRevision::class)
            ->assertSee('Seleccione responsable')
            ->assertSee($destinatario->email)
            ->set('reviewerSelections.'.$revision->id, $destinatario->id)
            ->call('assignReviewer', $revision->id)
            ->assertHasNoErrors();

        $revision->refresh();
        $this->assertSame('ASIGNADO', $revision->estado);
        $this->assertSame($destinatario->id, $revision->asignado_usuario_id);
        $this->assertFalse($service->usuarioPuedeVer($revision, $actor));
        $this->assertTrue($service->usuarioPuedeVer($revision, $destinatario));
    }

    public function test_usuario_con_rol_daft_es_dirigido_a_su_dashboard_desde_inicio(): void
    {
        [, $revisor] = $this->escenarioConDosEtapas();
        $revisor->activeRole->givePermissionTo(Permission::findOrCreate('daft.acceso', 'web'));

        $this->actingAs($revisor);

        Livewire::test(InicioAdmin::class)
            ->assertRedirect(route('daft.dashboard'));
    }

    public function test_formulario_muestra_el_flujo_configurado_y_su_actividad_real(): void
    {
        [$programa, $revisor] = $this->escenarioConDosEtapas();
        $service = app(ProgramaWorkflowService::class);
        $this->actingAs($revisor);

        Livewire::test(ProgramaForm::class, ['programa' => $programa])
            ->assertSee('Etapa 1/2')
            ->assertSee('Etapa 1')
            ->assertSee('Etapa 2')
            ->assertSee('Asignado a: '.$revisor->name);

        $service->enviarARevision($programa, $revisor);

        Livewire::test(ProgramaForm::class, ['programa' => $programa->fresh()])
            ->assertSee('Etapa 1/2')
            ->assertSee('Ciclo de revisión 1')
            ->assertSee('Enviado a revisión');

        $service->aprobar($programa->fresh()->etapaActual(), $revisor, 'Contenido validado');

        Livewire::test(ProgramaForm::class, ['programa' => $programa->fresh()])
            ->assertSee('Etapa 2/2')
            ->assertSee('Etapa aprobada')
            ->assertSee('Contenido validado');
    }

    public function test_configuracion_daft_permite_elegir_revision_o_aprobacion(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $this->actingAs($revisor);

        Livewire::test(ConfiguracionFlujosProyectos::class)
            ->call('showProgramFlows')
            ->call('selectProgramTipoPrograma', $programa->tipo_programa_id)
            ->assertSee('Tipo')
            ->assertSee('Revision')
            ->assertSee('Aprobacion')
            ->set('programStages.0.cargo_firma_id', $flujo->etapas()->firstOrFail()->cargo_firma_id)
            ->set('programStages.0.tipo_etapa', 'APROBACION')
            ->assertSet('programStages.0.tipo_etapa', 'APROBACION')
            ->assertSee('Cargo de firma')
            ->set('programStages.0.tipo_etapa', 'REVISION')
            ->set('programStages.1.tipo_etapa', 'REVISION')
            ->assertSet('programStages.0.tipo_etapa', 'REVISION')
            ->assertSet('programStages.0.cargo_firma_id', '')
            ->assertDontSee('Cargo de firma')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('flujos_aprobacion_etapas', [
            'id' => $flujo->etapas()->firstOrFail()->id,
            'tipo_etapa' => 'REVISION',
        ]);
    }

    public function test_revisor_abre_el_expediente_del_programa_antes_de_aprobar(): void
    {
        Storage::fake('public');
        [$programa, $revisor] = $this->escenarioConDosEtapas();
        $programa->centrosPrograma()->create([
            'centro_facultad_id' => $programa->centro_facultad_id,
            'activo' => true,
        ]);
        $documentPath = 'asignaturas/descripciones-minimas/revision.pdf';
        Storage::disk('public')->put($documentPath, '%PDF documento de prueba');
        $asignatura = Asignatura::create([
            'codigo' => 'REV-101',
            'nombre' => 'Asignatura para revisión documental',
            'creditos_academicos' => 4,
            'horas_academicas' => 60,
            'ruta_documento_descripcion_minima' => $documentPath,
            'activa' => true,
        ]);
        $programaAsignatura = $programa->asignaturasPrograma()->create([
            'asignatura_id' => $asignatura->id,
            'orden' => 1,
            'es_obligatoria' => true,
        ]);
        app(ProgramaWorkflowService::class)->enviarARevision($programa, $revisor);
        $revision = $programa->fresh()->etapaActual();
        $this->actingAs($revisor);

        Livewire::test(ProgramaRevisionDetail::class, ['revision' => $revision])
            ->assertSee('Expediente del programa')
            ->assertSee($programa->nombre)
            ->assertSee($programa->centroFacultad->nombre)
            ->assertSee('Asignatura para revisión documental')
            ->assertSee('Aceptar y firmar etapa')
            ->call('downloadDocument', $programaAsignatura->id)
            ->assertFileDownloaded('rev-101-asignatura-para-revision-documental.pdf')
            ->call('approveRevision')
            ->assertRedirect(route('daft.bandeja-revision'));

        $this->assertSame('ETAPA_2', $programa->fresh()->etapaActual()?->etapa_codigo);
    }

    public function test_descarga_la_plantilla_del_tipo_de_programa(): void
    {
        Storage::fake('public');
        [$programa, $revisor] = $this->escenarioConDosEtapas();
        $path = 'daft/plantillas/formato-prueba.docx';
        $programa->tipoPrograma->update(['plantilla_docx_path' => $path]);
        Storage::disk('public')->put($path, 'contenido docx de prueba');
        $this->actingAs($revisor);

        Livewire::test(ProgramaForm::class, ['programa' => $programa->fresh()])
            ->call('downloadTemplate')
            ->assertFileDownloaded('Formato-'.Str::slug($programa->tipoPrograma->nombre).'.docx');
    }

    public function test_tipo_de_programa_nuevo_aparece_inmediatamente_en_la_lista(): void
    {
        Storage::fake('public');
        $nombre = 'Tipo inmediato '.uniqid();
        $cantidadInicial = TipoPrograma::count();

        Livewire::test(ListTiposPrograma::class)
            ->set('tipoPrograma.nombre', $nombre)
            ->set('tipoPrograma.horas_minimas', 10)
            ->set('tipoPrograma.horas_maximas', 120)
            ->set('plantillaDocumento', UploadedFile::fake()->create(
                'plantilla.docx',
                20,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ))
            ->call('saveTipoPrograma')
            ->assertHasNoErrors()
            ->assertSee($nombre)
            ->assertViewHas('tiposPrograma', fn ($tipos) => $tipos->count() === $cantidadInicial + 1
                && $tipos->contains('nombre', $nombre));

        $this->assertDatabaseHas('tipos_programa', [
            'nombre' => $nombre,
            'horas_minimas' => 10,
            'horas_maximas' => 120,
            'activo' => true,
        ]);
    }

    public function test_tomar_revision_actualiza_la_pestana_y_una_accion_repetida_no_rompe_la_bandeja(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $flujo->etapas()->update([
            'requiere_asignacion' => true,
            'usuario_responsable_id' => null,
        ]);
        app(ProgramaWorkflowService::class)->enviarARevision($programa, $revisor);
        $revision = $programa->fresh()->etapaActual();
        $this->actingAs($revisor);

        $component = Livewire::test(ListBandejaRevision::class)
            ->assertSee(route('daft.bandeja-revision.show', $revision), false)
            ->call('assignToMe', $revision->id)
            ->assertDispatched('daft-review-assigned');

        $this->assertDatabaseHas('programa_revisiones', [
            'id' => $revision->id,
            'estado' => 'ASIGNADO',
            'asignado_usuario_id' => $revisor->id,
        ]);

        $component
            ->call('assignToMe', $revision->id)
            ->assertSee('La revisión ya fue tomada o resuelta. La bandeja fue actualizada.');
    }

    public function test_etapa_que_requiere_asignacion_no_puede_procesarse_antes_de_asignarla(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $flujo->etapas()->firstOrFail()->update([
            'requiere_asignacion' => true,
            'usuario_responsable_id' => null,
        ]);
        $service = app(ProgramaWorkflowService::class);
        $service->enviarARevision($programa, $revisor);
        $revision = $programa->fresh()->etapaActual();

        $this->assertSame('PENDIENTE_ASIGNACION', $revision?->estado);
        $this->assertNull($revision?->asignado_usuario_id);
        $this->assertFalse($service->usuarioPuedeActuar($revision, $revisor));

        foreach (['aprobar', 'rechazar'] as $accion) {
            try {
                $accion === 'aprobar'
                    ? $service->aprobar($revision->fresh(), $revisor)
                    : $service->rechazar($revision->fresh(), $revisor, 'Requiere correcciones');
                $this->fail("No debía permitir {$accion} una etapa pendiente de asignación.");
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }

        $this->assertSame('PENDIENTE_ASIGNACION', $revision->fresh()->estado);
    }

    public function test_usuario_sin_autorizacion_no_puede_asignar_responsable(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $flujo->etapas()->firstOrFail()->update(['requiere_asignacion' => true]);
        $rolAjeno = Role::findOrCreate('DAFT Rol ajeno '.uniqid(), 'web');
        $usuarioNoAutorizado = User::factory()->create(['active_role_id' => $rolAjeno->id]);
        $usuarioNoAutorizado->assignRole($rolAjeno);
        $service = app(ProgramaWorkflowService::class);
        $service->enviarARevision($programa, $revisor);
        $revision = $programa->fresh()->etapaActual();

        try {
            $service->asignarAUsuario($revision, $usuarioNoAutorizado, $revisor);
            $this->fail('Un usuario sin el rol de la etapa no debía poder asignar al responsable.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('PENDIENTE_ASIGNACION', $revision->fresh()->estado);
    }

    public function test_solo_responsable_asignado_puede_procesar_etapa_y_admin_conserva_acceso(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $flujo->etapas()->firstOrFail()->update(['requiere_asignacion' => true]);
        $otroRevisor = User::factory()->create(['active_role_id' => $revisor->active_role_id]);
        $otroRevisor->assignRole($revisor->activeRole);
        $adminRole = Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['active_role_id' => $adminRole->id]);
        $admin->assignRole($adminRole);
        $service = app(ProgramaWorkflowService::class);
        $service->enviarARevision($programa, $revisor);
        $revision = $programa->fresh()->etapaActual();
        $service->asignarAUsuario($revision, $admin, $otroRevisor);
        $revision->refresh();

        $this->assertFalse($service->usuarioPuedeActuar($revision, $revisor));
        $this->assertTrue($service->usuarioPuedeActuar($revision, $otroRevisor));
        $this->assertTrue($service->usuarioPuedeActuar($revision, $admin));

        try {
            $service->aprobar($revision, $revisor);
            $this->fail('Un revisor distinto al responsable asignado no debía poder aprobar.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $service->aprobar($revision->fresh(), $admin, 'Validado por administración');
        $this->assertSame('ETAPA_2', $programa->fresh()->etapaActual()?->etapa_codigo);
    }

    public function test_subsanacion_conserva_responsable_anterior_si_sigue_siendo_elegible(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $flujo->etapas()->firstOrFail()->update(['requiere_asignacion' => true]);
        $service = app(ProgramaWorkflowService::class);
        $service->enviarARevision($programa, $revisor);
        $revision = $programa->fresh()->etapaActual();
        $service->asignarAUsuario($revision, $revisor, $revisor);
        $service->rechazar($revision->fresh(), $revisor, 'Debe corregir la documentación');

        $service->enviarARevision($programa->fresh(), $revisor);
        $revisionSubsanada = $programa->fresh()->etapaActual();

        $this->assertSame(2, $revisionSubsanada?->revision_ciclo);
        $this->assertSame('ASIGNADO', $revisionSubsanada?->estado);
        $this->assertSame($revisor->id, $revisionSubsanada?->asignado_usuario_id);
    }

    public function test_subsanacion_vuelve_a_pendiente_si_responsable_anterior_ya_no_es_elegible(): void
    {
        [$programa, $revisor, $flujo] = $this->escenarioConDosEtapas();
        $etapa = $flujo->etapas()->firstOrFail();
        $etapa->update(['requiere_asignacion' => true]);
        $service = app(ProgramaWorkflowService::class);
        $service->enviarARevision($programa, $revisor);
        $revision = $programa->fresh()->etapaActual();
        $service->asignarAUsuario($revision, $revisor, $revisor);
        $service->rechazar($revision->fresh(), $revisor, 'Debe corregir la documentación');
        $revisor->removeRole($etapa->rolRevisor);

        $service->enviarARevision($programa->fresh(), $revisor);
        $revisionSubsanada = $programa->fresh()->etapaActual();

        $this->assertSame('PENDIENTE_ASIGNACION', $revisionSubsanada?->estado);
        $this->assertNull($revisionSubsanada?->asignado_usuario_id);
    }

    public function test_edita_una_asignatura_del_programa_y_reemplaza_su_documento(): void
    {
        Storage::fake('public');
        [$programa, $revisor] = $this->escenarioConDosEtapas();
        $asignatura = Asignatura::create([
            'codigo' => 'ASG-100',
            'nombre' => 'Asignatura original',
            'creditos_academicos' => 3,
            'horas_academicas' => 40,
            'ruta_documento_descripcion_minima' => 'asignaturas/descripciones-minimas/anterior.pdf',
            'activa' => true,
        ]);
        $programa->asignaturasPrograma()->create([
            'asignatura_id' => $asignatura->id,
            'orden' => 1,
            'es_obligatoria' => true,
        ]);
        $this->actingAs($revisor);

        Livewire::test(ProgramaForm::class, ['programa' => $programa->fresh()])
            ->call('openEditAsignaturaModal', $asignatura->id)
            ->assertSee('Editar asignatura')
            ->set('editingAsignatura.codigo', 'ASG-101')
            ->set('editingAsignatura.nombre', 'Asignatura actualizada')
            ->set('editingAsignatura.creditos_academicos', '4.00')
            ->set('editingAsignatura.horas_academicas', 55)
            ->set('editingAsignaturaDocumento', UploadedFile::fake()->create('descripcion.pdf', 20, 'application/pdf'))
            ->call('updateAsignatura')
            ->assertHasNoErrors()
            ->assertSet('showEditAsignaturaModal', false)
            ->assertSee('Asignatura actualizada');

        $asignatura->refresh();
        $this->assertSame('ASG-101', $asignatura->codigo);
        $this->assertSame(55, $asignatura->horas_academicas);
        $this->assertSame(55, (int) $programa->fresh()->horas_maximas_programa);
        Storage::disk('public')->assertExists($asignatura->ruta_documento_descripcion_minima);
    }

    public function test_configura_requisitos_solo_con_asignaturas_del_mismo_programa(): void
    {
        [$programa, $revisor] = $this->escenarioConDosEtapas();
        $principal = Asignatura::create([
            'codigo' => 'ASG-200',
            'nombre' => 'Asignatura principal',
            'creditos_academicos' => 4,
            'horas_academicas' => 60,
            'activa' => true,
        ]);
        $requisito = Asignatura::create([
            'codigo' => 'ASG-100',
            'nombre' => 'Asignatura requisito',
            'creditos_academicos' => 3,
            'horas_academicas' => 45,
            'activa' => true,
        ]);
        $externa = Asignatura::create([
            'codigo' => 'EXT-100',
            'nombre' => 'Asignatura externa',
            'creditos_academicos' => 2,
            'horas_academicas' => 30,
            'activa' => true,
        ]);
        foreach ([$principal, $requisito] as $index => $asignatura) {
            $programa->asignaturasPrograma()->create([
                'asignatura_id' => $asignatura->id,
                'orden' => $index + 1,
                'es_obligatoria' => true,
            ]);
        }
        $this->actingAs($revisor);

        $component = Livewire::test(ProgramaForm::class, ['programa' => $programa->fresh()])
            ->call('openPrerequisitosModal', $principal->id)
            ->assertSee('Requisitos de la asignatura')
            ->assertSee('ASG-100')
            ->set('selectedPrerequisiteIds', [$externa->id])
            ->call('savePrerequisitos')
            ->assertHasErrors('selectedPrerequisiteIds');

        $this->assertDatabaseMissing('asignatura_prerrequisitos', [
            'asignatura_id' => $principal->id,
            'prerrequisito_asignatura_id' => $externa->id,
        ]);

        $component
            ->set('selectedPrerequisiteIds', [$requisito->id])
            ->call('savePrerequisitos')
            ->assertHasNoErrors()
            ->assertSet('showPrerequisitosModal', false)
            ->assertSee('ASG-100');

        $this->assertDatabaseHas('asignatura_prerrequisitos', [
            'asignatura_id' => $principal->id,
            'prerrequisito_asignatura_id' => $requisito->id,
        ]);
    }

    private function escenarioConDosEtapas(): array
    {
        $role = Role::findOrCreate('DAFT Revisor '.uniqid(), 'web');
        $revisor = User::factory()->create(['active_role_id' => $role->id]);
        $revisor->assignRole($role);
        $tipo = TipoPrograma::create(['nombre' => 'Tipo '.uniqid(), 'activo' => true]);
        $cargo = $this->cargo();
        $flujo = FlujoAprobacion::create([
            'codigo' => 'DAFT_TEST_'.uniqid(),
            'nombre' => 'Flujo DAFT de prueba',
            'proceso' => 'PROGRAMA',
            'tipo_programa_id' => $tipo->id,
            'activo' => true,
        ]);

        foreach ([1, 2] as $orden) {
            $flujo->etapas()->create([
                'orden' => $orden,
                'codigo' => 'ETAPA_'.$orden,
                'nombre' => 'Etapa '.$orden,
                'tipo_etapa' => $orden === 2 ? 'APROBACION' : 'REVISION',
                'rol_revisor_id' => $role->id,
                'usuario_responsable_id' => $revisor->id,
                'cargo_firma_id' => $cargo->id,
                'requiere_asignacion' => false,
                'activo' => true,
            ]);
        }

        $programa = ProgramaCertificacion::create([
            'centro_facultad_id' => $this->centro()->id,
            'codigo' => 'PROG-'.uniqid(),
            'nombre' => 'Programa de prueba',
            'tipo_programa_id' => $tipo->id,
            'estado_flujo' => 'ELABORACION',
            'revision_ciclo' => 0,
            'creado_por_usuario_id' => $revisor->id,
        ]);

        return [$programa, $revisor, $flujo];
    }

    private function cargo(): CargoFirma
    {
        $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado DAFT test']);
        $tipo = TipoCargoFirma::firstOrCreate(['nombre' => 'Revisor DAFT test']);

        return CargoFirma::create([
            'descripcion' => 'Cargo DAFT test',
            'tipo_cargo_firma_id' => $tipo->id,
            'tipo_estado_id' => $estado->id,
            'estado_siguiente_id' => $estado->id,
        ]);
    }

    private function centro(): FacultadCentro
    {
        $campus = Campus::create([
            'nombre_campus' => 'Campus '.uniqid(),
            'siglas' => 'CMP',
            'direccion' => 'Dirección',
            'telefono' => '0000-0000',
            'url' => 'https://unah.test',
        ]);

        return FacultadCentro::create([
            'nombre' => 'Centro '.uniqid(),
            'es_facultad' => true,
            'siglas' => 'FC'.random_int(100, 999),
            'campus_id' => $campus->id,
        ]);
    }
}
