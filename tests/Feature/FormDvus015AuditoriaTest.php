<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\Personal\CategoriaEmpleado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\AporteInstitucional;
use App\Models\Proyecto\Proyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Correcciones de la auditoría del FORM-DVUS-015 contra el formato oficial:
 * campos obligatorios, validación completa al enviar y un PDF igual al formato.
 */
class FormDvus015AuditoriaTest extends TestCase
{
    use DatabaseTransactions;

    private function componenteVoluntariado(): CreateProyectoVinculacion
    {
        $component = new CreateProyectoVinculacion;
        $component->esVoluntariado = true;
        $component->voluntariado_participacion = array_fill_keys(Proyecto::columnasVoluntariadoParticipacion(), null);

        return $component;
    }

    private function invocar(CreateProyectoVinculacion $component, string $metodo, ...$argumentos): mixed
    {
        $method = new \ReflectionMethod(CreateProyectoVinculacion::class, $metodo);

        return $method->invoke($component, ...$argumentos);
    }

    private function erroresDe(callable $accion): array
    {
        try {
            $accion();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        $this->fail('Se esperaba ValidationException.');
    }

    public function test_saltar_pasos_con_la_barra_valida_los_pasos_intermedios(): void
    {
        $component = $this->componenteVoluntariado();
        $component->recordId = 1;
        $component->currentStep = 4;
        $component->actividades = [[
            'descripcion' => 'Jornada comunitaria',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '2026-10-01',
            'fecha_finalizacion' => '2026-10-02',
            'horas' => 4,
            'empleados' => [1],
        ]];

        $errores = $this->erroresDe(fn () => $component->goToStep(9));

        // El paso 5 (formulación) está vacío: el formulario se queda ahí.
        $this->assertSame(5, $component->currentStep);
        $this->assertArrayHasKey('resumen', $errores);
    }

    public function test_enviar_revalida_todos_los_pasos_desde_el_primero(): void
    {
        $component = $this->componenteVoluntariado();
        $component->recordId = 1;
        $component->currentStep = 9;

        $errores = $this->erroresDe(fn () => $this->invocar($component, 'validarFormularioAntesDeEnviar'));

        $this->assertSame(1, $component->currentStep);
        $this->assertArrayHasKey('nombre_proyecto', $errores);
        $this->assertArrayHasKey('tematica_principal', $errores);
    }

    public function test_paso_2_exige_voluntariado_personal_e_internacional(): void
    {
        $component = $this->componenteVoluntariado();
        $component->currentStep = 2;
        $component->estudiante_proyecto = [[
            'tipo_participacion_estudiante' => 'Voluntariado',
            'carrera_id' => null,
            'asignatura_id' => null,
            'periodo_academico_id' => null,
            'cantidad_estudiantes_hombres' => 3,
            'cantidad_estudiantes_mujeres' => 2,
            'total_estudiantes' => 5,
        ]];

        $errores = $this->erroresDe(fn () => $component->nextStep());

        $this->assertSame(2, $component->currentStep);
        foreach (Proyecto::columnasVoluntariadoParticipacion() as $columna) {
            $this->assertArrayHasKey("voluntariado_participacion.$columna", $errores);
        }

        // 0 es una respuesta válida: lo obligatorio es registrarlo.
        $component->voluntariado_participacion = array_fill_keys(Proyecto::columnasVoluntariadoParticipacion(), 0);
        $component->nextStep();

        $this->assertSame(3, $component->currentStep);
    }

    public function test_paso_3_exige_los_compromisos_de_cada_contraparte(): void
    {
        $component = $this->componenteVoluntariado();
        $component->currentStep = 3;
        $component->entidad_contraparte = [[
            'entidad_contraparte_id' => 1,
            'nombre' => 'Universidad Internacional',
            'tipo_entidad' => 'internacional',
            'descripcion_acuerdos' => '',
            'instrumento_formalizacion' => [[
                'id' => 10,
                'tipo_documento' => 'convenio_marco',
                'documento_url' => 'proyectos/contrapartes/instrumentos/convenio.pdf',
                'nombre_archivo' => 'convenio.pdf',
                'documento_file' => null,
            ]],
        ]];

        $component->nextStep();

        $this->assertSame(3, $component->currentStep);
        $this->assertStringContainsString('compromisos', $component->getErrorBag()->first('entidad_contraparte.0'));

        $component->entidad_contraparte[0]['descripcion_acuerdos'] = 'Aporta el transporte y el espacio físico.';
        $component->nextStep();

        $this->assertSame(4, $component->currentStep);
    }

    public function test_modal_de_contraparte_exige_compromisos_en_el_015(): void
    {
        $component = $this->componenteVoluntariado();
        $component->nuevaContraparte = [
            'rtn' => '', 'nombre' => 'Alcaldía', 'tipo_entidad' => 'gobierno_municipal',
            'nombre_contacto' => '', 'cargo_contacto' => '', 'telefono' => '', 'correo' => '',
            'descripcion_acuerdos' => '',
            'instrumento_formalizacion' => [[
                'id' => null, 'tipo_documento' => 'carta_intenciones',
                'documento_url' => 'proyectos/contrapartes/instrumentos/carta.pdf',
                'nombre_archivo' => 'carta.pdf', 'documento_file' => null,
            ]],
        ];

        $errores = $this->erroresDe(fn () => $component->saveContraparte());

        $this->assertArrayHasKey('nuevaContraparte.descripcion_acuerdos', $errores);
        $this->assertSame([], $component->entidad_contraparte);
    }

    public function test_paso_6_exige_metodologia_de_seguimiento_y_beneficiarios_directos(): void
    {
        $component = $this->componenteVoluntariado();
        $component->currentStep = 6;

        $errores = $this->erroresDe(fn () => $component->nextStep());
        $this->assertArrayHasKey('metodologia_seguimiento', $errores);

        $component->hombres = 0;
        $component->mujeres = 0;
        $this->invocar($component, 'validacionesVoluntariadoPaso', 6);
        $this->assertTrue($component->getErrorBag()->has('hombres'));

        $component->resetErrorBag();
        $component->mujeres = 12;
        $this->invocar($component, 'validacionesVoluntariadoPaso', 6);
        $this->assertFalse($component->getErrorBag()->has('hombres'));
    }

    public function test_paso_7_exige_resultados_de_mediano_y_largo_plazo(): void
    {
        $component = $this->componenteVoluntariado();
        $component->currentStep = 7;
        $component->objetivo_general = 'Objetivo general';
        $component->objetivosEspecificos = [[
            'descripcion' => 'Objetivo específico',
            'resultados' => [[
                'nombre_resultado' => 'Resultado', 'nombre_indicador' => 'Indicador',
                'nombre_medio_verificacion' => 'Medio', 'plazo' => 'corto_plazo',
            ]],
        ]];
        $component->resultadosProyecto = [[
            'nombre_resultado' => 'Efecto', 'nombre_indicador' => 'Indicador',
            'nombre_medio_verificacion' => 'Medio', 'plazo' => 'mediano_plazo',
        ]];

        $errores = $this->erroresDe(fn () => $component->nextStep());

        $this->assertSame(7, $component->currentStep);
        $this->assertCount(1, $errores['resultadosProyecto']);
        $this->assertStringContainsString('largo plazo', $errores['resultadosProyecto'][0]);
    }

    public function test_seccion_vi_exige_al_menos_un_espacio_completo(): void
    {
        $component = $this->componenteVoluntariado();
        $component->espacios_institucionales = [[
            'id' => null, 'descripcion' => '', 'ubicacion' => '', 'unidad_gestora' => '', 'tiempo_uso_horas' => '',
        ]];
        $reglas = fn () => $component->validate($this->invocar($component, 'rulesVoluntariadoPaso', 9));

        $errores = $this->erroresDe($reglas);
        foreach (['descripcion', 'ubicacion', 'unidad_gestora', 'tiempo_uso_horas'] as $campo) {
            $this->assertArrayHasKey("espacios_institucionales.0.$campo", $errores);
        }

        $component->espacios_institucionales = [];
        $this->assertArrayHasKey('espacios_institucionales', $this->erroresDe($reglas));

        $component->espacios_institucionales = [[
            'id' => null, 'descripcion' => 'Auditorio', 'ubicacion' => 'Edificio F1',
            'unidad_gestora' => 'Facultad de Ciencias', 'tiempo_uso_horas' => '0',
        ]];
        $this->assertArrayHasKey('espacios_institucionales.0.tiempo_uso_horas', $this->erroresDe($reglas));

        $component->espacios_institucionales[0]['tiempo_uso_horas'] = '6';
        $component->resetErrorBag();
        $reglas();
        $this->assertTrue($component->getErrorBag()->isEmpty());
    }

    public function test_item_12_solo_admite_docentes_permanentes(): void
    {
        $component = $this->componenteVoluntariado();
        $empleado = fn (string $tipo, ?string $categoria) => tap(new Empleado(['tipo_empleado' => $tipo]))
            ->setRelation('categoria', $categoria ? new CategoriaEmpleado(['nombre' => $categoria]) : null);

        $this->assertTrue($this->invocar($component, 'esDocentePermanente', $empleado('docente', 'Titular III')));
        $this->assertTrue($this->invocar($component, 'esDocentePermanente', $empleado('docente', 'Auxiliar')));
        $this->assertFalse($this->invocar($component, 'esDocentePermanente', $empleado('docente', 'Profesores x hora')));
        $this->assertFalse($this->invocar($component, 'esDocentePermanente', $empleado('docente', null)));
        $this->assertFalse($this->invocar($component, 'esDocentePermanente', $empleado('administrativo', 'Administrativo')));
    }

    public function test_los_cuatro_cuadros_de_firma_tienen_el_mismo_tamano(): void
    {
        $partial = file_get_contents(resource_path('views/components/fichas/firmas-fijas-proyecto.blade.php'));
        $estilosPdf = file_get_contents(resource_path('views/components/fichas/partials/form-dvus-001-pdf-styles.blade.php'));

        // Columnas fijas: cada cuadro ocupa el 50% de su fila sin importar el largo del título.
        $this->assertStringContainsString('class="table_datos4 signature-table"', $partial);
        $this->assertStringContainsString('<col style="width: 16%;">', $partial);
        $this->assertStringContainsString('<col style="width: 34%;">', $partial);

        // Alto fijo por fila en el PDF. Los selectores deben ganarle a
        // ".table_datos4 td { height: auto !important }" de la ficha; si no, DomPDF colapsa
        // el área de firma de los cuadros aún sin firmar.
        foreach ([
            '.signature-table td.signature-title-cell { height: 9mm !important;',
            '.signature-table td.signature-image-cell { height: 40mm !important;',
            '.signature-table th.signature-caption-cell { height: 8mm !important;',
        ] as $regla) {
            $this->assertStringContainsString($regla, $estilosPdf);
        }
    }

    public function test_pdf_del_015_sigue_el_formato_oficial(): void
    {
        $tipoAccionId = DB::table('vinculacion_tipos_accion')->where('codigo', 'VOLUNTARIADO')->value('id');
        $this->assertNotNull($tipoAccionId, 'Falta el tipo de acción VOLUNTARIADO en la base de pruebas.');

        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Voluntariado de prueba',
            'tipo_accion_id' => $tipoAccionId,
            'hombres' => 7,
            'mujeres' => 9,
            'vol_profesores_hora_hombres' => 2,
            'vol_int_maestria_mujeres' => 4,
        ]);

