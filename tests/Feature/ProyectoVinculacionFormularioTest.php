<?php

namespace Tests\Feature;

use App\Http\Controllers\PDFController;
use App\Livewire\Docente\Proyectos\ProyectosDocenteList;
use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\NivelAcademico;
use App\Models\PeriodoAcademico;
use App\Models\Proyecto\Anexo;
use App\Models\Proyecto\IntegranteInternacional;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoAnexo;
use Database\Seeders\UnidadAcademica\PeriodoAcademicoSeeder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Tests\TestCase;

class ProyectoVinculacionFormularioTest extends TestCase
{
    public function test_actividad_permite_fecha_inicial_y_final_diferentes(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->fecha_inicio = '2026-07-01';
        $component->fecha_finalizacion = '2026-07-01';
        $component->actividades = [[
            'descripcion' => 'Jornada comunitaria',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '2026-07-01',
            'fecha_finalizacion' => '2026-07-05',
            'horas' => 12,
            'empleados' => [1],
        ]];

        $component->nextStep();

        $this->assertSame(5, $component->currentStep);
    }

    public function test_actividad_permite_fechas_anteriores_al_inicio_del_proyecto(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->fecha_inicio = '2026-07-01';
        $component->fecha_finalizacion = '2026-07-01';
        $component->actividades = [[
            'descripcion' => 'Actividad registrada posteriormente',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '2026-05-10',
            'fecha_finalizacion' => '2026-05-15',
            'horas' => 12,
            'empleados' => [1],
        ]];

        $component->nextStep();

        $this->assertSame(5, $component->currentStep);
    }

    public function test_actividad_permite_cruzar_hacia_fecha_general_del_proyecto(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->fecha_inicio = '2026-07-01';
        $component->fecha_finalizacion = '2026-07-01';
        $component->actividades = [[
            'descripcion' => 'Actividad iniciada antes',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '2026-06-30',
            'fecha_finalizacion' => '2026-07-01',
            'horas' => 10,
            'empleados' => [1],
        ]];

        $component->nextStep();

        $this->assertSame(5, $component->currentStep);
    }

    public function test_actividad_permite_mismo_dia(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->actividades = [[
            'descripcion' => 'Jornada comunitaria',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '2026-07-01',
            'fecha_finalizacion' => '2026-07-01',
            'horas' => 8,
            'empleados' => [1],
        ]];

        $component->nextStep();

        $this->assertSame(5, $component->currentStep);
    }

    public function test_actividad_permite_fecha_futura_sin_max_del_proyecto(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->fecha_inicio = '2026-07-01';
        $component->fecha_finalizacion = '2026-07-01';
        $component->actividades = [[
            'descripcion' => 'Jornada comunitaria',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '2026-07-01',
            'fecha_finalizacion' => '2026-07-05',
            'horas' => 80,
            'empleados' => [1],
        ]];

        $component->nextStep();

        $this->assertSame(5, $component->currentStep);
    }

    public function test_actividad_rechaza_fecha_final_anterior_a_la_inicial(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->actividades = [[
            'descripcion' => 'Jornada comunitaria',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '2026-07-03',
            'fecha_finalizacion' => '2026-07-01',
            'horas' => 12,
            'empleados' => [1],
        ]];

        $component->nextStep();

        $this->assertSame(4, $component->currentStep);
        $this->assertTrue($component->getErrorBag()->has('actividades.0.fecha_finalizacion'));
    }

    public function test_actividad_rechaza_fecha_inicial_vacia(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->actividades = [[
            'descripcion' => 'Jornada comunitaria',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '',
            'fecha_finalizacion' => '2026-05-10',
            'horas' => 12,
            'empleados' => [1],
        ]];

        try {
            $component->nextStep();
            $this->fail('El paso 4 avanzó sin fecha inicial.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actividades.0.fecha_inicio', $exception->validator->errors()->toArray());
            $this->assertSame(4, $component->currentStep);
        }
    }

    public function test_actividad_rechaza_fecha_final_vacia(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->actividades = [[
            'descripcion' => 'Jornada comunitaria',
            'resultados' => 'Producto esperado',
            'fecha_inicio' => '2026-05-10',
            'fecha_finalizacion' => '',
            'horas' => 12,
            'empleados' => [1],
        ]];

        try {
            $component->nextStep();
            $this->fail('El paso 4 avanzó sin fecha final.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actividades.0.fecha_finalizacion', $exception->validator->errors()->toArray());
            $this->assertSame(4, $component->currentStep);
        }
    }

