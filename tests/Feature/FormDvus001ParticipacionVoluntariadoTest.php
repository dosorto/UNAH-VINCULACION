<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\NivelAcademico;
use App\Models\Proyecto\IntegranteInternacional;
use App\Models\Proyecto\Proyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Sección III del FORM-DVUS-001: el voluntariado de personal de la UNAH (ítem 13) y el
 * internacional (ítem 14) se capturan aparte de los estudiantes (ítem 12) y son opcionales.
 */
class FormDvus001ParticipacionVoluntariadoTest extends TestCase
{
    use DatabaseTransactions;

    private function componente001(): CreateProyectoVinculacion
    {
        $component = new CreateProyectoVinculacion;
        $component->esVoluntariado = false;
        $component->voluntariado_participacion = array_fill_keys(Proyecto::columnasVoluntariadoParticipacion(), null);
        $component->estudiante_proyecto = [[
            'tipo_participacion_estudiante' => 'Voluntariado',
            'carrera_id' => null,
            'asignatura_id' => null,
            'periodo_academico_id' => null,
            'cantidad_estudiantes_hombres' => 3,
            'cantidad_estudiantes_mujeres' => 2,
            'total_estudiantes' => 5,
        ]];

        return $component;
    }

    private function invocar(CreateProyectoVinculacion $component, string $metodo): mixed
    {
        return (new \ReflectionMethod(CreateProyectoVinculacion::class, $metodo))->invoke($component);
    }

    private function textoFicha(Proyecto $proyecto): string
    {
        $html = view('components.fichas.ficha-proyecto-vinculacion', ['proyecto' => $proyecto->fresh(), 'isPdf' => true])->render();

        return preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(preg_replace('#<style.*?</style>#s', '', $html))));
    }

    public function test_paso_2_no_exige_voluntariado_personal_ni_internacional(): void
    {
        $component = $this->componente001();
        $component->recordId = 70;
        $component->currentStep = 2;

        $this->assertTrue($component->isStepComplete(2));

        $component->nextStep();

        $this->assertSame(3, $component->currentStep);
    }

    public function test_paso_2_rechaza_cantidades_negativas(): void
    {
        $component = $this->componente001();
        $component->currentStep = 2;
        $component->voluntariado_participacion['vol_profesores_hora_hombres'] = -1;

        try {
            $component->nextStep();
            $this->fail('Se esperaba ValidationException por una cantidad negativa.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('voluntariado_participacion.vol_profesores_hora_hombres', $e->errors());
            $this->assertStringContainsString('13. Voluntariado personal de la UNAH', $e->errors()['voluntariado_participacion.vol_profesores_hora_hombres'][0]);
        }

        $this->assertSame(2, $component->currentStep);
    }

    public function test_el_001_guarda_vacios_como_cero_y_el_015_los_deja_pendientes(): void
    {
        $component = $this->componente001();
        $component->voluntariado_participacion['vol_personal_administrativo_mujeres'] = '4';

        $datos = $this->invocar($component, 'voluntariadoParticipacionParaGuardar');

        $this->assertSame(4, $datos['vol_personal_administrativo_mujeres']);
        $this->assertSame(0, $datos['vol_int_grado_hombres']);
        $this->assertCount(count(Proyecto::columnasVoluntariadoParticipacion()), $datos);

        $component->esVoluntariado = true;

        $this->assertNull($this->invocar($component, 'voluntariadoParticipacionParaGuardar')['vol_int_grado_hombres']);
    }

    public function test_el_voluntariado_de_estudiantes_se_identifica_en_el_formulario_del_001(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));
        $componente = file_get_contents(app_path('Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php'));

        $this->assertStringContainsString("'Voluntariado' => 'Voluntariado (estudiantes)'", $componente);
        $this->assertStringContainsString('Participación de estudiantes y voluntarios', $vista);
        $this->assertStringContainsString("'Deje en blanco o en 0 si no aplica.'", $vista);
    }

    public function test_ficha_usa_lo_capturado_y_conserva_el_calculo_de_proyectos_anteriores(): void
    {
        $tipoAccionId = DB::table('vinculacion_tipos_accion')->where('codigo', 'DESARROLLO_LOCAL_REGIONAL')->value('id');
        $nivelMaestriaId = NivelAcademico::where('nombre', 'Maestría')->value('id');
        $this->assertNotNull($tipoAccionId, 'Falta el tipo de acción DESARROLLO_LOCAL_REGIONAL en la base de pruebas.');
        $this->assertNotNull($nivelMaestriaId, 'Falta el nivel académico Maestría en la base de pruebas.');

        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Desarrollo local de prueba',
            'tipo_accion_id' => $tipoAccionId,
        ]);
        $docenteInternacional = IntegranteInternacional::create([
            'nombre_completo' => 'Docente Internacional',
            'documento_identidad' => 'PAS-001-PRUEBA',
            'email' => 'docente.internacional@example.com',
            'sexo' => 'femenino',
            'pais' => 'Costa Rica',
            'institucion' => 'Universidad de prueba',
            'nivel_academico_id' => $nivelMaestriaId,
        ]);
        $proyecto->integrantesInternacionales()->attach($docenteInternacional->id);

        $this->assertFalse($proyecto->fresh()->esVoluntariado());

        // Proyecto anterior a la captura (columnas en null): la ficha no cambia.
        $this->assertMatchesRegularExpression('/14\. Voluntariado internacional .*? Hombres Mujeres 0 0 0 1 0 0/', $this->textoFicha($proyecto));

        // Con lo capturado, los ítems 13 y 14 salen del formulario, no del ítem 11.
        $proyecto->update(array_replace(
            array_fill_keys(Proyecto::columnasVoluntariadoParticipacion(), 0),
            ['vol_profesores_hora_hombres' => 2, 'vol_int_doctorado_mujeres' => 3],
        ));

        $texto = $this->textoFicha($proyecto);

        $this->assertMatchesRegularExpression('/13\. Voluntariado personal de la UNAH .*? Hombres Mujeres 2 0 0 0 0 0 0 0/', $texto);
        $this->assertMatchesRegularExpression('/14\. Voluntariado internacional .*? Hombres Mujeres 0 0 0 0 0 3/', $texto);
    }
}
