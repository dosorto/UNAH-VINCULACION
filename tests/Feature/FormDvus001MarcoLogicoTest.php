<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Paso 7 (Marco Lógico) como documento guiado: objetivo general, cada objetivo específico con
 * sus resultados de corto plazo y las listas de mediano y largo plazo, sin tablas ni modales.
 */
class FormDvus001MarcoLogicoTest extends TestCase
{
    use DatabaseTransactions;

    private function resultado(string $nombre, string $plazo = 'corto_plazo', bool $completo = true): array
    {
        return [
            'id' => null, 'wire_key' => $nombre, 'nombre_resultado' => $nombre,
            'nombre_indicador' => $completo ? "Indicador de {$nombre}" : '',
            'nombre_medio_verificacion' => $completo ? "Medio de {$nombre}" : '',
            'plazo' => $plazo,
        ];
    }

    private function htmlPaso7(string $codigoTipoAccion): string
    {
        $tipo = DB::table('vinculacion_tipos_accion')->where('codigo', $codigoTipoAccion)->value('id');
        $this->assertNotNull($tipo, "Falta el tipo de acción {$codigoTipoAccion} en la base de pruebas.");

        return Livewire::actingAs(User::whereHas('empleado')->firstOrFail())
            ->withQueryParams(['tipo_accion_id' => $tipo])
            ->test(CreateProyectoVinculacion::class)
            ->set('objetivo_general', 'Mejorar la producción agrícola de la comunidad')
            ->set('objetivosEspecificos', [
                ['id' => null, 'wire_key' => 'oe-1', 'descripcion' => 'Capacitar a los productores', 'resultados' => [$this->resultado('Productores capacitados')]],
                ['id' => null, 'wire_key' => 'oe-2', 'descripcion' => 'Instalar parcelas demostrativas', 'resultados' => [$this->resultado('Parcelas instaladas', completo: false)]],
            ])
            ->set('resultadosProyecto', [
                $this->resultado('Prácticas adoptadas', 'mediano_plazo'),
                $this->resultado('Rendimiento mejorado', 'largo_plazo'),
            ])
            ->set('currentStep', 7)
            ->html();
    }

    public function test_el_paso_7_se_muestra_como_documento_guiado(): void
    {
        $html = $this->htmlPaso7('DESARROLLO_LOCAL_REGIONAL');

        foreach (['marco-objetivo-general', 'marco-objetivos', 'marco-mediano-largo'] as $seccion) {
            $this->assertStringContainsString("id=\"{$seccion}\"", $html);
        }

        // Todos los objetivos y todos sus campos están a la vista (sin maestro-detalle).
        $this->assertStringContainsString('wire:model.live.debounce.1000ms="objetivosEspecificos.0.descripcion"', $html);
        $this->assertStringContainsString('wire:model.live.debounce.1000ms="objetivosEspecificos.1.resultados.0.nombre_medio_verificacion"', $html);

        // Resumen: el OE 2 tiene un resultado incompleto.
        $this->assertStringContainsString('2 objetivos específicos · 1 por completar', $html);
        $this->assertStringContainsString('Hay un resultado incompleto', $html);

        // Mediano y largo plazo se editan en su lista, enlazados a su índice real.
        $largo = strpos($html, 'c) Impacto que se desea generar en el proyecto');
        $this->assertNotFalse($largo);
        $this->assertLessThan($largo, strpos($html, 'resultadosProyecto.0.nombre_resultado'));
        $this->assertGreaterThan($largo, strpos($html, 'resultadosProyecto.1.nombre_resultado'));
        $this->assertStringContainsString('b) Resultados de mediano plazo', $html);

        foreach (['openResultadoProyectoModal', 'saveResultadoProyecto', 'selectObjetivo', 'Corto plazo</span>'] as $retirado) {
            $this->assertStringNotContainsString($retirado, $html);
        }
    }

    public function test_el_015_conserva_sus_etiquetas(): void
    {
        $html = $this->htmlPaso7('VOLUNTARIADO');

        $this->assertStringContainsString('b) Indicadores de mediano plazo', $html);
        $this->assertStringContainsString('c) Impacto que se desea generar', $html);
        $this->assertStringContainsString('Registre al menos uno de cada plazo.', $html);
    }

    public function test_agregar_un_objetivo_lo_enfoca_y_eliminar_conserva_al_menos_uno(): void
    {
        $tipo = DB::table('vinculacion_tipos_accion')->where('codigo', 'DESARROLLO_LOCAL_REGIONAL')->value('id');

        Livewire::actingAs(User::whereHas('empleado')->firstOrFail())
            ->withQueryParams(['tipo_accion_id' => $tipo])
            ->test(CreateProyectoVinculacion::class)
            ->call('addObjetivo')
            ->assertDispatched('marco-logico-enfocar', objetivo: 1);

        $component = new CreateProyectoVinculacion;
        $component->objetivosEspecificos = [['id' => null, 'wire_key' => 'unico', 'descripcion' => 'Único', 'resultados' => []]];

        $component->removeObjetivo(0);

        $this->assertCount(1, $component->objetivosEspecificos);
        $this->assertSame('', $component->objetivosEspecificos[0]['descripcion']);
    }
}
