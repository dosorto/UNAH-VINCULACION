<?php

namespace Tests\Unit;

use App\Livewire\Proyectos\Vinculacion\CreatePasantia;
use Tests\TestCase;

class PasantiaAnexosTest extends TestCase
{
    public function test_check_depends_on_saved_document_and_modal_renders(): void
    {
        $component = new CreatePasantia;
        $component->mount();
        $this->assertFalse($component->isStepComplete(7));
        $component->form['adjunta_carta_formalizacion'] = 'Sí';
        $this->assertFalse($component->isStepComplete(7));
        $component->form['archivo_convenio_marco'] = 'pasantias/convenio.pdf';
        $this->assertTrue($component->isStepComplete(7));
        $html = view('livewire.proyectos.vinculacion.partials.pasantia-anexos', [
            'errors' => new \Illuminate\Support\ViewErrorBag,
            'form' => $component->form,
            'showAnexoModal' => true,
            'nuevoAnexoTipo' => '',
            'nuevoAnexoArchivo' => null,
        ])->render();
        $this->assertStringContainsString('convenio.pdf', $html);
        $this->assertStringContainsString('Carta de formalización', $html);
        $this->assertStringContainsString('Seleccione o suelte su documento aquí', $html);
    }
}
