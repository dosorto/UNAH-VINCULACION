<?php

namespace Tests\Feature;

use App\Models\Estado\TipoEstado;
use App\Models\InformeIntermedio\InformeIntermedioProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HistoricalProjectClosureEligibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_proyecto_historico_con_aprobaciones_en_ciclos_distintos_y_legacy_aprobado_habilita_cierre(): void
    {
        [$proyecto, $usuario, $empleado, $etapas] = $this->contexto();
        $this->firmar($proyecto, $empleado, $etapas[0], 'Aprobado', 2);
        foreach (array_slice($etapas, 1) as $etapa) {
            $this->firmar($proyecto, $empleado, $etapa, 'Aprobado', 4);
        }
        $this->documentoLegacy($proyecto, $empleado, 'Aprobado');

        $this->assertTrue($proyecto->fresh()->puedeMostrarCierreProyecto($usuario));
    }

    public function test_decision_posterior_pendiente_o_rechazada_invalida_aprobacion_anterior(): void
    {
        [$proyecto, , $empleado, $etapas] = $this->contexto();
        foreach ($etapas as $etapa) {
            $this->firmar($proyecto, $empleado, $etapa, 'Aprobado', 1);
        }
        $this->firmar($proyecto, $empleado, $etapas[0], 'Pendiente', 2);
        $this->assertFalse(app(\App\Services\Proyecto\ProyectoWorkflowService::class)->inscripcionCompletada($proyecto));

        $this->firmar($proyecto, $empleado, $etapas[0], 'Rechazado', 3);
        $this->assertFalse(app(\App\Services\Proyecto\ProyectoWorkflowService::class)->inscripcionCompletada($proyecto));
    }

    public function test_informe_moderno_no_aprobado_tiene_prioridad_sobre_legacy_aprobado(): void
    {
        [$proyecto, $usuario, $empleado, $etapas] = $this->contexto();
        foreach ($etapas as $etapa) {
            $this->firmar($proyecto, $empleado, $etapa, 'Aprobado', 1);
        }
        $this->documentoLegacy($proyecto, $empleado, 'Aprobado');
        InformeIntermedioProyecto::create([
            'proyecto_id' => $proyecto->id,
            'archivo_pdf' => 'historico.pdf',
            'nombre_original' => 'historico.pdf',
            'tamano_bytes' => 10,
            'hash_sha256' => str_repeat('a', 64),
            'estado' => InformeIntermedioProyecto::ESTADO_EN_REVISION,
            'subido_por' => $usuario->id,
        ]);

        $this->assertFalse($proyecto->fresh()->puedeMostrarCierreProyecto($usuario));
    }

    private function contexto(): array
    {
        $usuario = User::factory()->create();
        $empleado = Empleado::create(['user_id' => $usuario->id, 'nombre_completo' => 'Histórico '.uniqid(), 'numero_empleado' => 'H'.uniqid()]);
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Histórico '.uniqid(), 'codigo_proyecto' => 'H-'.uniqid()]);
        EmpleadoProyecto::create(['proyecto_id' => $proyecto->id, 'empleado_id' => $empleado->id, 'rol' => 'Coordinador']);
        $flujo = FlujoAprobacion::create(['codigo' => 'H-'.uniqid(), 'nombre' => 'Histórico', 'proceso' => 'PROYECTO', 'activo' => true]);
        $etapas = [];
        foreach (range(1, 5) as $orden) {
            $tipo = TipoEstado::firstOrCreate(['nombre' => 'Histórico estado '.uniqid()]);
            $rol = TipoCargoFirma::create(['nombre' => 'Histórico cargo '.uniqid()]);
            $cargo = CargoFirma::create(['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $rol->id, 'tipo_estado_id' => $tipo->id]);
            $etapas[] = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id, 'orden' => $orden, 'codigo' => 'H'.$orden.uniqid(), 'nombre' => 'Etapa '.$orden,
                'tipo_etapa' => 'APROBACION', 'cargo_firma_id' => $cargo->id, 'usuario_responsable_id' => $usuario->id,
                'activo' => true, 'aplica_inscripcion' => true, 'aplica_informe_intermedio' => $orden >= 4, 'aplica_cierre_proyecto' => $orden >= 4,
            ]);
        }
        $proyecto->update(['flujo_aprobacion_id' => $flujo->id]);
        $proyecto->estado_proyecto()->create(['empleado_id' => $empleado->id, 'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => 'En curso'])->id, 'fecha' => now(), 'es_actual' => true]);

        return [$proyecto, $usuario, $empleado, $etapas];
    }

    private function firmar(Proyecto $proyecto, Empleado $empleado, FlujoAprobacionEtapa $etapa, string $estado, int $ciclo): void
    {
        $proyecto->guardarFirmaDeEtapa($etapa, $empleado, ['estado_revision' => $estado], null, $ciclo);
    }

    private function documentoLegacy(Proyecto $proyecto, Empleado $empleado, string $estado): void
    {
        $documento = DocumentoProyecto::create(['proyecto_id' => $proyecto->id, 'tipo_documento' => 'Informe Intermedio', 'documento_url' => 'legacy.pdf']);
        $documento->estado_documento()->create(['empleado_id' => $empleado->id, 'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => $estado])->id, 'fecha' => now(), 'es_actual' => true]);
    }
}
