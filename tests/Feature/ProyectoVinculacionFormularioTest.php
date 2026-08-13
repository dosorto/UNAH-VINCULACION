<?php

namespace Tests\Feature;

use App\Http\Controllers\PDFController;
use App\Livewire\Docente\Proyectos\ProyectosDocenteList;
use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\Proyecto\Proyecto;
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
            'fecha_inicio' => '2026-07-01',
            'fecha_finalizacion' => '2026-07-05',
            'horas' => 12,
            'empleados' => [],
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
            'fecha_inicio' => '2026-05-10',
            'fecha_finalizacion' => '2026-05-15',
            'horas' => 12,
            'empleados' => [],
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
            'fecha_inicio' => '2026-06-30',
            'fecha_finalizacion' => '2026-07-01',
            'horas' => 10,
            'empleados' => [],
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
            'fecha_inicio' => '2026-07-01',
            'fecha_finalizacion' => '2026-07-01',
            'horas' => 8,
            'empleados' => [],
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
            'fecha_inicio' => '2026-07-01',
            'fecha_finalizacion' => '2026-07-05',
            'horas' => 80,
            'empleados' => [],
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
            'fecha_inicio' => '2026-07-03',
            'fecha_finalizacion' => '2026-07-01',
            'horas' => 12,
            'empleados' => [],
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
            'fecha_inicio' => '',
            'fecha_finalizacion' => '2026-05-10',
            'horas' => 12,
            'empleados' => [],
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
            'fecha_inicio' => '2026-05-10',
            'fecha_finalizacion' => '',
            'horas' => 12,
            'empleados' => [],
        ]];

        try {
            $component->nextStep();
            $this->fail('El paso 4 avanzó sin fecha final.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actividades.0.fecha_finalizacion', $exception->validator->errors()->toArray());
            $this->assertSame(4, $component->currentStep);
        }
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
