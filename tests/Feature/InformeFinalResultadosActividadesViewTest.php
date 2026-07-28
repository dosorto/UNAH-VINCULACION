<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\InformeFinal\EditInformeFinalProyecto;
use Tests\TestCase;

class InformeFinalResultadosActividadesViewTest extends TestCase
{
    public function test_el_paso_cinco_usa_las_secciones_independientes_de_resultados_y_actividades(): void
    {
        $view = file_get_contents(resource_path('views/livewire/proyectos/informe-final/edit-informe-final-proyecto.blade.php'));
        $partial = file_get_contents(resource_path('views/livewire/proyectos/informe-final/partials/resultados-actividades.blade.php'));

        $this->assertStringContainsString("@include('livewire.proyectos.informe-final.partials.resultados-actividades')", $view);
        $this->assertStringContainsString('No hay resultados registrados.', $partial);
        $this->assertStringContainsString('No hay actividades ejecutadas registradas.', $partial);
        $this->assertStringContainsString('wire:confirm="¿Eliminar este resultado?"', $partial);
        $this->assertStringContainsString('wire:confirm="¿Eliminar esta actividad?"', $partial);
    }

    public function test_los_modales_exponen_las_acciones_y_campos_de_las_colecciones_existentes(): void
    {
        $component = file_get_contents(app_path('Livewire/Proyectos/InformeFinal/EditInformeFinalProyecto.php'));
        $partial = file_get_contents(resource_path('views/livewire/proyectos/informe-final/partials/resultados-actividades.blade.php'));

        foreach (['openResultadoModal', 'guardarResultadoModal', 'openActividadModal', 'guardarActividadModal'] as $method) {
            $this->assertStringContainsString('function '.$method, $component);
        }

        foreach (['objetivo_especifico', 'resultado_planificado', 'producto_logrado', 'actividad_planificada', 'actividad_realizada', 'medio_verificacion'] as $field) {
            $this->assertStringContainsString($field, $partial);
        }
    }

    public function test_el_periodo_de_actividad_se_presenta_en_formato_dia_mes_anio(): void
    {
        $component = new EditInformeFinalProyecto;

        $this->assertSame('09/11/2026', $component->formatearPeriodoActividad('2026-11-09', '2026-11-09'));
        $this->assertSame('09/11/2026 – 13/11/2026', $component->formatearPeriodoActividad('2026-11-09', '2026-11-13'));
        $this->assertSame('09/11/2026', $component->formatearPeriodoActividad('2026-11-09', null));
        $this->assertSame('No registrado', $component->formatearPeriodoActividad(null, null));
    }

    public function test_el_stepper_no_marca_completo_un_paso_con_registros_parciales(): void
    {
        $component = new EditInformeFinalProyecto;
        $component->resultados = [['resultado_planificado' => 'Resultado', 'porcentaje_cumplimiento' => 100]];
        $component->actividades = [];

        $this->assertFalse($component->isStepComplete(5));

        $component->actividades = [['actividad_planificada' => 'Actividad', 'estado' => 'ejecutada', 'fecha_inicial' => '2026-11-09', 'fecha_final' => '2026-11-13']];

        $this->assertTrue($component->isStepComplete(5));
    }

    public function test_el_documento_inf001_no_contiene_marca_de_agua_borrador_y_resuelve_la_ultima_decision(): void
    {
        $documento = file_get_contents(resource_path('views/proyectos/informe-final/partials/inf-001-document.blade.php'));
        $generador = file_get_contents(app_path('Services/InformeFinal/InformeFinalPdfGenerator.php'));

        $this->assertStringNotContainsString('inf-draft-watermark', $documento);
        $this->assertStringNotContainsString('>BORRADOR<', $documento);
        $this->assertStringContainsString("->groupBy('flujo_aprobacion_etapa_id')", $generador);
        $this->assertStringContainsString("->filter(fn (FirmaProyecto \$firma) => \$firma->estado_revision === 'Aprobado')", $generador);
    }

}
