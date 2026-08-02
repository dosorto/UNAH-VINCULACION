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
        $this->assertStringNotContainsString("quitarFila('resultados'", $partial);
        $this->assertStringContainsString('wire:confirm="¿Eliminar esta actividad?"', $partial);
        $this->assertStringContainsString('Agregar resultado', $partial);
        $this->assertStringContainsString('Agregar actividad ejecutada', $partial);
        $this->assertStringNotContainsString('Editar resultado', $partial);
        $this->assertStringNotContainsString('aria-label="Editar actividad"', $partial);
        $this->assertStringNotContainsString('openActividadModal({{ $i }}, true)', $partial);
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

        $this->assertStringContainsString('wire:click="openResultadoModal({{ $i }})"', $partial);
        $this->assertStringNotContainsString('openResultadoModal({{ $i }}, true)', $partial);
        $this->assertStringContainsString('foreach ([\'resultado_esperado_id\', \'objetivo_especifico\'', $component);
    }

    public function test_el_periodo_de_actividad_se_presenta_en_formato_dia_mes_anio(): void
    {
        $component = new EditInformeFinalProyecto;

        $this->assertSame('09/11/2026', $component->formatearPeriodoActividad('2026-11-09', '2026-11-09'));
        $this->assertSame('09/11/2026 – 13/11/2026', $component->formatearPeriodoActividad('2026-11-09', '2026-11-13'));
        $this->assertSame('09/11/2026', $component->formatearPeriodoActividad('2026-11-09', null));
        $this->assertSame('No registrado', $component->formatearPeriodoActividad(null, null));
    }

    public function test_limpia_filas_de_cooperacion_completamente_vacias(): void
    {
        $component = new EditInformeFinalProyecto;
        $method = new \ReflectionMethod($component, 'limpiarFilasCooperacionVacias');

        $component->cooperacion = [
            ['nombre' => '', 'pasaporte' => ' ', 'correo' => '', 'pais' => '', 'universidad' => '', 'horas_dedicadas' => 0],
            ['nombre' => '  ', 'pasaporte' => '', 'correo' => '', 'pais' => '', 'universidad' => '', 'horas_dedicadas' => 0],
            ['nombre' => 'Ana', 'pasaporte' => '', 'correo' => '', 'pais' => '', 'universidad' => '', 'horas_dedicadas' => 0],
            ['nombre' => '', 'pasaporte' => '', 'correo' => '', 'pais' => '', 'universidad' => '', 'horas_dedicadas' => 2],
        ];

        $method->invoke($component);

        $this->assertCount(2, $component->cooperacion);
        $this->assertSame('Ana', $component->cooperacion[0]['nombre']);
        $this->assertSame(2, $component->cooperacion[1]['horas_dedicadas']);
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

    public function test_reflexion_respeta_el_orden_del_registro_en_una_distribucion_vertical(): void
    {
        $view = file_get_contents(resource_path('views/livewire/proyectos/informe-final/edit-informe-final-proyecto.blade.php'));
        $inicio = strpos($view, 'Reflexión, transformación y sostenibilidad');
        $fin = strpos($view, 'Objetivos de Desarrollo Sostenible', $inicio);
        $seccion = substr($view, $inicio, $fin - $inicio);
        $campos = ['dificultades', 'acciones_dificultades', 'lecciones_aprendidas', 'buenas_practicas', 'problema_inicial', 'transformacion_lograda', 'mecanismos_sostenibilidad', 'acciones_contraparte_sostenibilidad', 'desafios', 'respuesta_reforma_universitaria', 'recomendaciones', 'bibliografia'];

        $this->assertStringContainsString('mt-5 space-y-4', $seccion);
        $this->assertStringNotContainsString('md:grid-cols-2', $seccion);
        $this->assertStringContainsString('<div class="w-full"><label class="{{ $label }}">{{ $name }}</label><textarea rows="4" wire:model.live.debounce.1000ms="general.{{ $field }}" @readonly($this->esCampoReflexionHeredado($field))', $seccion);
        $this->assertStringNotContainsString('@disabled($this->esCampoReflexionHeredado($field))', $seccion);
        $posicionAnterior = -1;
        foreach ($campos as $campo) {
            $posicion = strpos($seccion, "'{$campo}'");
            $this->assertNotFalse($posicion);
            $this->assertGreaterThan($posicionAnterior, $posicion);
            $this->assertStringContainsString("general.{{ \$field }}", $seccion);
            $posicionAnterior = $posicion;
        }
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