        foreach ([
            'horas_trabajo_docentes' => 1000, 'horas_trabajo_estudiantes' => 100, 'gastos_movilizacion' => 100,
            'utiles_materiales_oficina' => 100, 'gastos_impresion' => 100,
            // Valores guardados con la fórmula anterior: el PDF no debe usarlos.
            'costos_indirectos_infraestructura' => 6.3, 'costos_indirectos_servicios' => 6.3,
        ] as $concepto => $costoTotal) {
            AporteInstitucional::create([
                'proyecto_id' => $proyecto->id, 'concepto' => $concepto,
                'cantidad' => 1, 'costo_unitario' => $costoTotal, 'costo_total' => $costoTotal,
            ]);
        }

        $this->assertTrue($proyecto->fresh()->esVoluntariado());

        $html = view('components.fichas.ficha-proyecto-vinculacion', ['proyecto' => $proyecto->fresh(), 'isPdf' => true])->render();
        $texto = preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(preg_replace('#<style.*?</style>#s', '', $html))));

        foreach ([
            'Tel. 2216-7070 Ext. 110576',
            'I. INFORMACIÓN GENERAL 1. Fecha de registro Año Mes Día',
            '3. Unidad(s) Académica(s)',
            'Programa al que pertenece',
            '8. Beneficiarios directos Hombres 7 Mujeres 9 Indicar tipo de etnia Pueblo originario',
            'Aldea (incluye ciudad)',
            'IV. INFORMACIÓN DE LA ENTIDAD CONTRAPARTE (Sí existe más de una contraparte',
            '24. ANTECEDENTES: (Explicar brevemente en qué consiste el programa',
            'b) Indicadores de mediano plazo.',
            '3.00 1,400.00 42.00',
            'Total aporte institucional 1,484.00',
            'Nota: El aporte de la institución contraparte',
            'IX. FIRMAS',
            'Firma del profesor/a responsable del programa',
        ] as $esperado) {
            $this->assertStringContainsString($esperado, $texto);
        }

        foreach (['VIII. FIRMAS', '2216-6100', 'Fecha de solicitud de registro', 'Tiempo de participación en el proyecto',
            '(OBLIGATORIO)', 'RTN:', 'Cantidad aproximada', '(ODS principal)', 'Si hubiese'] as $noEsperado) {
            $this->assertStringNotContainsString($noEsperado, $texto);
        }

        // Ítems 15 y 16 salen de lo capturado, no derivados de otros ítems.
        $this->assertMatchesRegularExpression('/15\. Voluntariado personal de la UNAH .*? Hombres Mujeres 2 0 0 0/', $texto);
        $this->assertMatchesRegularExpression('/16\. Voluntariado internacional .*? Hombres Mujeres 0 0 0 4 0 0/', $texto);
    }
}
