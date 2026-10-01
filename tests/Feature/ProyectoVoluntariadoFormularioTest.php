<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * FORM-DVUS-015 (Voluntariado Académico) reutiliza el wizard `CreateProyectoVinculacion`
 * en "modo voluntariado". Estas pruebas fijan las reglas y la completitud de paso
 * exclusivas del 015, sin tocar la base de datos.
 */
class ProyectoVoluntariadoFormularioTest extends TestCase
{
    private function componenteVoluntariado(): CreateProyectoVinculacion
    {
        $component = new CreateProyectoVinculacion;
        $component->esVoluntariado = true;

        return $component;
    }

    public function test_paso_5_no_avanza_sin_la_experiencia_academica(): void
    {
        $component = $this->componenteVoluntariado();
        $component->currentStep = 5;

        foreach (['resumen', 'participacion_unah', 'participacion_contraparte', 'participacion_comunidad',
            'definicion_problema', 'alineamiento_reforma', 'metodologia', 'bibliografia'] as $campo) {
            $component->{$campo} = 'Contenido de prueba suficiente';
        }

        try {
            $component->nextStep();
            $this->fail('Se esperaba ValidationException por falta de experiencia académica.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('experiencia_conocimientos_teoricos', $e->errors());
        }

        $this->assertSame(5, $component->currentStep);

        $component->experiencia_conocimientos_teoricos = 'Marco conceptual aplicado';
        $component->experiencia_habilidades_tecnicas = 'Levantamiento de datos en campo';
        $component->experiencia_competencias_blandas = 'Trabajo en equipo y comunicación';

        $component->nextStep();

        $this->assertSame(6, $component->currentStep);
    }

    public function test_un_proyecto_no_voluntariado_no_exige_la_experiencia_academica(): void
    {
        $component = new CreateProyectoVinculacion;
        $component->esVoluntariado = false;
        $component->currentStep = 5;

        foreach (['resumen', 'participacion_unah', 'participacion_contraparte', 'participacion_comunidad',
            'definicion_problema', 'alineamiento_reforma', 'metodologia', 'bibliografia'] as $campo) {
            $component->{$campo} = 'Contenido de prueba suficiente';
        }

        $component->nextStep();

        $this->assertSame(6, $component->currentStep);
    }

    public function test_completitud_de_paso_1_exige_tematica_principal_en_voluntariado(): void
    {
        $component = $this->componenteVoluntariado();
        $component->recordId = 1;
        $component->nombre_proyecto = 'Voluntariado comunitario';
        $component->modalidad_id = 1;
        $component->categoria = [1];
        $component->ejes_prioritarios_unah = [1];
        $component->facultades_centros = [1];
        $component->departamentos_academicos = [1];
        $component->carrera_no_aplica = true;
        $component->fecha_inicio = '2026-02-01';
        $component->fecha_finalizacion = '2026-06-30';
        $component->programa_pertenece = 'Programa X';
        $component->lineas_investigacion_academica = 'Línea Y';
        $component->ods = [1];

        $this->assertFalse($component->isStepComplete(1));

        $component->tematica_principal = 'educacion';

        // El FORM-DVUS-015 también exige la meta a la que se contribuye (ítem 30).
        $this->assertFalse($component->isStepComplete(1));

        $component->metasContribuye = ['1'];

        $this->assertTrue($component->isStepComplete(1));
    }

    public function test_costos_indirectos_institucionales_usan_la_tasa_del_3_por_ciento(): void
    {
        $component = new CreateProyectoVinculacion;
        $component->aporte_institucional = [
            ['concepto' => 'gastos_movilizacion', 'cantidad' => 100, 'costo_unitario' => 200, 'costo_total' => 20000, 'editable' => true],
            ['concepto' => 'costos_indirectos_infraestructura', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => false],
            ['concepto' => 'costos_indirectos_servicios', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => false],
        ];

        $method = new \ReflectionMethod(CreateProyectoVinculacion::class, 'recalculateAporteInstitucional');
        $method->setAccessible(true);
        $method->invoke($component);

        $infra = collect($component->aporte_institucional)->firstWhere('concepto', 'costos_indirectos_infraestructura');

        // 3% de L 20,000 (sumatoria del costo total de los conceptos a–e): la fila
        // muestra el porcentaje como cantidad y la sumatoria como costo unitario.
        $this->assertSame(3.0, $infra['cantidad']);
        $this->assertSame(20000.0, $infra['costo_unitario']);
        $this->assertSame(600.0, $infra['costo_total']);
    }

    public function test_resultados_de_mediano_largo_plazo_se_editan_en_su_lista(): void
    {
        $component = new CreateProyectoVinculacion;

        // Cada lista agrega una fila con su plazo; se completa en la misma pantalla.
        $component->addResultadoProyecto('largo_plazo');
        $component->addResultadoProyecto('mediano_plazo');

        $this->assertCount(2, $component->resultadosProyecto);
        $this->assertSame('largo_plazo', $component->resultadosProyecto[0]['plazo']);
        $this->assertSame('mediano_plazo', $component->resultadosProyecto[1]['plazo']);
        $this->assertNotEmpty($component->resultadosProyecto[0]['wire_key']);
        $this->assertNotSame($component->resultadosProyecto[0]['wire_key'], $component->resultadosProyecto[1]['wire_key']);

        // Un plazo que no es de mediano ni largo plazo no agrega nada.
        $component->addResultadoProyecto('corto_plazo');
        $component->addResultadoProyecto('otro');
        $this->assertCount(2, $component->resultadosProyecto);

        $component->removeResultadoProyecto(0);
        $this->assertCount(1, $component->resultadosProyecto);
        $this->assertSame('mediano_plazo', $component->resultadosProyecto[0]['plazo']);
        // Sin sesión no se guarda nada, y el autoguardado lo informa en vez de fallar en silencio.
        $this->assertSame('error', $component->estadoAutoGuardado);
    }

    public function test_una_fila_de_mediano_o_largo_plazo_incompleta_no_deja_avanzar(): void
    {
        $component = $this->componenteVoluntariado();
        $component->currentStep = 7;
        $component->addResultadoProyecto('mediano_plazo');
        $component->resultadosProyecto[0]['nombre_resultado'] = 'Solo el resultado';

        try {
            $component->nextStep();
            $this->fail('Se esperaba ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('resultadosProyecto.0.nombre_indicador', $e->errors());
            $this->assertArrayHasKey('resultadosProyecto.0.nombre_medio_verificacion', $e->errors());
        }

        $this->assertSame(7, $component->currentStep);
    }

    public function test_completitud_de_paso_5_exige_experiencia_en_voluntariado(): void
    {
        $component = $this->componenteVoluntariado();
        $component->recordId = 1;

        foreach (['resumen', 'participacion_unah', 'participacion_contraparte', 'participacion_comunidad',
            'definicion_problema', 'alineamiento_reforma', 'metodologia', 'bibliografia'] as $campo) {
            $component->{$campo} = 'Contenido';
        }

        $this->assertFalse($component->isStepComplete(5));

        $component->experiencia_conocimientos_teoricos = 'a';
        $component->experiencia_habilidades_tecnicas = 'b';
        $component->experiencia_competencias_blandas = 'c';

        $this->assertTrue($component->isStepComplete(5));
    }
}
