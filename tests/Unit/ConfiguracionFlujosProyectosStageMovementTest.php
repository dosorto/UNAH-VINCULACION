<?php

namespace Tests\Unit;

use App\Livewire\Configuracion\Flujos\ConfiguracionFlujosProyectos;
use Tests\TestCase;

use function Livewire\store;

class ConfiguracionFlujosProyectosStageMovementTest extends TestCase
{
    public function test_mover_etapa_de_proyecto_emite_el_evento_para_animar_la_tarjeta_correcta(): void
    {
        $component = new ConfiguracionFlujosProyectos;
        $component->activeFlowTab = 'proyectos';
        $component->stages = [
            ['ui_key' => 'project-one', 'codigo' => 'ONE'],
            ['ui_key' => 'project-two', 'codigo' => 'TWO'],
        ];

        $component->moveStageUp(1);

        $this->assertSame(['project-two', 'project-one'], array_column($component->stages, 'ui_key'));
        $this->assertSame([
            'name' => 'workflow-stage-moved',
            'params' => ['list' => 'project', 'stageKey' => 'project-two'],
        ], store($component)->get('dispatched')[0]->serialize());
    }

    public function test_mover_etapa_de_programa_emite_el_evento_para_animar_la_tarjeta_correcta(): void
    {
        $component = new ConfiguracionFlujosProyectos;
        $component->activeFlowTab = 'programas';
        $component->programStages = [
            ['ui_key' => 'program-one', 'codigo' => 'ONE'],
            ['ui_key' => 'program-two', 'codigo' => 'TWO'],
        ];

        $component->moveStageDown(0);

        $this->assertSame(['program-two', 'program-one'], array_column($component->programStages, 'ui_key'));
        $this->assertSame([
            'name' => 'workflow-stage-moved',
            'params' => ['list' => 'program', 'stageKey' => 'program-one'],
        ], store($component)->get('dispatched')[0]->serialize());
    }

    public function test_los_campos_se_actualizan_por_clave_sin_contaminar_otras_etapas(): void
    {
        $component = new ConfiguracionFlujosProyectos;
        $component->activeFlowTab = 'proyectos';
        $component->stages = [
            [
                'ui_key' => 'project-one',
                'codigo' => 'ONE',
                'nombre' => 'Etapa uno',
                'tipo_etapa' => 'REVISION',
                'cargo_firma_id' => '1',
                'rol_revisor_id' => '10',
                'usuario_responsable_id' => '',
            ],
            [
                'ui_key' => 'project-two',
                'codigo' => 'TWO',
                'nombre' => 'Etapa dos',
                'tipo_etapa' => 'REVISION',
                'cargo_firma_id' => '2',
                'rol_revisor_id' => '20',
                'usuario_responsable_id' => '',
            ],
        ];

        $component->moveStageUp(1);
        $component->updateStageField('stages', 'project-two', 'nombre', 'Etapa dos modificada');
        $component->updateStageField('stages', 'project-two', 'tipo_etapa', 'APROBACION');
        $component->updateStageField('stages', 'project-two', 'cargo_firma_id', '9');
        $component->updateStageField('stages', 'project-two', 'rol_revisor_id', '22');
        $component->updateStageField('stages', 'project-two', 'usuario_responsable_id', '33');

        $stagesByKey = collect($component->stages)->keyBy('ui_key');

        $this->assertSame('Etapa dos modificada', $stagesByKey['project-two']['nombre']);
        $this->assertSame('APROBACION', $stagesByKey['project-two']['tipo_etapa']);
        $this->assertSame('9', $stagesByKey['project-two']['cargo_firma_id']);
        $this->assertSame('22', $stagesByKey['project-two']['rol_revisor_id']);
        $this->assertSame('33', $stagesByKey['project-two']['usuario_responsable_id']);
        $this->assertSame('Etapa uno', $stagesByKey['project-one']['nombre']);
        $this->assertSame('REVISION', $stagesByKey['project-one']['tipo_etapa']);
        $this->assertSame('1', $stagesByKey['project-one']['cargo_firma_id']);
        $this->assertSame('10', $stagesByKey['project-one']['rol_revisor_id']);
        $this->assertSame('', $stagesByKey['project-one']['usuario_responsable_id']);
    }

    public function test_las_etapas_de_programa_tambien_mantienen_sus_campos_independientes(): void
    {
        $component = new ConfiguracionFlujosProyectos;
        $component->activeFlowTab = 'programas';
        $component->programStages = [
            [
                'ui_key' => 'program-one',
                'codigo' => 'ONE',
                'nombre' => 'Programa uno',
                'tipo_etapa' => 'REVISION',
                'cargo_firma_id' => '1',
            ],
            [
                'ui_key' => 'program-two',
                'codigo' => 'TWO',
                'nombre' => 'Programa dos',
                'tipo_etapa' => 'REVISION',
                'cargo_firma_id' => '2',
            ],
        ];

        $component->moveStageDown(0);
        $component->updateStageField('programStages', 'program-one', 'nombre', 'Programa uno modificado');
        $component->updateStageField('programStages', 'program-one', 'tipo_etapa', 'APROBACION');
        $component->updateStageField('programStages', 'program-one', 'cargo_firma_id', '9');

        $stagesByKey = collect($component->programStages)->keyBy('ui_key');

        $this->assertSame('Programa uno modificado', $stagesByKey['program-one']['nombre']);
        $this->assertSame('APROBACION', $stagesByKey['program-one']['tipo_etapa']);
        $this->assertSame('9', $stagesByKey['program-one']['cargo_firma_id']);
        $this->assertSame('Programa dos', $stagesByKey['program-two']['nombre']);
        $this->assertSame('REVISION', $stagesByKey['program-two']['tipo_etapa']);
        $this->assertSame('2', $stagesByKey['program-two']['cargo_firma_id']);
    }
}
