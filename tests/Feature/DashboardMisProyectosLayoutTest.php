<?php

namespace Tests\Feature;

use App\Livewire\Inicio\Dashboards\DasboardDocente;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DashboardMisProyectosLayoutTest extends TestCase
{
    public function test_docente_limita_el_nombre_y_prioriza_el_ancho_del_progreso(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/inicio/dashboards/dasboard-docente.blade.php'));

        $this->assertStringContainsString('<col class="w-[18%]">', $vista);
        $this->assertStringContainsString('<col class="w-[38%]">', $vista);
        $this->assertStringContainsString('<x-dashboard.texto-truncado :texto="$formulario[\'nombre\']" />', $vista);
        $this->assertStringContainsString('<x-dashboard.stepper-progreso :stepper="$formulario[\'stepper\']" />', $vista);
    }

    public function test_director_limita_el_nombre_y_usa_el_stepper_compartido(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/inicio/dashboards/dashboard-director.blade.php'));

        $this->assertStringContainsString('<col class="w-[19%]">', $vista);
        $this->assertStringContainsString('<col class="w-[41%]">', $vista);
        $this->assertStringContainsString('<x-dashboard.texto-truncado :texto="$proyecto->nombre_proyecto" />', $vista);
        $this->assertStringContainsString('<x-dashboard.stepper-progreso :stepper="$stepperDirector" />', $vista);
    }

    public function test_etapas_del_flujo_tienen_tooltip_con_nombre_y_estado(): void
    {
        $stepper = file_get_contents(resource_path('views/components/dashboard/stepper-progreso.blade.php'));

        $this->assertStringContainsString('role="tooltip"', $stepper);
        $this->assertStringContainsString("\$tooltip = \$paso['nombre'].' — '.\$estadoEtiqueta;", $stepper);
        $this->assertStringContainsString('group-hover:visible', $stepper);
        $this->assertStringContainsString('group-focus:visible', $stepper);
    }

    public function test_nombre_truncado_conserva_el_texto_completo_en_tooltip(): void
    {
        $componente = file_get_contents(resource_path('views/components/dashboard/texto-truncado.blade.php'));

        $this->assertStringContainsString('class="block truncate"', $componente);
        $this->assertStringContainsString('title="{{ $texto }}"', $componente);
        $this->assertStringContainsString('role="tooltip"', $componente);
    }

    public function test_proyecto_sin_firmas_del_flujo_muestra_sin_enviar(): void
    {
        $filas = collect([
            ['etapa' => (object) ['nombre' => 'Revisión'], 'firma' => null, 'adoptada_antes' => false],
            ['etapa' => (object) ['nombre' => 'Aprobación'], 'firma' => null, 'adoptada_antes' => false],
        ]);

        $this->assertSame([], $this->stepperEstados($filas));
    }

    public function test_proyecto_con_firma_del_flujo_muestra_sus_etapas(): void
    {
        $filas = collect([
            [
                'etapa' => (object) ['nombre' => 'Revisión'],
                'firma' => (object) ['estado_revision' => 'Pendiente'],
                'adoptada_antes' => false,
            ],
            ['etapa' => (object) ['nombre' => 'Aprobación'], 'firma' => null, 'adoptada_antes' => false],
        ]);

        $stepper = $this->stepperEstados($filas);

        $this->assertCount(2, $stepper);
        $this->assertSame('actual', $stepper[0]['estado']);
        $this->assertSame('pendiente', $stepper[1]['estado']);
    }

    public function test_director_solo_carga_firmas_asociadas_a_un_flujo_y_una_etapa(): void
    {
        $componente = file_get_contents(app_path('Livewire/Inicio/Dashboards/DashboardDirector.php'));

        $this->assertStringContainsString("->whereNotNull('flujo_aprobacion_id')", $componente);
        $this->assertStringContainsString("->whereNotNull('flujo_aprobacion_etapa_id')", $componente);
    }

    private function stepperEstados(Collection $filas): array
    {
        $metodo = new \ReflectionMethod(DasboardDocente::class, 'stepperEstados');
        $metodo->setAccessible(true);

        return $metodo->invoke(new DasboardDocente, $filas);
    }
}
