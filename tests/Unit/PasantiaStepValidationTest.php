<?php

namespace Tests\Unit;

use App\Livewire\Proyectos\Vinculacion\CreatePasantia;
use Tests\TestCase;

class PasantiaStepValidationTest extends TestCase
{
    public function test_attachments_are_the_last_step_and_form_cannot_replace_signatures(): void
    {
        $this->assertCount(7, CreatePasantia::PASOS);
        $this->assertSame('Adjuntos', CreatePasantia::PASOS[7]);
        $this->assertNotContains('Firmas', CreatePasantia::PASOS);
        $component = new class extends CreatePasantia
        {
            public function payloadFor(array $payload): array
            {
                return $this->normalizarPayload($payload);
            }
            public function attachmentsRules(): array
            {
                return $this->reglasPaso(7);
            }
        };
        $component->mount();
        $this->assertArrayHasKey('cartaFormalizacionArchivo', $component->attachmentsRules());
        $this->assertSame(['nombre_estudiante' => 'Ana'], $component->payloadFor([
            'nombre_estudiante' => 'Ana',
            'nombre_firma_coordinador' => 'Otro firmante',
            'firma_coordinador' => 'otra.png',
            'firma_supervisor' => 'otra.png',
            'firma_estudiante' => 'otra.png',
        ]));
    }

    public function test_experience_headings_are_not_required_fields_or_pdf_values(): void
    {
        $component = new class extends CreatePasantia
        {
            public function experienceRules(): array
            {
                return $this->reglasPasoCompleto(3);
            }
        };
        $component->mount();
        $component->form = array_replace($component->form, [
            'resumen_responsabilidades' => 'Desarrollar aplicaciones y documentar procesos.',
            'area_departamento' => 'Tecnología',
            'descripcion_conocimientos_teoricos' => 'Programación y bases de datos.',
            'habilidades_desarrollar' => 'Análisis y comunicación.',
            'pasantia_remunerada' => 'No',
        ]);
        $this->assertTrue(validator(['form' => $component->form], $component->experienceRules())->passes());

        $registro = new \App\Models\Pasantia([
            'descripcion_experiencia' => 'LEGACY-EXPERIENCIA',
            'descripcion_cargo' => 'LEGACY-CARGO',
            'area_conocimiento' => 'LEGACY-AREA',
        ]);
        $html = view('pdf.pasantias.form-013', [
            'registro' => $registro,
            'secciones' => app(\App\Services\Pasantias\PasantiaPdfGenerator::class)->seccionesFormulario(),
        ])->render();
        foreach (['LEGACY-EXPERIENCIA', 'LEGACY-CARGO', 'LEGACY-AREA'] as $value) {
            $this->assertStringNotContainsString($value, $html);
        }
        foreach (['a) Descripción del cargo', 'b) Área de conocimiento que se aplicará', 'c) Habilidades que se desarrollarán', 'd) Compensación'] as $heading) {
            $this->assertStringContainsString('<th colspan="2">'.$heading.'</th>', $html);
        }
    }

    public function test_unpaid_internship_clears_amount_in_form_and_autosave_payload(): void
    {
        $component = new class extends CreatePasantia
        {
            public function payloadFor(array $payload): array
            {
                return $this->normalizarPayload($payload);
            }
        };
        $component->mount();
        $component->autoguardadoActivo = false;
        $component->form['pasantia_remunerada'] = 'No';
        $component->form['monto_remuneracion'] = 500;
        $component->updatedForm('No', 'pasantia_remunerada');

        $this->assertNull($component->form['monto_remuneracion']);
        $payload = $component->payloadFor(['pasantia_remunerada' => 'No']);
        $this->assertArrayHasKey('monto_remuneracion', $payload);
        $this->assertNull($payload['monto_remuneracion']);
        $this->assertFalse($payload['pasantia_remunerada']);
        $this->assertNull($component->payloadFor(['monto_remuneracion' => 500])['monto_remuneracion']);

        $component->form['pasantia_remunerada'] = 'Sí';
        $this->assertSame(500, $component->payloadFor(['monto_remuneracion' => 500])['monto_remuneracion']);
    }

    public function test_subject_list_preserves_legacy_subject_and_can_remove_it(): void
    {
        $component = new class extends CreatePasantia
        {
            public function payloadFor(array $payload): array
            {
                return $this->normalizarPayload($payload);
            }
        };
        $component->mount();
        $component->autoguardadoActivo = false;
        $component->form['codigo_asignatura'] = 'IS-410';
        $component->form['nombre_asignatura'] = 'Programación';
        $this->assertSame([['codigo' => 'IS-410', 'nombre' => 'Programación']], $component->asignaturasSeleccionadas());
        $component->quitarAsignatura(0);
        $this->assertSame([], $component->asignaturasSeleccionadas());
        $payload = $component->payloadFor(['asignaturas' => []]);
        $this->assertNull($payload['codigo_asignatura']);
        $this->assertNull($payload['nombre_asignatura']);
        $this->assertSame([], $payload['asignaturas']);
    }

    public function test_empty_steps_fail_navigation_but_allow_drafts(): void
    {
        $component = new class extends CreatePasantia
        {
            public function rulesFor(int $step, bool $complete): array
            {
                return $complete ? $this->reglasPasoCompleto($step) : $this->reglasPaso($step);
            }
        };
        $component->mount();

        foreach (range(1, 6) as $step) {
            $this->assertTrue(validator(['form' => $component->form], $component->rulesFor($step, true))->fails());
            $this->assertTrue(validator(['form' => $component->form], $component->rulesFor($step, false))->passes());
        }
    }

    public function test_conditional_amounts_and_official_options(): void
    {
        $component = new class extends CreatePasantia
        {
            public function rulesFor(int $step): array
            {
                return $this->reglasPasoCompleto($step);
            }
        };
        $component->mount();
        foreach ([2 => ['otorga_creditos', 'cantidad_creditos'], 3 => ['pasantia_remunerada', 'monto_remuneracion']] as $step => [$answer, $amount]) {
            foreach (['Sí' => true, 'No' => false] as $value => $required) {
                $component->form[$answer] = $value;
                $errors = validator(['form' => $component->form], $component->rulesFor($step))->errors();
                $this->assertSame($required, $errors->has('form.'.$amount));
            }
        }
        foreach (CreatePasantia::OPCIONES_FORMULARIO as $field => $options) {
            $step = in_array($field, ['tipo_pasantia', 'modalidad_ejecucion']) ? 2 : 4;
            foreach ($options as $option) {
                $component->form[$field] = $option;
                $errors = validator(['form' => $component->form], $component->rulesFor($step))->errors();
                $this->assertFalse($errors->has('form.'.$field), $option);
            }
        }
    }
}