    public function test_actividad_exige_producto_horas_positivas_y_responsables(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 4;
        $component->actividades = [[
            'descripcion' => 'Jornada comunitaria',
            'resultados' => '',
            'fecha_inicio' => '2026-08-14',
            'fecha_finalizacion' => '2026-08-14',
            'horas' => 0,
            'empleados' => [],
        ]];

        try {
            $component->nextStep();
            $this->fail('El paso 4 avanzó con campos obligatorios incompletos.');
        } catch (ValidationException $exception) {
            $errores = $exception->validator->errors();

            $this->assertTrue($errores->has('actividades.0.resultados'));
            $this->assertTrue($errores->has('actividades.0.horas'));
            $this->assertTrue($errores->has('actividades.0.empleados'));
            $this->assertSame(4, $component->currentStep);
        }
    }

    public function test_horas_docentes_suman_horas_por_responsables_de_todas_las_actividades(): void
    {
        $component = $this->formComponent();
        $component->actividades = [
            ['horas' => 12, 'empleados' => [1, 2]],
            ['horas' => 5, 'empleados' => ['2', '3', '3']],
            ['horas' => 8, 'empleados' => []],
        ];
        $component->aporte_institucional = [[
            'concepto' => 'horas_trabajo_docentes',
            'cantidad' => 999,
            'costo_unitario' => 500,
            'costo_total' => 499500,
        ]];

        $method = new \ReflectionMethod(CreateProyectoVinculacion::class, 'recalculateAporteInstitucional');
        $method->setAccessible(true);
        $method->invoke($component);

        $aporteDocentes = collect($component->aporte_institucional)
            ->firstWhere('concepto', 'horas_trabajo_docentes');

        $this->assertSame(34, $component->totalHorasTrabajoDocentes());
        $this->assertSame(34, $aporteDocentes['cantidad']);
        $this->assertSame(17000.0, $aporteDocentes['costo_total']);

        $vista = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));
        $this->assertStringContainsString(
            "@readonly((\$aporte['concepto'] ?? '') === 'horas_trabajo_docentes')",
            $vista
        );
    }

    public function test_editar_actividad_de_varios_dias_conserva_las_fechas_pasadas(): void
    {
        $component = $this->formComponent();
        $component->fecha_inicio = '2026-07-01';
        $component->fecha_finalizacion = '2026-07-01';
        $component->actividades = [[
            'id' => null,
            'descripcion' => 'Jornada comunitaria',
            'fecha_inicio' => '2026-05-10',
            'fecha_finalizacion' => '2026-05-15',
            'horas' => 20,
            'empleados' => [],
        ]];

        $component->openActividadModal(0);

        $this->assertSame('2026-05-10', $component->nuevaActividad['fecha_inicio']);
        $this->assertSame('2026-05-15', $component->nuevaActividad['fecha_finalizacion']);
    }

    public function test_abrir_modal_dos_veces_no_conserva_fechas_anteriores(): void
    {
        $component = $this->formComponent();
        $component->actividades = [[
            'id' => null,
            'descripcion' => 'Jornada comunitaria',
            'fecha_inicio' => '2026-07-01',
            'fecha_finalizacion' => '2026-07-05',
            'horas' => 20,
            'empleados' => [],
        ]];

        $component->openActividadModal(0);
        $component->closeActividadModal();
        $component->openActividadModal();

        $this->assertSame('', $component->nuevaActividad['fecha_inicio']);
        $this->assertSame('', $component->nuevaActividad['fecha_finalizacion']);
    }

    public function test_html_de_fecha_inicio_no_usa_min_ni_max(): void
    {
        $html = $this->renderActividadDateInputs('2026-07-01', '2026-07-01', '2026-07-01');
        $attributes = $this->dateInputAttributes($html);

        $this->assertSame('nuevaActividad.fecha_inicio', $attributes['inicio']['wire:model.live']);
        $this->assertArrayNotHasKey('min', $attributes['inicio']);
        $this->assertArrayNotHasKey('max', $attributes['inicio']);
    }

    public function test_html_de_fecha_fin_solo_usa_min_de_fecha_inicio_de_actividad(): void
    {
        $html = $this->renderActividadDateInputs('2026-07-01', '2026-07-31', '2026-05-10');
        $attributes = $this->dateInputAttributes($html);

        $this->assertSame('nuevaActividad.fecha_finalizacion', $attributes['fin']['wire:model.live']);
        $this->assertSame('2026-05-10', $attributes['fin']['min']);
        $this->assertArrayNotHasKey('max', $attributes['fin']);
    }

    public function test_html_de_fecha_fin_no_usa_min_si_fecha_inicio_actividad_esta_vacia(): void
    {
        $html = $this->renderActividadDateInputs('2026-07-01', '2026-07-31', '');
        $attributes = $this->dateInputAttributes($html);

        $this->assertArrayNotHasKey('min', $attributes['fin']);
        $this->assertArrayNotHasKey('max', $attributes['fin']);
    }

    public function test_html_de_fecha_fin_ignora_fecha_final_general_del_proyecto(): void
    {
        $html = $this->renderActividadDateInputs('2026-07-01', '2026-07-01', '2026-07-01');
        $attributes = $this->dateInputAttributes($html);

        $this->assertSame('2026-07-01', $attributes['fin']['min']);
        $this->assertArrayNotHasKey('max', $attributes['fin']);
    }

    public function test_paso_5_no_avanza_con_campos_visibles_vacios_o_espacios(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 5;
        $this->llenarDescripcion($component);
        $component->alineamiento_reforma = '   ';

        try {
            $component->nextStep();
            $this->fail('El paso 5 avanzó con campos vacíos.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('alineamiento_reforma', $exception->validator->errors()->toArray());
            $this->assertSame(5, $component->currentStep);
        }
    }

    public function test_paso_7_no_avanza_sin_resultados_esperados(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 7;
        $component->objetivo_general = 'Mejorar capacidades locales';
        $component->objetivosEspecificos = [[
            'descripcion' => 'Fortalecer la organización comunitaria',
            'resultados' => [],
        ]];

        try {
            $component->nextStep();
            $this->fail('El paso 7 avanzó sin resultados esperados.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('objetivosEspecificos.0.resultados', $exception->validator->errors()->toArray());
            $this->assertSame(7, $component->currentStep);
        }
    }

    public function test_resultado_sin_indicador_falla(): void
    {
        $this->assertMarcoLogicoFallaConResultadoIncompleto('nombre_indicador');
    }

    public function test_resultado_sin_medio_de_verificacion_falla(): void
    {
        $this->assertMarcoLogicoFallaConResultadoIncompleto('nombre_medio_verificacion');
    }

    public function test_resultado_sin_plazo_falla(): void
    {
        $this->assertMarcoLogicoFallaConResultadoIncompleto('plazo');
    }

    public function test_beneficiario_vacio_se_convierte_en_cero(): void
    {
        $component = $this->formComponent();
        $component->indigenas_mujeres = '';

        $component->calcTotales();

        $this->assertSame(0, $component->indigenas_mujeres);
    }

    public function test_calc_totales_no_lanza_error_y_calcula_subtotales(): void
    {
        $component = $this->formComponent();
        $component->indigenas_hombres = 3;
        $component->indigenas_mujeres = null;
        $component->afroamericanos_hombres = 2;
        $component->afroamericanos_mujeres = 4;
        $component->mestizos_hombres = 5;
        $component->mestizos_mujeres = 6;

        $component->calcTotales();

        $this->assertSame(10, $component->hombres);
        $this->assertSame(10, $component->mujeres);
        $this->assertSame(20, $component->poblacion_participante);
    }

    public function test_nombre_de_pdf_usa_codigo_sanitizado(): void
    {
        $proyecto = new Proyecto(['codigo_proyecto' => 'PROY 2026/001:Final?.pdf']);
        $proyecto->id = 25;

        $this->assertSame('FORM-DVUS-001-PROY-2026001Final.pdf', $this->nombreArchivoPerfil($proyecto));
    }

    public function test_nombre_de_pdf_usa_id_si_no_hay_codigo(): void
    {
        $proyecto = new Proyecto(['codigo_proyecto' => null]);
        $proyecto->id = 25;

        $this->assertSame('FORM-DVUS-001-Proyecto-25.pdf', $this->nombreArchivoPerfil($proyecto));
    }

    public function test_nombre_de_pdf_usa_id_si_codigo_queda_vacio_al_sanitizar(): void
    {
        $proyecto = new Proyecto(['codigo_proyecto' => '///.pdf']);
        $proyecto->id = 25;

        $this->assertSame('FORM-DVUS-001-Proyecto-25.pdf', $this->nombreArchivoPerfil($proyecto));
    }

    public function test_detalle_conserva_un_solo_boton_descargar_pdf(): void
    {
        $ficha = file_get_contents(resource_path('views/components/fichas/ficha-proyecto-vinculacion.blade.php'));
        $detalle = file_get_contents(resource_path('views/livewire/docente/proyectos/historial-proyecto.blade.php'));

        $this->assertStringNotContainsString('Descargar PDF', $ficha);
        $this->assertSame(1, substr_count($detalle, "route('proyecto.perfil.pdf.download'"));
        $this->assertStringContainsString("route('proyecto.perfil.pdf.download'", $detalle);
        $this->assertStringNotContainsString("route('proyecto.perfil.pdf',", $detalle);
        $this->assertStringNotContainsString('<iframe src="{{ route(\'proyecto.perfil.pdf.download', $detalle);
        $this->assertStringNotContainsString('<embed src="{{ route(\'proyecto.perfil.pdf.download', $detalle);
        $this->assertStringNotContainsString('<object data="{{ route(\'proyecto.perfil.pdf.download', $detalle);
    }

    public function test_rutas_pdf_separan_preview_y_descarga(): void
    {
        $preview = Route::getRoutes()->getByName('proyecto.perfil.pdf');
        $download = Route::getRoutes()->getByName('proyecto.perfil.pdf.download');

        $this->assertNotNull($preview);
        $this->assertNotNull($download);
        $this->assertSame('proyectos/{proyecto}/perfil-pdf', $preview->uri());
        $this->assertSame(PDFController::class.'@previsualizarPerfilProyecto', $preview->getActionName());
        $this->assertSame('proyectos/{proyecto}/perfil-pdf/descargar', $download->uri());
        $this->assertSame(PDFController::class.'@descargarPerfilProyecto', $download->getActionName());
    }

    public function test_respuestas_pdf_declaran_disposition_correcto(): void
    {
        $response = $this->aplicarHeadersPerfilPdf('inline', 'FORM-DVUS-001-PROY-2026001.pdf');

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame(
            'inline; filename="FORM-DVUS-001-PROY-2026001.pdf"',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_ruta_download_devuelve_attachment(): void
    {
        $response = $this->aplicarHeadersPerfilPdf('attachment', 'FORM-DVUS-001-PROY-2026001.pdf');

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame(
            'attachment; filename="FORM-DVUS-001-PROY-2026001.pdf"',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_ruta_ver_proyecto_no_es_pdf(): void
    {
        $historial = Route::getRoutes()->getByName('historialproyecto');

        $this->assertNotNull($historial);
        $this->assertSame('historialproyecto/{proyecto}', $historial->uri());
        $this->assertStringNotContainsString(PDFController::class, $historial->getActionName());
        $this->assertStringNotContainsString('pdf', strtolower($historial->uri()));
    }

    public function test_detalle_vuelve_a_mi_historial_vinculacion(): void
    {
        $detalle = file_get_contents(resource_path('views/livewire/docente/proyectos/historial-proyecto.blade.php'));

        $this->assertStringContainsString('route($historialRouteName, $historialRouteParameters)', $detalle);
    }

    public function test_director_abre_el_proyecto_en_la_pantalla_de_detalle(): void
    {
        $listado = file_get_contents(resource_path('views/livewire/director-facultad-centro/proyectos/list-proyectos.blade.php'));

        $this->assertStringContainsString("route('historialproyecto', \$record)", $listado);
        $this->assertStringNotContainsString('wire:click="openView', $listado);
        $this->assertStringNotContainsString('$viewModal', $listado);
    }

    public function test_ruta_de_detalle_admite_el_permiso_de_director(): void
    {
        $historial = Route::getRoutes()->getByName('historialproyecto');
        $middleware = implode('|', $historial->gatherMiddleware());

        $this->assertStringContainsString('director.proyectos', $middleware);
    }

    public function test_revisores_abren_la_ficha_en_pantalla_completa(): void
    {
        $bandejas = [
            'list-proyectos-vinculacion-solicitados.blade.php' => 'solicitados',
            'list-proyecto-revision-final.blade.php' => 'revision-final',
        ];

        foreach ($bandejas as $archivo => $origen) {
            $vista = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/'.$archivo));

            $this->assertStringContainsString("'origen' => '{$origen}'", $vista);
            $this->assertStringContainsString("route('historialproyecto'", $vista);
            $this->assertStringNotContainsString('wire:click="openView', $vista);
            $this->assertStringNotContainsString('components.fichas.ficha-proyecto-vinculacion', $vista);
            $this->assertStringNotContainsString('$viewModal', $vista);
        }
    }

    public function test_ficha_base_del_proyecto_solo_se_incrusta_en_su_pantalla_completa(): void
    {
        $vistasConFicha = collect(File::allFiles(resource_path('views/livewire')))
            ->filter(fn ($archivo) => str_contains(
                file_get_contents($archivo->getPathname()),
                'components.fichas.ficha-proyecto-vinculacion'
            ))
            ->map(fn ($archivo) => $archivo->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame(['docente/proyectos/historial-proyecto.blade.php'], $vistasConFicha);
    }

    public function test_proyectos_por_firmar_envia_la_ficha_base_al_detalle(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/docente/proyectos/proyectos-por-firmar.blade.php'));

        $this->assertStringContainsString("'origen' => 'por-firmar'", $vista);
        $this->assertStringContainsString("route('historialproyecto'", $vista);
        $this->assertStringNotContainsString('components.fichas.ficha-proyecto-vinculacion', $vista);
    }

    public function test_subsanacion_usa_nombre_correcto_y_area_amplia_para_observaciones(): void
    {
        $detalle = file_get_contents(resource_path('views/livewire/docente/proyectos/historial-proyecto.blade.php'));

        $this->assertStringContainsString('Enviar proyecto a subsanación', $detalle);
        $this->assertStringContainsString('rows="10"', $detalle);
        $this->assertStringContainsString('max-w-3xl', $detalle);
        $this->assertStringContainsString('wire:click="subsanar"', $detalle);
        $this->assertStringNotContainsString('Rechazar Proyecto', $detalle);
        $this->assertStringNotContainsString('Confirmar Rechazo', $detalle);
    }

    public function test_ruta_de_detalle_admite_las_bandejas_de_revision(): void
    {
        $historial = Route::getRoutes()->getByName('historialproyecto');
        $middleware = implode('|', $historial->gatherMiddleware());

        $this->assertStringContainsString('proyectos.solicitados', $middleware);
        $this->assertStringContainsString('proyectos.revision-final', $middleware);
    }

    public function test_mi_historial_soporta_tipo_de_accion_en_query_string(): void
    {
        $property = new \ReflectionProperty(ProyectosDocenteList::class, 'filterTipoAccion');
        $attributes = $property->getAttributes(Url::class);

        $this->assertNotEmpty($attributes);
        $this->assertSame('tipo', $attributes[0]->getArguments()['as']);
        $this->assertSame('todas', $attributes[0]->getArguments()['except']);
    }

    public function test_iframes_ocultos_de_ficha_se_crean_solo_al_abrir_modal(): void
    {
        $ficha = str_replace(
            ["\r\n", "\r"],
            "\n",
            file_get_contents(resource_path('views/components/fichas/ficha-proyecto-vinculacion.blade.php'))
        );

        $this->assertGreaterThanOrEqual(2, substr_count($ficha, '<template x-if="open">'));
        $this->assertMatchesRegularExpression(
            '/<template\s+x-if="open">\s*<iframe\s+src="\{\{\s*Storage::url\(\$instrumento->documento_url\)\s*\}\}"/',
            $ficha
        );
        $this->assertMatchesRegularExpression(
            '/<template\s+x-if="open">\s*<iframe\s+src="\{\{\s*Storage::url\(\$anexo->documento_url\)\s*\}\}"/',
            $ficha
        );
    }

    public function test_identificadores_internacionales_superiores_a_catorce_caracteres_son_validos(): void
    {
        $identificador = 'US-INTL TAX-2026 00001';

        $this->assertTrue(Validator::make(['rtn' => $identificador], [
            'rtn' => $this->reglasRtn('Estados Unidos'),
        ])->passes());
        $this->assertTrue(Validator::make(['rtn' => $identificador], [
            'rtn' => $this->reglasRtn(null, 'internacional'),
        ])->passes());
    }

    public function test_rtn_hondureno_conserva_catorce_digitos_numericos(): void
    {
        $rules = ['rtn' => $this->reglasRtn('Honduras')];

        $this->assertTrue(Validator::make(['rtn' => '08011985123456'], $rules)->passes());
        $this->assertTrue(Validator::make(['rtn' => '0801-1985-123456'], $rules)->fails());
        $this->assertTrue(Validator::make(['rtn' => '080119851234567'], $rules)->fails());
    }

    public function test_ods_permite_hasta_tres_selecciones_y_rechaza_una_cuarta(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 1;
        $method = new \ReflectionMethod(CreateProyectoVinculacion::class, 'rulesPasoActualBase');
        $method->setAccessible(true);
        $rules = $method->invoke($component);

        $this->assertTrue(Validator::make(['ods' => [1, 2, 3]], ['ods' => $rules['ods']])->passes());
        $this->assertTrue(Validator::make(['ods' => [1, 2, 3, 4]], ['ods' => $rules['ods']])->fails());
    }

    public function test_selector_ods_impide_agregar_una_cuarta_opcion(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));

        $this->assertStringContainsString('maxOds: 3', $vista);
        $this->assertStringContainsString('if (current.length >= this.maxOds) return;', $vista);
        $this->assertStringContainsString("(selected || []).length + '/3'", $vista);
    }

    public function test_paso_uno_se_marca_completo_cuando_la_carrera_no_aplica(): void
    {
        $component = $this->formComponent();
        $component->recordId = 10;
        $component->nombre_proyecto = 'Proyecto sin carrera';
        $component->modalidad_id = 1;
        $component->categoria = [1];
        $component->ejes_prioritarios_unah = [1];
        $component->facultades_centros = [1];
        $component->departamentos_academicos = [1];
        $component->carreras = [];
        $component->carrera_no_aplica = true;
        $component->programa_pertenece = 'Programa institucional';
        $component->lineas_investigacion_academica = 'Línea institucional';
        $component->ods = [1];
        $component->fecha_inicio = '2026-08-01';
        $component->fecha_finalizacion = '2026-08-31';

        $this->assertTrue($component->isStepComplete(1));

        $component->carrera_no_aplica = false;

        $this->assertFalse($component->isStepComplete(1));
    }

    public function test_aportes_vacios_se_calculan_como_cero_sin_error_de_tipo(): void
    {
        $component = $this->formComponent();
        $component->aporte_institucional = [['costo_total' => 125.50]];

        foreach ([
            'aporte_contraparte',
            'aporte_internacionales',
            'aporte_otras_universidades',
            'aporte_comunidad',
            'otros_aportes',
        ] as $campo) {
            $component->{$campo} = '';
        }

        $this->assertSame(125.50, $component->totalGeneralPresupuesto());
    }

    public function test_fila_internacional_refleja_el_nivel_academico_seleccionado(): void
    {
        $nivel = new NivelAcademico(['nombre' => 'Maestría']);
        $nivel->id = 2;

        $integrante = new IntegranteInternacional([
            'nombre_completo' => 'Docente internacional',
            'rtn' => 'INT-2026',
            'sexo' => 'femenino',
            'pais' => 'Guatemala',
            'institucion' => 'Universidad internacional',
            'nivel_academico_id' => 2,
        ]);
        $integrante->id = 15;
        $integrante->setRelation('nivelAcademico', $nivel);

        $method = new \ReflectionMethod(CreateProyectoVinculacion::class, 'filaIntegranteInternacional');
        $method->setAccessible(true);
        $fila = $method->invoke($this->formComponent(), $integrante);

        $this->assertSame(2, $fila['nivel_academico_id']);
        $this->assertSame('Maestría', $fila['nivel_academico_nombre']);
        $this->assertSame('femenino', $fila['sexo']);
    }

    public function test_formulario_internacional_incluye_edicion_y_nivel_obligatorio(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));
        $componente = file_get_contents(app_path('Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php'));

        $this->assertStringContainsString('wire:click="openInternacionalModal({{ $i }})"', $vista);
        $this->assertStringContainsString("'Editar Docente Internacional'", $vista);
        $this->assertStringContainsString("'Actualizar docente'", $vista);
        $this->assertStringContainsString("'nuevoIntegranteInternacional.nivel_academico_id' => [", $componente);
        $this->assertStringContainsString("'nuevoIntegranteInternacional.sexo' => 'required|in:masculino,femenino'", $componente);
        $this->assertStringContainsString('Seleccione el sexo para contabilizar al docente en la ficha.', $componente);
        $this->assertStringContainsString("'required',", $componente);
    }

    public function test_paso_de_equipo_requiere_sexo_y_nivel_para_reflejar_docente_en_ficha(): void
    {
        $component = $this->formComponent();
        $component->recordId = 70;
        $component->estudiante_proyecto = [[
            'tipo_participacion_estudiante' => 'Voluntariado',
            'cantidad_estudiantes_hombres' => 1,
            'cantidad_estudiantes_mujeres' => 0,
        ]];
        $component->integrante_internacional_proyecto = [[
            'integrante_internacional_id' => 3,
            'nivel_academico_id' => 3,
            'nivel_academico_nombre' => 'Doctorado/Posgrado',
            'sexo' => '',
        ]];

        $this->assertFalse($component->isStepComplete(2));

        $component->integrante_internacional_proyecto[0]['sexo'] = 'masculino';

        $this->assertTrue($component->isStepComplete(2));
    }

    public function test_grupo_de_estudiantes_no_se_guarda_con_total_cero(): void
    {
        $component = $this->formComponent();
        $component->showEstudianteModal = true;
        $component->nuevoEstudiante = [
            'tipo_participacion_estudiante' => 'Voluntariado',
            'carrera_id' => null,
            'asignatura_id' => null,
            'periodo_academico_id' => null,
            'cantidad_estudiantes_hombres' => 0,
            'cantidad_estudiantes_mujeres' => 0,
            'total_estudiantes' => 0,
        ];

        $component->saveEstudiante();

        $this->assertSame([], $component->estudiante_proyecto);
        $this->assertTrue($component->showEstudianteModal);
        $this->assertTrue($component->getErrorBag()->has('nuevoEstudiante.total_estudiantes'));
    }

    public function test_paso_de_equipo_rechaza_un_grupo_preexistente_con_total_cero(): void
    {
        $component = $this->formComponent();
        $component->currentStep = 2;
        $component->estudiante_proyecto = [[
            'tipo_participacion_estudiante' => 'Voluntariado',
            'carrera_id' => null,
            'asignatura_id' => null,
            'periodo_academico_id' => null,
            'cantidad_estudiantes_hombres' => 0,
            'cantidad_estudiantes_mujeres' => 0,
            'total_estudiantes' => 0,
        ]];

        $component->nextStep();

        $this->assertSame(2, $component->currentStep);
        $this->assertTrue($component->getErrorBag()->has('estudiante_proyecto'));
        $this->assertTrue($component->getErrorBag()->has('estudiante_proyecto.0.total_estudiantes'));
    }

    public function test_catalogo_base_contiene_los_tres_periodos_academicos(): void
    {
        $this->assertSame([
            'Primer Periodo',
            'Segundo Periodo',
            'Tercer Periodo',
        ], PeriodoAcademico::NOMBRES_BASE);

        $seeder = file_get_contents((new \ReflectionClass(PeriodoAcademicoSeeder::class))->getFileName());
        $this->assertStringContainsString('PeriodoAcademico::NOMBRES_BASE', $seeder);
        $this->assertStringContainsString("firstOrCreate(['nombre' => \$nombre])", $seeder);
    }

    public function test_catalogo_de_anexos_contiene_los_cuatro_tipos_solicitados(): void
    {
        $this->assertSame([
            'Carta de solicitud del proyecto firmada por el representante legal de la contraparte',
            'Convenio/carta de intenciones firmada entre la UNAH y contraparte',
            'Oficio de remisión del Decano/Director Centro Regional',
            'Otros (detallar)',
        ], array_column(TipoAnexo::TIPOS_BASE, 'nombre'));

        $this->assertFalse(TipoAnexo::TIPOS_BASE[0]['requiere_detalle']);
        $this->assertTrue(TipoAnexo::TIPOS_BASE[3]['requiere_detalle']);

        $seeder = file_get_contents(database_path('seeders/Proyecto/TipoAnexoSeeder.php'));
        $this->assertStringContainsString('TipoAnexo::TIPOS_BASE', $seeder);
        $this->assertStringContainsString("['codigo' => \$datos['codigo']]", $seeder);
    }

    public function test_anexos_se_cargan_desde_modal_y_se_muestran_en_tabla_con_tipo(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));
        $componente = file_get_contents(app_path('Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php'));

        $this->assertStringContainsString('wire:click="openAnexoModal"', $vista);
        $this->assertStringContainsString('wire:model.live="nuevoAnexoTipoId"', $vista);
        $this->assertStringContainsString('Seleccione o suelte sus documentos aquí', $vista);
        $this->assertStringContainsString('$anexo->tipoAnexo?->nombre', $vista);
        $this->assertStringContainsString('Detalle el tipo de documento que está adjuntando.', $componente);
        $this->assertStringContainsString("'nombre_archivo' => mb_substr", $componente);

        $anexo = new Anexo([
            'tipo_anexo_id' => 4,
            'documento_url' => 'anexos/archivo.pdf',
            'nombre_archivo' => 'Carta firmada.pdf',
            'detalle' => 'Documento complementario',
        ]);

        $this->assertSame(4, $anexo->tipo_anexo_id);
        $this->assertSame('Carta firmada.pdf', $anexo->nombre_archivo);
        $this->assertSame('Documento complementario', $anexo->detalle);
    }

    public function test_paso_de_anexos_exige_documento_uno_o_dos_y_documento_tres(): void
    {
        $component = $this->formComponent();
        $component->recordId = 70;

        $component->codigosTiposAnexoAdjuntos = [
            TipoAnexo::CODIGO_CARTA_SOLICITUD,
            TipoAnexo::CODIGO_OFICIO_REMISION,
        ];
        $this->assertTrue($component->isStepComplete(9));

        $component->codigosTiposAnexoAdjuntos = [
            TipoAnexo::CODIGO_CONVENIO_CARTA,
            TipoAnexo::CODIGO_OFICIO_REMISION,
        ];
        $this->assertTrue($component->isStepComplete(9));

        $component->codigosTiposAnexoAdjuntos = [TipoAnexo::CODIGO_OFICIO_REMISION];
        $this->assertFalse($component->isStepComplete(9));

        $component->codigosTiposAnexoAdjuntos = [TipoAnexo::CODIGO_CARTA_SOLICITUD];
        $this->assertFalse($component->isStepComplete(9));
    }

    private function formComponent(): CreateProyectoVinculacion
    {
        return new CreateProyectoVinculacion;
    }

    private function reglasRtn(?string $pais = null, ?string $tipoEntidad = null): array
    {
        $method = new \ReflectionMethod(CreateProyectoVinculacion::class, 'reglasRtn');
        $method->setAccessible(true);

        return $method->invoke($this->formComponent(), $pais, $tipoEntidad);
    }

    private function nombreArchivoPerfil(Proyecto $proyecto): string
    {
        $method = new \ReflectionMethod(PDFController::class, 'nombreArchivoPerfilProyecto');
        $method->setAccessible(true);

        return $method->invoke(new PDFController, $proyecto);
    }

    private function aplicarHeadersPerfilPdf(string $disposition, string $nombreArchivo)
    {
        $method = new \ReflectionMethod(PDFController::class, 'aplicarHeadersPerfilPdf');
        $method->setAccessible(true);

        return $method->invoke(new PDFController, response('pdf'), $disposition, $nombreArchivo);
    }

    private function llenarDescripcion(CreateProyectoVinculacion $component): void
    {
        $component->resumen = 'Resumen';
        $component->descripcion_participantes = 'Participantes';
        $component->definicion_problema = 'Problema';
        $component->alineamiento_reforma = 'Reforma';
        $component->impacto_deseado = 'Impacto';
        $component->metodologia = 'Metodologia';
        $component->bibliografia = 'Bibliografia';
    }

    private function assertMarcoLogicoFallaConResultadoIncompleto(string $campo): void
    {
        $component = $this->formComponent();
        $component->currentStep = 7;
        $component->objetivo_general = 'Mejorar capacidades locales';
        $component->objetivosEspecificos = [[
            'descripcion' => 'Fortalecer la organización comunitaria',
            'resultados' => [[
                'nombre_resultado' => 'Comité formado',
                'nombre_indicador' => 'Un comité activo',
                'nombre_medio_verificacion' => 'Acta de conformación',
                'plazo' => 'corto_plazo',
            ]],
        ]];
        $component->objetivosEspecificos[0]['resultados'][0][$campo] = '';

        try {
            $component->nextStep();
            $this->fail("El paso 7 avanzó con {$campo} vacío.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey("objetivosEspecificos.0.resultados.0.{$campo}", $exception->validator->errors()->toArray());
            $this->assertSame(7, $component->currentStep);
        }
    }

    private function renderActividadDateInputs(string $fechaInicioProyecto, string $fechaFinProyecto, string $fechaInicioActividad): string
    {
        return Blade::render(<<<'BLADE'
@php
    $actividadFechaFinMin = data_get($nuevaActividad, 'fecha_inicio');
@endphp
<input id="inicio" type="date" wire:model.live="nuevaActividad.fecha_inicio" />
<input id="fin" type="date" wire:model.live="nuevaActividad.fecha_finalizacion"
    @if($actividadFechaFinMin) min="{{ $actividadFechaFinMin }}" @endif
/>
BLADE, [
            'fecha_inicio' => $fechaInicioProyecto,
            'fecha_finalizacion' => $fechaFinProyecto,
            'nuevaActividad' => ['fecha_inicio' => $fechaInicioActividad],
        ]);
    }

    private function dateInputAttributes(string $html): array
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $attributes = [];
        foreach ($dom->getElementsByTagName('input') as $input) {
            $id = $input->getAttribute('id');
            $attributes[$id] = [];
            foreach ($input->attributes as $attribute) {
                $attributes[$id][$attribute->name] = $attribute->value;
            }
        }

        return $attributes;
    }
}
