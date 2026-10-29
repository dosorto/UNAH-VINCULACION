<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\HistorialProyecto;
use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Mail\EtapaFlujoPendiente;
use App\Mail\ProyectoCreado;
use App\Mail\ProyectoEstadoCambiado;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\Proyecto\DocumentoProyectoWorkflowService;
use App\Support\Proyecto\EstadoGeneralProyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Correos del FORM-DVUS-001 en cada etapa: inscripción (5 etapas) e
 * Informe Final (etapas 2 a 5), con la configuración de roles del flujo real.
 */
class FormDvus001CorreosTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, User> revisor por orden de etapa */
    private array $revisores = [];
    private User $coordinador;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_cada_etapa_de_la_inscripcion_lleva_al_revisor_a_la_bandeja_y_avisa_al_coordinador(): void
    {
        $proyecto = $this->contexto();
        $this->enviar($proyecto);

        foreach ([1, 2, 3, 4, 5] as $orden) {
            // Revisor y Director de Vinculación también tienen «Revisión de
            // Informes», que no lista proyectos: el enlace debe ir a la bandeja.
            Mail::assertQueued(EtapaFlujoPendiente::class, fn (EtapaFlujoPendiente $mail): bool => $mail->hasTo($this->revisores[$orden]->email)
                && $mail->tipoRegistro === 'proyecto'
                && $mail->actionUrl === route('SolicitudProyectosDocente'));

            $this->aprobar($this->firmaActual($proyecto), $this->revisores[$orden]);
        }

        Mail::assertQueued(ProyectoEstadoCambiado::class, 4 + 1);
        Mail::assertQueued(ProyectoEstadoCambiado::class, fn (ProyectoEstadoCambiado $mail): bool => $mail->hasTo($this->coordinador->email)
            && $mail->nuevoEstado === 'Flujo de inscripción aprobado'
            && $mail->tipoAviso === 'cierre'
            && str_contains($mail->render(), 'REVISIÓN COMPLETADA'));
    }

    public function test_la_subsanacion_se_presenta_como_tal_y_el_reenvio_confirma_al_coordinador(): void
    {
        $proyecto = $this->contexto();
        $this->enviar($proyecto);
        $this->aprobar($this->firmaActual($proyecto), $this->revisores[1]);
        $this->rechazar($this->firmaActual($proyecto), $this->revisores[2], 'Corregir el presupuesto.');

        Mail::assertQueued(ProyectoEstadoCambiado::class, function (ProyectoEstadoCambiado $mail): bool {
            if ($mail->nuevoEstado !== 'Proyecto en subsanación') {
                return false;
            }
            $html = $mail->render();

            return $mail->tipoAviso === 'subsanacion'
                && str_contains($html, 'SUBSANACIÓN REQUERIDA')
                && str_contains($html, 'Corregir el presupuesto.')
                && ! str_contains($html, 'ESTADO ACTUALIZADO');
        });

        $this->actingAs($this->coordinador->fresh());
        $historial = new HistorialProyecto;
        $rechazada = (new \ReflectionMethod($historial, 'firmaRechazadaActualPorEtapa'))->invoke($historial, $proyecto->fresh());
        $empleados = (new \ReflectionMethod($historial, 'empleadosPorEtapaParaReenvio'))->invoke($historial, $rechazada);
        (new \ReflectionMethod($historial, 'reenviarDesdeSubsanacionPorEtapa'))->invoke($historial, $rechazada, $this->coordinador->fresh(), $empleados);

        Mail::assertQueued(EtapaFlujoPendiente::class, fn (EtapaFlujoPendiente $mail): bool => $mail->hasTo($this->revisores[2]->email)
            && $mail->etapa->orden === 2
            && $mail->actionUrl === route('SolicitudProyectosDocente'));
        Mail::assertQueued(ProyectoEstadoCambiado::class, fn (ProyectoEstadoCambiado $mail): bool => $mail->hasTo($this->coordinador->email)
            && $mail->nuevoEstado === 'Proyecto reenviado a revisión'
            && str_contains($mail->comentario, '"Jefe Departamento"'));
    }

    public function test_el_informe_final_avisa_envio_subsanacion_reenvio_y_cierre(): void
    {
        $proyecto = $this->contexto();
        $this->enviar($proyecto);
        foreach ([1, 2, 3, 4, 5] as $orden) {
            $this->aprobar($this->firmaActual($proyecto), $this->revisores[$orden]);
        }

        $this->actingAs($this->coordinador->fresh());
        $documentos = app(DocumentoProyectoWorkflowService::class);
        $informe = $documentos->iniciar($proyecto->fresh(), 'Informe Final', 'documentos/informes-finales/inf-001.pdf', $this->coordinador->fresh());

        // El cierre empieza en la etapa 2: la 1 solo aplica a la inscripción.
        Mail::assertQueued(EtapaFlujoPendiente::class, fn (EtapaFlujoPendiente $mail): bool => $mail->hasTo($this->revisores[2]->email)
            && $mail->tipoRegistro === 'Informe Final'
            && str_contains($mail->render(), 'tiene un Informe Final pendiente'));
        Mail::assertQueued(ProyectoEstadoCambiado::class, fn (ProyectoEstadoCambiado $mail): bool => $mail->nuevoEstado === 'Informe Final enviado a revisión'
            && $mail->tipoDocumento === 'Informe Final'
            && str_contains($mail->envelope()->subject, 'Informe Final'));

        $this->rechazar($this->firmaActualDocumento($proyecto, $informe), $this->revisores[2], 'Ajustar los indicadores.');
        Mail::assertQueued(ProyectoEstadoCambiado::class, fn (ProyectoEstadoCambiado $mail): bool => $mail->nuevoEstado === 'Informe Final en subsanación'
            && $mail->tipoAviso === 'subsanacion'
            && str_contains($mail->render(), 'Su Informe Final fue devuelto para subsanación'));

        $documentos->reenviarDesdeSubsanacion($proyecto->fresh(), $informe->fresh(), 'documentos/informes-finales/inf-001-v2.pdf', $this->coordinador->fresh());
        Mail::assertQueued(ProyectoEstadoCambiado::class, fn (ProyectoEstadoCambiado $mail): bool => $mail->nuevoEstado === 'Informe Final reenviado a revisión');

        foreach ([2, 3, 4, 5] as $orden) {
            $this->aprobar($this->firmaActualDocumento($proyecto, $informe), $this->revisores[$orden]);
        }

        $this->assertSame('Finalizado', $proyecto->fresh()->estado_general);
        Mail::assertQueued(ProyectoEstadoCambiado::class, fn (ProyectoEstadoCambiado $mail): bool => $mail->nuevoEstado === 'Informe Final aprobado'
            && $mail->tipoAviso === 'cierre'
            && $mail->accion === 'cierre del proyecto');
    }

    public function test_el_acuse_dice_enviado_a_revision_y_no_manda_a_produccion(): void
    {
        $proyecto = $this->contexto();
        $correo = new ProyectoCreado($proyecto, $this->coordinador);

        // Sale al enviar a revisión, no al crear el borrador.
        $this->assertSame('Proyecto enviado a revisión - '.$proyecto->nombre_proyecto, $correo->envelope()->subject);
        $this->assertStringContainsString('Confirmación de envío a revisión', $correo->render());
        $this->assertSame(route('historialproyecto', $proyecto), $correo->actionUrl);
        $this->assertStringNotContainsString('nexo.unah.edu.hn', $correo->render());
    }

    // ── Acciones ────────────────────────────────────────────────────────────

    private function enviar(Proyecto $proyecto): void
    {
        $this->actingAs($this->coordinador);
        $componente = new CreateProyectoVinculacion;
        (new \ReflectionMethod($componente, 'enviarPorFlujoDeEtapas'))->invoke($componente, $proyecto->fresh());
    }

    private function aprobar(FirmaProyecto $firma, User $revisor): void
    {
        $this->actingAs($revisor);
        (new ProyectosPorFirmar)->aprobar($firma->id);
        $this->assertSame('Aprobado', $firma->fresh()->estado_revision);
    }

    private function rechazar(FirmaProyecto $firma, User $revisor, string $motivo): void
    {
        $this->actingAs($revisor);
        $bandeja = new ProyectosPorFirmar;
        $bandeja->rechazarId = $firma->id;
        $bandeja->rechazarComentario = $motivo;
        $bandeja->rechazar();
        $this->assertSame('Rechazado', $firma->fresh()->estado_revision);
    }

    private function firmaActual(Proyecto $proyecto): FirmaProyecto
    {
        $proyecto = $proyecto->fresh();
        $ciclo = (int) $proyecto->firma_proyecto()->whereNotNull('flujo_aprobacion_etapa_id')->max('revision_ciclo');
        $firma = $proyecto->firmaActualDeEtapasDelFlujo((int) $proyecto->flujo_aprobacion_id, $ciclo);
        $this->assertNotNull($firma);

        return $firma;
    }

    private function firmaActualDocumento(Proyecto $proyecto, DocumentoProyecto $documento): FirmaProyecto
    {
        $proyecto = $proyecto->fresh();
        $ciclo = (int) $documento->firma_documento()->whereNotNull('flujo_aprobacion_etapa_id')->max('revision_ciclo');
        $firma = $proyecto->firmaActualDeEtapasDelFlujo((int) $proyecto->flujo_aprobacion_id, $ciclo, $documento->fresh());
        $this->assertNotNull($firma);

        return $firma;
    }

    // ── Escenario ───────────────────────────────────────────────────────────

    /** Flujo con las 5 etapas del FORM-DVUS-001; el cierre aplica de la 2 a la 5. */
    private function contexto(): Proyecto
    {
        $etapas = [
            1 => ['Enlace Vinculacion', []],
            2 => ['Jefe Departamento', []],
            3 => ['Director centro', []],
            4 => ['Revisor Vinculacion', ['proyectos.informes', 'proyectos.solicitados']],
            5 => ['Director Vinculacion', ['proyectos.informes']],
        ];
        $this->coordinador = $this->usuario(null, ['docente.proyectos', 'docente.crear-proyecto']);
        $flujo = FlujoAprobacion::create([
            'codigo' => 'CORREOS_001_'.uniqid(),
            'nombre' => 'Flujo de correos 001',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);

        foreach ($etapas as $orden => [$rolNombre, $permisos]) {
            $rol = Role::firstOrCreate(['name' => $rolNombre, 'guard_name' => 'web']);
            $this->revisores[$orden] = $this->usuario($rol, array_merge(['docente.proyectos', 'docente.trazabilidad'], $permisos));
            FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'ETAPA_0'.$orden,
                'nombre' => $rolNombre,
                'tipo_etapa' => 'APROBACION',
                'rol_revisor_id' => $rol->id,
                'usuario_responsable_id' => $this->revisores[$orden]->id,
                'cargo_firma_id' => $this->cargo()->id,
                'activo' => true,
                'aplica_inscripcion' => true,
                'aplica_informe_intermedio' => $orden >= 2,
                'aplica_cierre_proyecto' => $orden >= 2,
            ]);
        }

        foreach (['Subsanacion', 'Aprobado', 'Finalizado'] as $nombre) {
            TipoEstado::firstOrCreate(['nombre' => $nombre]);
        }
        CargoFirma::firstOrCreate([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => TipoCargoFirma::firstOrCreate(['nombre' => 'Coordinador Proyecto'])->id,
        ]);

        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto de correos '.uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
        ]);
        EmpleadoProyecto::create([
            'empleado_id' => $this->coordinador->empleado->id,
            'proyecto_id' => $proyecto->id,
            'rol' => 'Coordinador',
        ]);
        EstadoProyecto::withoutEvents(fn () => $proyecto->estado_proyecto()->create([
            'empleado_id' => $this->coordinador->empleado->id,
            'tipo_estado_id' => EstadoGeneralProyecto::id('Borrador'),
            'fecha' => now(),
            'es_actual' => true,
        ]));

        return $proyecto;
    }

    private function usuario(?Role $rol, array $permisos): User
    {
        $usuario = User::factory()->create();
        Empleado::create([
            'nombre_completo' => 'Empleado correos 001',
            'numero_empleado' => 'C001-'.uniqid(),
            'user_id' => $usuario->id,
        ]);

        foreach ($permisos as $permiso) {
            $usuario->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));
        }

        if ($rol) {
            $usuario->assignRole($rol);
            $usuario->forceFill(['active_role_id' => $rol->id])->save();
        }

        return $usuario->fresh();
    }

    private function cargo(): CargoFirma
    {
        $nombre = 'Cargo correos 001 '.uniqid();

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => TipoCargoFirma::create(['nombre' => $nombre])->id,
            'tipo_estado_id' => TipoEstado::create(['nombre' => 'Estado '.$nombre])->id,
        ]);
    }
}
