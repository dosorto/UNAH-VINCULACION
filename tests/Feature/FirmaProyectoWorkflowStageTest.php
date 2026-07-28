<?php

namespace Tests\Feature;

use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FirmaProyectoWorkflowStageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_columnas_de_etapa_existen_y_aceptan_null(): void
    {
        $columnas = [
            'flujo_aprobacion_id',
            'flujo_aprobacion_etapa_id',
            'orden_revision',
            'etapa_codigo',
            'etapa_nombre',
            'rol_requerido',
            'responsable_usuario_id',
            'revision_ciclo',
        ];

        foreach ($columnas as $columna) {
            $this->assertTrue(Schema::hasColumn('firma_proyecto', $columna));
            $this->assertTrue($this->columnaPermiteNull('firma_proyecto', $columna));
        }
    }

    public function test_firma_legacy_puede_crearse_sin_etapa(): void
    {
        $firma = $this->crearFirma();

        $this->assertNull($firma->flujo_aprobacion_etapa_id);
        $this->assertTrue($firma->esFirmaLegacy());
        $this->assertFalse($firma->usaFlujoPorEtapa());
    }

    public function test_firma_con_etapa_detecta_flujo_por_etapa(): void
    {
        [$flujo, $etapa] = $this->crearFlujoConEtapa();

        $firma = $this->crearFirma([
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'rol_requerido' => 'Coordinador Proyecto',
            'revision_ciclo' => 1,
        ]);

        $this->assertTrue($firma->usaFlujoPorEtapa());
        $this->assertFalse($firma->esFirmaLegacy());
        $this->assertTrue($firma->flujoAprobacion->is($flujo));
        $this->assertTrue($firma->flujoEtapa->is($etapa));
    }

    public function test_dos_firmas_pueden_compartir_cargo_con_etapas_diferentes(): void
    {
        $cargo = $this->crearCargoFirma();
        $empleado = $this->crearEmpleado();
        $flujo = $this->crearFlujo();
        $etapaA = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $etapaB = $this->crearEtapa($flujo, $cargo, 2, 'ETAPA_B');
        $firmableId = random_int(100000, 999999);

        $firmaA = $this->crearFirma([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'firmable_id' => $firmableId,
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapaA->id,
            'orden_revision' => $etapaA->orden,
            'etapa_codigo' => $etapaA->codigo,
            'etapa_nombre' => $etapaA->nombre,
        ]);
        $firmaB = $this->crearFirma([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'firmable_id' => $firmableId,
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapaB->id,
            'orden_revision' => $etapaB->orden,
            'etapa_codigo' => $etapaB->codigo,
            'etapa_nombre' => $etapaB->nombre,
        ]);

        $this->assertNotSame($firmaA->id, $firmaB->id);
        $this->assertSame(2, FirmaProyecto::where('firmable_type', Proyecto::class)
            ->where('firmable_id', $firmableId)
            ->where('cargo_firma_id', $cargo->id)
            ->count());
    }

    public function test_eliminar_etapa_conserva_firma_y_snapshot(): void
    {
        [$flujo, $etapa] = $this->crearFlujoConEtapa();

        $firma = $this->crearFirma([
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'rol_requerido' => 'Director centro',
        ]);

        $etapa->delete();
        $firma->refresh();

        $this->assertNull($firma->flujo_aprobacion_etapa_id);
        $this->assertSame('ETAPA_1', $firma->etapa_codigo);
        $this->assertSame('Etapa 1', $firma->etapa_nombre);
        $this->assertSame('Director centro', $firma->rol_requerido);
        $this->assertTrue($firma->exists);
    }

    public function test_eliminar_usuario_responsable_conserva_firma(): void
    {
        $responsable = User::create([
            'name' => 'Responsable temporal',
            'email' => 'responsable-temporal-'.uniqid().'@unah.test',
        ]);

        $firma = $this->crearFirma([
            'responsable_usuario_id' => $responsable->id,
        ]);

        $responsable->forceDelete();
        $firma->refresh();

        $this->assertNull($firma->responsable_usuario_id);
        $this->assertTrue($firma->exists);
    }

    public function test_firma_legacy_conserva_relaciones_cargo_y_empleado(): void
    {
        $cargo = $this->crearCargoFirma();
        $empleado = $this->crearEmpleado();

        $firma = $this->crearFirma([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
        ]);

        $this->assertTrue($firma->cargo_firma->is($cargo));
        $this->assertTrue($firma->empleado->is($empleado));
        $this->assertTrue($firma->esFirmaLegacy());
    }

    public function test_estado_revision_permite_guardar_y_actualizar_anulado(): void
    {
        $firma = $this->crearFirma(['estado_revision' => 'Anulado']);

        $this->assertSame('Anulado', $firma->refresh()->estado_revision);

        $firma->update(['estado_revision' => 'Pendiente']);
        $firma->update(['estado_revision' => 'Anulado']);

        $this->assertSame('Anulado', $firma->refresh()->estado_revision);
    }

    public function test_guardar_firma_de_etapa_crea_snapshot_completo(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $responsable = $this->crearUsuario();
        $rol = $this->crearRol('Coordinador Proyecto');
        $flujo = $this->crearFlujo();
        $etapa = $this->crearEtapa($flujo, $cargo, 1, 'REV_COORD', [
            'rol_revisor_id' => $rol->id,
            'usuario_responsable_id' => $responsable->id,
        ]);
        $empleado = $this->crearEmpleado();

        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->save();

        $firma = $proyecto->guardarFirmaDeEtapa($etapa, $empleado, [
            'estado_revision' => 'Pendiente',
        ]);

        $this->assertSame($flujo->id, $firma->flujo_aprobacion_id);
        $this->assertSame($etapa->id, $firma->flujo_aprobacion_etapa_id);
        $this->assertSame(1, $firma->orden_revision);
        $this->assertSame('REV_COORD', $firma->etapa_codigo);
        $this->assertSame('Etapa 1', $firma->etapa_nombre);
        $this->assertSame($rol->name, $firma->rol_requerido);
        $this->assertSame($responsable->id, $firma->responsable_usuario_id);
        $this->assertSame($cargo->id, $firma->cargo_firma_id);
        $this->assertSame(1, $firma->revision_ciclo);
        $this->assertSame($empleado->id, $firma->empleado_id);
        $this->assertSame(Proyecto::class, $firma->firmable_type);
        $this->assertSame($proyecto->id, $firma->firmable_id);
    }

    public function test_dos_etapas_con_mismo_cargo_crean_firmas_independientes_por_etapa(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $etapaA = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $etapaB = $this->crearEtapa($flujo, $cargo, 2, 'ETAPA_B');
        $empleado = $this->crearEmpleado();

        $firmaA = $proyecto->guardarFirmaDeEtapa($etapaA, $empleado);
        $firmaB = $proyecto->guardarFirmaDeEtapa($etapaB, $empleado);

        $this->assertNotSame($firmaA->id, $firmaB->id);
        $this->assertSame(2, $proyecto->firma_proyecto()->where('cargo_firma_id', $cargo->id)->count());
    }

    public function test_guardar_misma_etapa_y_ciclo_actualiza_firma_y_refresca_snapshot(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $etapa = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $empleadoA = $this->crearEmpleado();
        $empleadoB = $this->crearEmpleado();

        $firmaInicial = $proyecto->guardarFirmaDeEtapa($etapa, $empleadoA);

        $etapa->update([
            'codigo' => 'ETAPA_ACTUALIZADA',
            'nombre' => 'Etapa actualizada',
        ]);

        $firmaActualizada = $proyecto->guardarFirmaDeEtapa($etapa->fresh(), $empleadoB);

        $this->assertSame($firmaInicial->id, $firmaActualizada->id);
        $this->assertSame($empleadoB->id, $firmaActualizada->empleado_id);
        $this->assertSame('ETAPA_ACTUALIZADA', $firmaActualizada->etapa_codigo);
        $this->assertSame('Etapa actualizada', $firmaActualizada->etapa_nombre);
        $this->assertSame(1, $proyecto->firma_proyecto()->where('flujo_aprobacion_etapa_id', $etapa->id)->count());
    }

    public function test_misma_etapa_en_otro_ciclo_crea_firma_independiente(): void
    {
        $proyecto = $this->crearProyecto();
        [$flujo, $etapa] = $this->crearFlujoConEtapa();
        $empleado = $this->crearEmpleado();

        $firmaCicloUno = $proyecto->guardarFirmaDeEtapa($etapa, $empleado, [], null, 1);
        $firmaCicloDos = $proyecto->guardarFirmaDeEtapa($etapa, $empleado, [], null, 2);

        $this->assertSame($flujo->id, $firmaCicloDos->flujo_aprobacion_id);
        $this->assertNotSame($firmaCicloUno->id, $firmaCicloDos->id);
        $this->assertSame(2, $proyecto->firma_proyecto()->where('flujo_aprobacion_etapa_id', $etapa->id)->count());
    }

    public function test_guardar_firma_de_etapa_no_modifica_firmas_legacy_ni_otras_etapas(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $etapaA = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $etapaB = $this->crearEtapa($flujo, $cargo, 2, 'ETAPA_B');
        $empleado = $this->crearEmpleado();
        $legacy = $this->crearFirma([
            'firmable_type' => Proyecto::class,
            'firmable_id' => $proyecto->id,
            'cargo_firma_id' => $cargo->id,
            'empleado_id' => $empleado->id,
        ]);
        $otraEtapa = $proyecto->guardarFirmaDeEtapa($etapaB, $empleado, ['estado_revision' => 'Aprobado']);

        $proyecto->guardarFirmaDeEtapa($etapaA, $empleado);

        $this->assertNull($legacy->refresh()->flujo_aprobacion_etapa_id);
        $this->assertSame('Aprobado', $otraEtapa->refresh()->estado_revision);
    }

    public function test_anular_duplicados_de_etapa_solo_afecta_misma_etapa_y_ciclo(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $etapaA = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $etapaB = $this->crearEtapa($flujo, $cargo, 2, 'ETAPA_B');
        $empleado = $this->crearEmpleado();
        $principal = $this->crearFirmaDeEtapaManual($proyecto, $etapaA, $empleado, ['revision_ciclo' => 1]);
        $duplicada = $this->crearFirmaDeEtapaManual($proyecto, $etapaA, $empleado, ['revision_ciclo' => 1]);
        $otroCiclo = $this->crearFirmaDeEtapaManual($proyecto, $etapaA, $empleado, ['revision_ciclo' => 2]);
        $otraEtapa = $this->crearFirmaDeEtapaManual($proyecto, $etapaB, $empleado, ['revision_ciclo' => 1]);
        $aprobada = $this->crearFirmaDeEtapaManual($proyecto, $etapaA, $empleado, [
            'revision_ciclo' => 1,
            'estado_revision' => 'Aprobado',
        ]);
        $rechazada = $this->crearFirmaDeEtapaManual($proyecto, $etapaA, $empleado, [
            'revision_ciclo' => 1,
            'estado_revision' => 'Rechazado',
        ]);

        $proyecto->anularFirmasPendientesDuplicadasDeEtapa($etapaA->id, 1, $principal->id);

        $this->assertSame('Pendiente', $principal->refresh()->estado_revision);
        $this->assertSame('Anulado', $duplicada->refresh()->estado_revision);
        $this->assertNotSame('Rechazado', $duplicada->estado_revision);
        $this->assertSame('Pendiente', $otroCiclo->refresh()->estado_revision);
        $this->assertSame('Pendiente', $otraEtapa->refresh()->estado_revision);
        $this->assertSame('Aprobado', $aprobada->refresh()->estado_revision);
        $this->assertSame('Rechazado', $rechazada->refresh()->estado_revision);
    }

    public function test_sincronizar_firmas_de_etapas_crea_una_por_etapa_activa_en_orden(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $etapaA = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $etapaB = $this->crearEtapa($flujo, $cargo, 2, 'ETAPA_B');
        $this->crearEtapa($flujo, $cargo, 3, 'ETAPA_INACTIVA', ['activo' => false]);
        $empleadoA = $this->crearEmpleado();
        $empleadoB = $this->crearEmpleado();
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->save();

        $firmas = $proyecto->sincronizarFirmasDeEtapasDelFlujo([
            $etapaA->id => $empleadoA->id,
            $etapaB->id => $empleadoB,
        ]);

        $this->assertCount(2, $firmas);
        $this->assertSame([$etapaA->id, $etapaB->id], $firmas->pluck('flujo_aprobacion_etapa_id')->all());
        $this->assertSame([$empleadoA->id, $empleadoB->id], $firmas->pluck('empleado_id')->all());
        $this->assertSame(['Pendiente', 'Pendiente'], $firmas->pluck('estado_revision')->all());
    }

    public function test_sincronizar_falla_si_falta_empleado_y_no_deja_firmas_parciales(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $etapaA = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $etapaB = $this->crearEtapa($flujo, $cargo, 2, 'ETAPA_B');
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El empleado indicado para la etapa "Etapa 2" no existe.');

        try {
            $proyecto->sincronizarFirmasDeEtapasDelFlujo([
                $etapaA->id => $this->crearEmpleado()->id,
                $etapaB->id => 999999999,
            ]);
        } finally {
            $this->assertSame(0, $proyecto->firma_proyecto()->count());
        }
    }

    public function test_sincronizar_falla_si_falta_asignacion_de_etapa(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $etapa = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se indicó un empleado para la etapa "Etapa 1".');

        $proyecto->sincronizarFirmasDeEtapasDelFlujo([]);
    }

    public function test_sincronizar_falla_si_la_etapa_no_pertenece_al_flujo(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $otroFlujo = $this->crearFlujo();
        $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_A');
        $etapaAjena = $this->crearEtapa($otroFlujo, $cargo, 1, 'ETAPA_AJENA');
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La etapa indicada no pertenece al flujo del proyecto.');

        $proyecto->sincronizarFirmasDeEtapasDelFlujo([
            $etapaAjena->id => $this->crearEmpleado()->id,
        ]);
    }

    public function test_guardar_firma_de_etapa_falla_si_no_tiene_cargo_o_ciclo_es_invalido(): void
    {
        $proyecto = $this->crearProyecto();
        [$flujo, $etapa] = $this->crearFlujoConEtapa();
        $empleado = $this->crearEmpleado();

        $etapaSinCargo = $etapa->replicate();
        $etapaSinCargo->id = $etapa->id;
        $etapaSinCargo->exists = true;
        $etapaSinCargo->flujo_aprobacion_id = $flujo->id;
        $etapaSinCargo->cargo_firma_id = null;

        try {
            $proyecto->guardarFirmaDeEtapa($etapaSinCargo, $empleado);
            $this->fail('La etapa sin cargo debió fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('La etapa "Etapa 1" no tiene cargo de firma.', $exception->getMessage());
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El ciclo de revisión debe ser mayor o igual a 1.');

        $proyecto->guardarFirmaDeEtapa($etapa, $empleado, [], null, 0);
    }

    public function test_guardar_firma_de_etapa_funciona_para_documento_y_no_colisiona_con_proyecto(): void
    {
        $proyecto = $this->crearProyecto();
        [$flujo, $etapa] = $this->crearFlujoConEtapa();
        $empleado = $this->crearEmpleado();
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Intermedio',
            'documento_url' => 'documentos/prueba.pdf',
        ]);

        $firmaProyecto = $proyecto->guardarFirmaDeEtapa($etapa, $empleado);
        $firmaDocumento = $proyecto->guardarFirmaDeEtapa($etapa, $empleado, [], $documento);

        $this->assertSame($flujo->id, $firmaDocumento->flujo_aprobacion_id);
        $this->assertSame(Proyecto::class, $firmaProyecto->firmable_type);
        $this->assertSame($proyecto->id, $firmaProyecto->firmable_id);
        $this->assertSame(\App\Models\Proyecto\DocumentoProyecto::class, $firmaDocumento->firmable_type);
        $this->assertSame($documento->id, $firmaDocumento->firmable_id);
        $this->assertNotSame($firmaProyecto->id, $firmaDocumento->id);
    }

    public function test_sincronizar_firmas_de_etapas_funciona_para_documento_y_no_colisiona_con_proyecto(): void
    {
        $proyecto = $this->crearProyecto();
        [$flujo, $etapa] = $this->crearFlujoConEtapa();
        $empleado = $this->crearEmpleado();
        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Intermedio',
            'documento_url' => 'documentos/prueba.pdf',
        ]);
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->save();

        $firmaProyecto = $proyecto->guardarFirmaDeEtapa($etapa, $empleado);
        $firmasDocumento = $proyecto->sincronizarFirmasDeEtapasDelFlujo([
            $etapa->id => $empleado->id,
        ], Proyecto::FLUJO_INSCRIPCION, $documento);

        $firmaDocumento = $firmasDocumento->first();

        $this->assertCount(1, $firmasDocumento);
        $this->assertSame(\App\Models\Proyecto\DocumentoProyecto::class, $firmaDocumento->firmable_type);
        $this->assertSame($documento->id, $firmaDocumento->firmable_id);
        $this->assertSame($etapa->id, $firmaDocumento->flujo_aprobacion_etapa_id);
        $this->assertNotSame($firmaProyecto->id, $firmaDocumento->id);
    }

    public function test_sincronizar_firmas_de_etapas_falla_si_ciclo_es_invalido(): void
    {
        $proyecto = $this->crearProyecto();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El ciclo de revisión debe ser mayor o igual a 1.');

        $proyecto->sincronizarFirmasDeEtapasDelFlujo([], Proyecto::FLUJO_INSCRIPCION, null, 0);
    }

    public function test_guardar_firma_de_cargo_conserva_comportamiento_legacy(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $empleadoA = $this->crearEmpleado();
        $empleadoB = $this->crearEmpleado();

        $firmaA = $proyecto->guardarFirmaDeCargo($cargo->id, $empleadoA);
        $firmaB = $proyecto->guardarFirmaDeCargo($cargo->id, $empleadoB);

        $this->assertSame($firmaA->id, $firmaB->id);
        $this->assertSame($empleadoB->id, $firmaB->empleado_id);
        $this->assertNull($firmaB->flujo_aprobacion_etapa_id);
        $this->assertTrue($firmaB->esFirmaLegacy());
    }

    public function test_guardar_firma_de_cargo_rechaza_un_cargo_inexistente_antes_de_insertar(): void
    {
        $proyecto = $this->crearProyecto();
        $empleado = $this->crearEmpleado();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se encontró el cargo de firma configurado (ID: 999999999).');

        $proyecto->guardarFirmaDeCargo(999999999, $empleado);
    }

    public function test_agregar_firma_resuelve_el_cargo_configurado_sin_depender_del_id(): void
    {
        $proyecto = $this->crearProyecto();
        $empleado = $this->crearEmpleado();
        $nombreCargo = 'Coordinador Proyecto '.uniqid();
        $tipoCargo = TipoCargoFirma::create(['nombre' => $nombreCargo]);
        $cargo = CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => $tipoCargo->id,
        ]);

        $firma = $proyecto->agregarFirma($nombreCargo, $empleado);

        $this->assertSame($cargo->id, $firma->cargo_firma_id);
        $this->assertSame('Aprobado', $firma->estado_revision);
    }

    public function test_anular_duplicados_de_cargo_legacy_persiste_anulado(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $empleado = $this->crearEmpleado();
        $principal = $this->crearFirmaLegacyManual($proyecto, $cargo, $empleado);
        $duplicada = $this->crearFirmaLegacyManual($proyecto, $cargo, $empleado);

        $proyecto->anularFirmasPendientesDuplicadasDeCargo($cargo->id, $principal->id);

        $this->assertSame('Pendiente', $principal->refresh()->estado_revision);
        $this->assertSame('Anulado', $duplicada->refresh()->estado_revision);
    }

    public function test_firma_anulada_no_es_accionable_ni_aprobable_ni_rechazable(): void
    {
        $estado = $this->crearTipoEstado();
        $proyecto = $this->crearProyecto();
        $empleado = $this->crearEmpleado();
        $cargo = $this->crearCargoFirma($estado->id);
        $firma = $this->crearFirmaLegacyManual($proyecto, $cargo, $empleado, [
            'estado_revision' => 'Anulado',
        ]);

        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $estado->id,
            'fecha' => now(),
            'es_actual' => true,
        ]);

        $this->actingAs($empleado->user);

        $component = new ProyectosPorFirmar;
        $component->mount($empleado);

        $method = new \ReflectionMethod($component, 'firmasDisponiblesQuery');
        $method->setAccessible(true);

        $this->assertSame(0, $method->invoke($component)->whereKey($firma->id)->count());
        $this->assertFalse($component->puedeSubsanar($firma->id));

        try {
            $component->aprobar($firma->id);
            $this->fail('Una firma Anulada no debe aprobarse.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        try {
            $component->openRechazar($firma->id);
            $this->fail('Una firma Anulada no debe rechazarse.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('Anulado', $firma->refresh()->estado_revision);
    }

    public function test_firmas_de_etapas_del_flujo_devuelve_solo_firmas_nuevas_ordenadas_por_snapshot(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $otroFlujo = $this->crearFlujo();
        $empleado = $this->crearEmpleado();
        $etapaOrdenDos = $this->crearEtapa($flujo, $cargo, 3, 'ETAPA_2');
        $etapaEmpateA = $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_1A');
        $etapaEmpateB = $this->crearEtapa($flujo, $cargo, 2, 'ETAPA_1B');
        $etapaOtroFlujo = $this->crearEtapa($otroFlujo, $cargo, 1, 'OTRO_FLUJO');

        $firmaOrdenDos = $this->crearFirmaDeEtapaManual($proyecto, $etapaOrdenDos, $empleado);
        $firmaEmpateA = $this->crearFirmaDeEtapaManual($proyecto, $etapaEmpateA, $empleado);
        $firmaEmpateB = $this->crearFirmaDeEtapaManual($proyecto, $etapaEmpateB, $empleado, ['orden_revision' => 1]);
        $this->crearFirmaDeEtapaManual($proyecto, $etapaOtroFlujo, $empleado);
        $this->crearFirmaDeEtapaManual($proyecto, $etapaEmpateA, $empleado, ['revision_ciclo' => 2]);
        $this->crearFirmaLegacyManual($proyecto, $cargo, $empleado);
        $eliminada = $this->crearFirmaDeEtapaManual($proyecto, $etapaEmpateA, $empleado);
        $eliminada->newQuery()->whereKey($eliminada->id)->update(['deleted_at' => now()]);

        $firmas = $proyecto->firmasDeEtapasDelFlujo($flujo->id);

        $this->assertSame([
            $firmaEmpateA->id,
            $firmaEmpateB->id,
            $firmaOrdenDos->id,
        ], $firmas->pluck('id')->all());
        $this->assertFalse($firmas->contains('id', $eliminada->id));
    }

    public function test_firmas_de_etapas_del_flujo_aisla_proyecto_documentos_y_parametros_invalidos(): void
    {
        $proyecto = $this->crearProyecto();
        $otroProyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        [$flujo, $etapa] = $this->crearFlujoConEtapaConCargo($cargo);
        $empleado = $this->crearEmpleado();
        $documentoA = $this->crearDocumento($proyecto, 'Informe Intermedio');
        $documentoB = $this->crearDocumento($proyecto, 'Informe Final');
        $documentoAjeno = $this->crearDocumento($otroProyecto, 'Informe Intermedio');

        $firmaProyecto = $this->crearFirmaDeEtapaManual($proyecto, $etapa, $empleado);
        $firmaDocumentoA = $this->crearFirmaDeEtapaDocumentoManual($documentoA, $etapa, $empleado);
        $firmaDocumentoB = $this->crearFirmaDeEtapaDocumentoManual($documentoB, $etapa, $empleado);

        $this->assertSame([$firmaProyecto->id], $proyecto->firmasDeEtapasDelFlujo($flujo->id)->pluck('id')->all());
        $this->assertSame([$firmaDocumentoA->id], $proyecto->firmasDeEtapasDelFlujo($flujo->id, 1, $documentoA)->pluck('id')->all());
        $this->assertSame([$firmaDocumentoB->id], $proyecto->firmasDeEtapasDelFlujo($flujo->id, 1, $documentoB)->pluck('id')->all());

        try {
            $proyecto->firmasDeEtapasDelFlujo(0);
            $this->fail('El flujo inválido debió fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('El flujo de aprobación indicado no es válido.', $exception->getMessage());
        }

        try {
            $proyecto->firmasDeEtapasDelFlujo($flujo->id, 0);
            $this->fail('El ciclo inválido debió fallar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('El ciclo de revisión debe ser mayor o igual a 1.', $exception->getMessage());
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El documento indicado no pertenece al proyecto actual.');

        $proyecto->firmasDeEtapasDelFlujo($flujo->id, 1, $documentoAjeno);
    }

    public function test_firma_actual_respeta_pendiente_aprobado_anulado_y_rechazado(): void
    {
        $proyecto = $this->crearProyecto();
        [$flujo, $etapas] = $this->crearFlujoConEtapas(4);
        $empleado = $this->crearEmpleado();
        $firmaAprobada = $this->crearFirmaDeEtapaManual($proyecto, $etapas[0], $empleado, ['estado_revision' => 'Aprobado']);
        $firmaAnulada = $this->crearFirmaDeEtapaManual($proyecto, $etapas[1], $empleado, ['estado_revision' => 'Anulado']);
        $firmaPendiente = $this->crearFirmaDeEtapaManual($proyecto, $etapas[2], $empleado);
        $firmaPosterior = $this->crearFirmaDeEtapaManual($proyecto, $etapas[3], $empleado);

        $this->assertSame($firmaPendiente->id, $proyecto->firmaActualDeEtapasDelFlujo($flujo->id)?->id);

        $firmaPendiente->update(['estado_revision' => 'Aprobado']);
        $this->assertSame($firmaPosterior->id, $proyecto->firmaActualDeEtapasDelFlujo($flujo->id)?->id);

        $firmaAnulada->update(['estado_revision' => 'Rechazado']);
        $this->assertNull($proyecto->firmaActualDeEtapasDelFlujo($flujo->id));

        $firmaAnulada->update(['estado_revision' => 'Anulado']);
        $firmaPosterior->update(['estado_revision' => 'Aprobado']);
        $this->assertNull($proyecto->firmaActualDeEtapasDelFlujo($flujo->id));

        $firmaAprobada->update(['estado_revision' => 'Anulado']);
        $firmaPendiente->update(['estado_revision' => 'Anulado']);
        $firmaPosterior->update(['estado_revision' => 'Anulado']);
        $this->assertNull($proyecto->firmaActualDeEtapasDelFlujo($flujo->id));
    }

    public function test_firma_es_actual_solo_para_primera_pendiente_valida(): void
    {
        $proyecto = $this->crearProyecto();
        $otroProyecto = $this->crearProyecto();
        [$flujo, $etapas] = $this->crearFlujoConEtapas(3);
        $empleado = $this->crearEmpleado();
        $primera = $this->crearFirmaDeEtapaManual($proyecto, $etapas[0], $empleado);
        $segunda = $this->crearFirmaDeEtapaManual($proyecto, $etapas[1], $empleado);
        $tercera = $this->crearFirmaDeEtapaManual($proyecto, $etapas[2], $empleado, ['estado_revision' => 'Anulado']);
        $legacy = $this->crearFirmaLegacyManual($proyecto, $etapas[0]->cargoFirma, $empleado);
        $otraProyecto = $this->crearFirmaDeEtapaManual($otroProyecto, $etapas[0], $empleado);

        $this->assertTrue($proyecto->firmaEsActualEnFlujoPorEtapa($primera));
        $this->assertFalse($proyecto->firmaEsActualEnFlujoPorEtapa($segunda));
        $this->assertFalse($proyecto->firmaEsActualEnFlujoPorEtapa($tercera));
        $this->assertFalse($proyecto->firmaEsActualEnFlujoPorEtapa($legacy));
        $this->assertFalse($proyecto->firmaEsActualEnFlujoPorEtapa($otraProyecto));

        $primera->update(['estado_revision' => 'Aprobado']);
        $this->assertTrue($proyecto->firmaEsActualEnFlujoPorEtapa($segunda->refresh()));

        $primera->update(['estado_revision' => 'Rechazado']);
        $this->assertFalse($proyecto->firmaEsActualEnFlujoPorEtapa($primera->refresh()));
        $this->assertFalse($proyecto->firmaEsActualEnFlujoPorEtapa($segunda->refresh()));

        $primera->update(['estado_revision' => 'Aprobado']);
        $segunda->newQuery()->whereKey($segunda->id)->update(['deleted_at' => now()]);
        $this->assertFalse($proyecto->firmaEsActualEnFlujoPorEtapa($segunda->refresh()));

        $this->assertSame($flujo->id, $primera->flujo_aprobacion_id);
    }

    public function test_siguiente_firma_de_etapa_respeta_orden_ciclo_firmable_y_anuladas(): void
    {
        $proyecto = $this->crearProyecto();
        [$flujo, $etapas] = $this->crearFlujoConEtapas(4);
        $empleado = $this->crearEmpleado();
        $documento = $this->crearDocumento($proyecto);
        $primera = $this->crearFirmaDeEtapaManual($proyecto, $etapas[0], $empleado, ['estado_revision' => 'Aprobado']);
        $anulada = $this->crearFirmaDeEtapaManual($proyecto, $etapas[1], $empleado, ['estado_revision' => 'Anulado']);
        $siguiente = $this->crearFirmaDeEtapaManual($proyecto, $etapas[2], $empleado);
        $this->crearFirmaDeEtapaManual($proyecto, $etapas[1], $empleado, ['revision_ciclo' => 2]);
        $this->crearFirmaDeEtapaDocumentoManual($documento, $etapas[1], $empleado);
        $ultima = $this->crearFirmaDeEtapaManual($proyecto, $etapas[3], $empleado);

        $this->assertSame($siguiente->id, $proyecto->siguienteFirmaDeEtapa($primera)?->id);
        $this->assertSame($ultima->id, $proyecto->siguienteFirmaDeEtapa($siguiente)?->id);
        $this->assertNull($proyecto->siguienteFirmaDeEtapa($ultima));
        $this->assertSame($flujo->id, $anulada->flujo_aprobacion_id);
    }

    public function test_firmas_de_etapas_completadas_evalua_estados_resueltos_y_bloqueantes(): void
    {
        $proyecto = $this->crearProyecto();
        [$flujo, $etapas] = $this->crearFlujoConEtapas(2);
        $empleado = $this->crearEmpleado();

        $this->assertFalse($proyecto->firmasDeEtapasCompletadas($flujo->id));

        $legacy = $this->crearFirmaLegacyManual($proyecto, $etapas[0]->cargoFirma, $empleado, ['estado_revision' => 'Aprobado']);
        $this->assertFalse($proyecto->firmasDeEtapasCompletadas($flujo->id));

        $firmaA = $this->crearFirmaDeEtapaManual($proyecto, $etapas[0], $empleado, ['estado_revision' => 'Aprobado']);
        $firmaB = $this->crearFirmaDeEtapaManual($proyecto, $etapas[1], $empleado, ['estado_revision' => 'Aprobado']);
        $this->assertTrue($proyecto->firmasDeEtapasCompletadas($flujo->id));

        $firmaB->update(['estado_revision' => 'Anulado']);
        $this->assertTrue($proyecto->firmasDeEtapasCompletadas($flujo->id));

        $firmaA->update(['estado_revision' => 'Pendiente']);
        $this->assertFalse($proyecto->firmasDeEtapasCompletadas($flujo->id));

        $firmaA->update(['estado_revision' => 'Rechazado']);
        $this->assertFalse($proyecto->firmasDeEtapasCompletadas($flujo->id));

        $firmaA->update(['estado_revision' => 'Anulado']);
        $this->assertFalse($proyecto->firmasDeEtapasCompletadas($flujo->id));
        $this->assertSame('Aprobado', $legacy->refresh()->estado_revision);
    }

    public function test_snapshot_no_se_reordena_por_cambios_vivos_y_etapa_eliminada_no_es_accionable(): void
    {
        $proyecto = $this->crearProyecto();
        [$flujo, $etapas] = $this->crearFlujoConEtapas(3);
        $empleado = $this->crearEmpleado();
        $firmaA = $this->crearFirmaDeEtapaManual($proyecto, $etapas[0], $empleado);
        $firmaB = $this->crearFirmaDeEtapaManual($proyecto, $etapas[1], $empleado);
        $firmaC = $this->crearFirmaDeEtapaManual($proyecto, $etapas[2], $empleado);

        $etapas[0]->update(['orden' => 30]);
        $etapas[1]->update(['orden' => 20]);
        $etapas[2]->update(['orden' => 10]);

        $this->assertSame([
            $firmaA->id,
            $firmaB->id,
            $firmaC->id,
        ], $proyecto->firmasDeEtapasDelFlujo($flujo->id)->pluck('id')->all());

        $etapas[0]->delete();
        $firmaA->refresh();

        $this->assertNull($firmaA->flujo_aprobacion_etapa_id);
        $this->assertFalse($proyecto->firmaEsActualEnFlujoPorEtapa($firmaA));
        $this->assertSame([
            $firmaB->id,
            $firmaC->id,
        ], $proyecto->firmasDeEtapasDelFlujo($flujo->id)->pluck('id')->all());
        $this->assertSame($firmaB->id, $proyecto->firmaActualDeEtapasDelFlujo($flujo->id)?->id);
    }

    public function test_tres_etapas_con_mismo_cargo_mantienen_secuencia_independiente(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        [$flujo, $etapas] = $this->crearFlujoConEtapas(3, $cargo);
        $empleado = $this->crearEmpleado();
        $firmaA = $this->crearFirmaDeEtapaManual($proyecto, $etapas[0], $empleado);
        $firmaB = $this->crearFirmaDeEtapaManual($proyecto, $etapas[1], $empleado);
        $firmaC = $this->crearFirmaDeEtapaManual($proyecto, $etapas[2], $empleado);

        $this->assertSame(3, $proyecto->firmasDeEtapasDelFlujo($flujo->id)->where('cargo_firma_id', $cargo->id)->count());
        $this->assertSame($firmaA->id, $proyecto->firmaActualDeEtapasDelFlujo($flujo->id)?->id);
        $this->assertSame($firmaB->id, $proyecto->siguienteFirmaDeEtapa($firmaA)?->id);

        $firmaA->update(['estado_revision' => 'Aprobado']);
        $firmaB->update(['estado_revision' => 'Aprobado']);

        $this->assertSame($firmaC->id, $proyecto->firmaActualDeEtapasDelFlujo($flujo->id)?->id);
    }

    public function test_sincronizar_firmas_del_flujo_conserva_comportamiento_legacy(): void
    {
        $proyecto = $this->crearProyecto();
        $cargo = $this->crearCargoFirma();
        $rol = $this->crearRol('Rol Flujo Legacy');
        $empleado = $this->crearEmpleado();
        $empleado->user->assignRole($rol);
        $flujo = $this->crearFlujo();
        $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_LEGACY', ['rol_revisor_id' => $rol->id]);
        $proyecto->forceFill(['flujo_aprobacion_id' => $flujo->id])->save();

        $proyecto->sincronizarFirmasDelFlujo();

        $firma = $proyecto->firma_proyecto()->first();

        $this->assertNotNull($firma);
        $this->assertSame($cargo->id, $firma->cargo_firma_id);
        $this->assertNull($firma->flujo_aprobacion_etapa_id);
        $this->assertTrue($firma->esFirmaLegacy());
    }

    private function crearFirma(array $attributes = []): FirmaProyecto
    {
        return FirmaProyecto::create(array_merge([
            'empleado_id' => $attributes['empleado_id'] ?? $this->crearEmpleado()->id,
            'cargo_firma_id' => $attributes['cargo_firma_id'] ?? $this->crearCargoFirma()->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
            'firmable_type' => Proyecto::class,
            'firmable_id' => random_int(100000, 999999),
        ], $attributes));
    }

    private function crearFirmaLegacyManual(
        Proyecto $proyecto,
        CargoFirma $cargo,
        Empleado $empleado,
        array $attributes = []
    ): FirmaProyecto {
        return $proyecto->firma_proyecto()->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
        ], $attributes));
    }

    private function crearFirmaDeEtapaManual(
        Proyecto $proyecto,
        FlujoAprobacionEtapa $etapa,
        Empleado $empleado,
        array $attributes = []
    ): FirmaProyecto {
        return $proyecto->firma_proyecto()->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'revision_ciclo' => 1,
        ], $attributes));
    }

    private function crearFirmaDeEtapaDocumentoManual(
        \App\Models\Proyecto\DocumentoProyecto $documento,
        FlujoAprobacionEtapa $etapa,
        Empleado $empleado,
        array $attributes = []
    ): FirmaProyecto {
        return $documento->firma_documento()->create(array_merge([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $etapa->cargo_firma_id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash-test',
            'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => $etapa->orden,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'revision_ciclo' => 1,
        ], $attributes));
    }

    private function crearProyecto(): Proyecto
    {
        return Proyecto::create([
            'nombre_proyecto' => 'Proyecto de prueba '.uniqid(),
            'codigo_proyecto' => 'PRY-'.uniqid(),
        ]);
    }

    private function crearDocumento(Proyecto $proyecto, string $tipo = 'Informe Intermedio'): \App\Models\Proyecto\DocumentoProyecto
    {
        return $proyecto->documentos()->create([
            'tipo_documento' => $tipo,
            'documento_url' => 'documentos/'.uniqid().'.pdf',
        ]);
    }

    private function crearUsuario(): User
    {
        return User::create([
            'name' => 'Usuario prueba',
            'email' => 'usuario-prueba-'.uniqid().'@unah.test',
        ]);
    }

    private function crearEmpleado(): Empleado
    {
        $user = $this->crearUsuario();

        return Empleado::create([
            'nombre_completo' => 'Empleado prueba',
            'numero_empleado' => 'TEST-'.uniqid(),
            'celular' => '99999999',
            'user_id' => $user->id,
        ]);
    }

    private function crearRol(string $nombre): Role
    {
        return Role::create([
            'name' => $nombre.' '.uniqid(),
            'guard_name' => 'web',
        ]);
    }

    private function crearTipoEstado(): TipoEstado
    {
        return TipoEstado::create([
            'nombre' => 'Estado prueba '.uniqid(),
        ]);
    }

    private function crearCargoFirma(?int $tipoEstadoId = null): CargoFirma
    {
        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_estado_id' => $tipoEstadoId,
        ]);
    }

    private function crearFlujoConEtapa(): array
    {
        $cargo = $this->crearCargoFirma();
        $flujo = $this->crearFlujo();

        return [$flujo, $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_1')];
    }

    private function crearFlujoConEtapaConCargo(CargoFirma $cargo): array
    {
        $flujo = $this->crearFlujo();

        return [$flujo, $this->crearEtapa($flujo, $cargo, 1, 'ETAPA_1')];
    }

    private function crearFlujoConEtapas(int $cantidad, ?CargoFirma $cargo = null): array
    {
        $cargo = $cargo ?: $this->crearCargoFirma();
        $flujo = $this->crearFlujo();
        $etapas = [];

        for ($orden = 1; $orden <= $cantidad; $orden++) {
            $etapas[] = $this->crearEtapa($flujo, $cargo, $orden, 'ETAPA_'.$orden.'_'.uniqid());
        }

        return [$flujo, $etapas];
    }

    private function crearFlujo(): FlujoAprobacion
    {
        return FlujoAprobacion::create([
            'codigo' => 'FLUJO_TEST_'.uniqid(),
            'nombre' => 'Flujo de prueba',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
    }

    private function crearEtapa(
        FlujoAprobacion $flujo,
        CargoFirma $cargo,
        int $orden,
        string $codigo,
        array $attributes = []
    ): FlujoAprobacionEtapa
    {
        return FlujoAprobacionEtapa::create(array_merge([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => $orden,
            'codigo' => $codigo,
            'nombre' => 'Etapa '.$orden,
            'cargo_firma_id' => $cargo->id,
            'activo' => true,
        ], $attributes));
    }

    private function columnaPermiteNull(string $table, string $column): bool
    {
        $definition = collect(Schema::getColumns($table))
            ->firstWhere('name', $column);

        return (bool) ($definition['nullable'] ?? false);
    }
}
