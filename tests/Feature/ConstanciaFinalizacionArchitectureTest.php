<?php

namespace Tests\Feature;

use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\User;
use App\Services\Constancias\ConstanciaFinalizacionPdfGenerator;
use App\Services\Constancias\EmitirConstanciaFinalizacionProyecto;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConstanciaFinalizacionArchitectureTest extends TestCase
{
    use DatabaseTransactions;
    public function test_las_rutas_de_constancia_finalizacion_estan_registradas(): void
    {
        $this->assertNotNull(app('router')->getRoutes()->getByName('constancias.finalizacion.descargar'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('constancias.finalizacion.verificar'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('constancias.finalizacion.verificar.pdf'));
    }

    public function test_la_plantilla_pdf_usa_componentes_locales_y_fecha_en_espanol(): void
    {
        $vista = file_get_contents(resource_path('views/pdf/constancias/constancia-finalizacion-proyecto.blade.php'));
        $generador = file_get_contents(app_path('Services/Constancias/ConstanciaFinalizacionPdfGenerator.php'));
        $styles = file_get_contents(resource_path('views/pdf/constancias/partials/styles.blade.php'));

        $this->assertStringContainsString("@include('pdf.constancias.partials.header')", $vista);
        $this->assertStringContainsString("@include('pdf.constancias.partials.footer')", $vista);
        $this->assertStringContainsString("@include('pdf.constancias.partials.watermark')", $vista);
        $this->assertStringContainsString("@include('pdf.constancias.partials.styles')", $vista);
        $this->assertStringContainsString('page-break-before: always', $styles);
        $this->assertStringContainsString("->locale('es')", $vista);
        $this->assertStringNotContainsString('Storage::url', $vista);
        $this->assertStringNotContainsString('http://', $vista);
        $this->assertStringNotContainsString('data:image', $generador);
        $this->assertStringContainsString('temporaryQr', $generador);
        $this->assertStringContainsString("'qr' => 'file://'.\$qrPath", $generador);
        $this->assertStringContainsString("route('constancias.finalizacion.verificar'", $generador);
        $this->assertStringContainsString("'file://'", $generador);
        $header = file_get_contents(resource_path('views/pdf/constancias/partials/header.blade.php'));
        $chrome = file_get_contents(resource_path('views/components/fichas/partials/institutional-pdf-header.blade.php'));
        $chromeStyles = file_get_contents(resource_path('views/components/fichas/partials/institutional-pdf-chrome-styles.blade.php'));
        $this->assertStringContainsString('institutional-pdf-header', $header);
        $this->assertStringContainsString('assets/pdf/common/vra.png', $chrome);
        $this->assertStringContainsString('institutional-pdf-validation', $chromeStyles);

        $this->assertFileExists(resource_path('views/pdf/constancias/partials/header.blade.php'));
        $this->assertFileExists(resource_path('views/pdf/constancias/partials/footer.blade.php'));
        $this->assertFileExists(resource_path('views/pdf/constancias/partials/watermark.blade.php'));
        $this->assertFileExists(resource_path('views/pdf/constancias/partials/styles.blade.php'));
        $this->assertFileExists(public_path('images/enf/form-018-header.png'));
        $this->assertFileExists(public_path('images/enf/form-018-footer.png'));
        $this->assertFileExists(public_path('images/enf/form-018-watermark.png'));
        $this->assertFileExists(public_path('assets/pdf/common/rectangulo_amarillo.png'));
        $this->assertSame('julio', Carbon::parse('2026-07-29')->locale('es')->translatedFormat('F'));
    }

    public function test_el_pdf_renderiza_el_qr_sin_uri_data_ni_advertencias_de_protocolo(): void
    {
        $constancia = new ConstanciaFinalizacionProyecto;
        $constancia->forceFill([
            'token_cifrado' => Crypt::encryptString('token-de-prueba'),
            'snapshot' => [
                'constancia' => [
                    'numero' => 'N.º 0001-VRA/DVUS-2026',
                    'codigo_validacion' => 'ABC123',
                    'fecha_emision' => '2026-07-29 10:00:00',
                    'ciudad_emision' => 'Tegucigalpa',
                ],
                'proyecto' => ['nombre' => 'Proyecto de prueba', 'codigo' => 'PV-001'],
                'coordinador' => ['rol' => 'Coordinación', 'nombre' => 'Persona de prueba', 'numero_empleado' => '1', 'categoria' => 'Docente', 'departamento' => 'DVUS', 'horas' => 8],
                'equipo' => [],
                'beneficiarios' => ['hombres' => 1, 'mujeres' => 1],
                'participacion' => ['estudiantes' => 1, 'voluntarios_docentes' => 0, 'voluntarios_estudiantes' => 0, 'personal_administrativo' => 0],
                'presupuesto' => ['moneda' => 'L', 'unah' => '0.00', 'contraparte' => '0.00', 'total' => '0.00'],
                'autoridad' => ['nombre' => 'Autoridad de prueba', 'cargo' => 'Director Vinculación'],
            ],
        ]);

        $GLOBALS['_dompdf_warnings'] = [];
        $temporalesAntes = glob(storage_path('app/constancias/tmp/constancia-qr-*')) ?: [];
        $contenido = app(ConstanciaFinalizacionPdfGenerator::class)->content($constancia);
        $advertencias = implode("\n", array_map('strval', $GLOBALS['_dompdf_warnings'] ?? []));

        $this->assertStringStartsWith('%PDF', $contenido);
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $contenido));
        $this->assertStringNotContainsString('Permission denied', $advertencias);
        $this->assertStringNotContainsString('communication protocol is not supported', $advertencias);
        $this->assertSame($temporalesAntes, glob(storage_path('app/constancias/tmp/constancia-qr-*')) ?: []);
    }

    public function test_se_emite_con_revisor_vinculacion_cuando_el_flujo_de_cierre_no_tiene_director(): void
    {
        [$proyecto, $revisorFinal, $informe, $documento] = $this->scenarioCierreSinDirectorVinculacion();

        $constancia = app(EmitirConstanciaFinalizacionProyecto::class)
            ->emitir($proyecto->fresh(), $informe, $documento, $revisorFinal->user_id);

        $this->assertSame(ConstanciaFinalizacionProyecto::ESTADO_PENDIENTE, $constancia->estado);
        $this->assertSame($revisorFinal->nombre_completo, $constancia->snapshot['autoridad']['nombre']);
        $this->assertSame('Revisor Vinculacion', $constancia->snapshot['autoridad']['cargo']);
    }

    /**
     * Replica el cierre de un flujo como "FORM-DVUS-001 - Desarrollo local y regional":
     * dos etapas "Revisor Vinculacion" marcadas con `aplica_cierre_proyecto` (sin
     * "Director Vinculacion"), documento "Informe Final" Aprobado y proyecto Finalizado.
     *
     * @return array{0: Proyecto, 1: Empleado, 2: InformeFinalProyecto, 3: DocumentoProyecto}
     */
    private function scenarioCierreSinDirectorVinculacion(): array
    {
        $now = now();

        $type = VinculacionTipoAccion::firstOrCreate(['codigo' => 'DESARROLLO_LOCAL_REGIONAL'], ['nombre' => 'Desarrollo local y regional', 'activo' => true]);
        $proyecto = Proyecto::create([
            'tipo_accion_id' => $type->id,
            'codigo_proyecto' => 'PROY-CIERRE-' . uniqid(),
            'nombre_proyecto' => 'Proyecto de cierre sin etapa Director',
            'fecha_inicio' => '2026-01-15',
            'fecha_finalizacion' => '2026-12-15',
            'objetivo_general' => 'Objetivo de prueba',
            'poblacion_participante' => 100,
            'hombres' => 50,
            'mujeres' => 50,
            'mestizos_hombres' => 50,
            'mestizos_mujeres' => 50,
            'impacto_deseado' => 'Impacto de prueba',
            'total_aporte_institucional' => 50000,
        ]);

        $revisor1 = Empleado::create([
            'nombre_completo' => 'Revisor Cierre Uno',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Femenino',
            'user_id' => User::factory()->create()->id,
            'tipo_empleado' => 'administrativo',
        ]);
        $revisor2 = Empleado::create([
            'nombre_completo' => 'Revisor Cierre Dos',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Femenino',
            'user_id' => User::factory()->create()->id,
            'tipo_empleado' => 'administrativo',
        ]);

        $estadoFinalizado = TipoEstado::firstOrCreate(['nombre' => 'Finalizado']);
        $estadoAprobado = TipoEstado::firstOrCreate(['nombre' => 'Aprobado']);
        $tipoCargoRevisor = TipoCargoFirma::firstOrCreate(['nombre' => 'Revisor Vinculacion']);
        $cargoRevisor = CargoFirma::firstOrCreate(
            ['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $tipoCargoRevisor->id],
            ['tipo_estado_id' => $estadoFinalizado->id]
        );

        $flujo = FlujoAprobacion::create([
            'codigo' => 'CIERRE_SIN_DIRECTOR_' . uniqid(),
            'nombre' => 'Flujo FORM-DVUS-001 - Desarrollo local y regional',
            'proceso' => 'PROYECTO',
            'tipo_accion_id' => $type->id,
            'codigo_formulario' => 'FORM-DVUS-001',
            'activo' => true,
        ]);
        $proyecto->update(['flujo_aprobacion_id' => $flujo->id]);

        $documento = DocumentoProyecto::create([
            'proyecto_id' => $proyecto->id,
            'tipo_documento' => 'Informe Final',
            'documento_url' => 'informes-finales/' . uniqid() . '/informe-final.pdf',
        ]);

        foreach ([1 => $revisor1, 2 => $revisor2] as $orden => $revisor) {
            $etapa = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'ETAPA_CIERRE_REV_' . $orden . '_' . uniqid(),
                'nombre' => (string) $orden,
                'tipo_etapa' => 'APROBACION',
                'cargo_firma_id' => $cargoRevisor->id,
                'usuario_responsable_id' => $revisor->user_id,
                'activo' => true,
                'aplica_cierre_proyecto' => true,
            ]);

            $documento->firma_documento()->create([
                'empleado_id' => $revisor->id,
                'cargo_firma_id' => $cargoRevisor->id,
                'estado_revision' => 'Aprobado',
                'hash' => 'cierre-sin-director-' . uniqid(),
                'firmable_type' => DocumentoProyecto::class,
                'firmable_id' => $documento->id,
                'flujo_aprobacion_id' => $flujo->id,
                'flujo_aprobacion_etapa_id' => $etapa->id,
                'orden_revision' => $orden,
                'etapa_codigo' => $etapa->codigo,
                'etapa_nombre' => $etapa->nombre,
                'revision_ciclo' => 1,
                'fecha_firma' => $now,
            ]);
        }

        $documento->estado_documento()->create([
            'empleado_id' => $revisor2->id,
            'tipo_estado_id' => $estadoAprobado->id,
            'fecha' => $now,
            'comentario' => 'Informe final aprobado.',
            'es_actual' => true,
        ]);

        $proyecto->estado_proyecto()->create([
            'empleado_id' => $revisor2->id,
            'tipo_estado_id' => $estadoFinalizado->id,
            'fecha' => $now,
            'comentario' => 'Cierre INF-001.',
            'es_actual' => true,
        ]);

        EmpleadoProyecto::create(['empleado_id' => $revisor1->id, 'proyecto_id' => $proyecto->id, 'rol' => 'Coordinador']);

        $informe = InformeFinalProyecto::create([
            'proyecto_id' => $proyecto->id,
            'numero_registro' => 'INF-' . uniqid(),
            'nombre_proyecto' => 'Proyecto de cierre sin etapa Director',
            'fecha_inicio' => '2026-01-15',
            'fecha_finalizacion' => '2026-12-15',
            'fecha_cierre' => $now,
            'estado' => InformeFinalProyecto::ESTADO_COMPLETO,
            'created_by' => $revisor1->user_id,
            'updated_by' => $revisor1->user_id,
        ]);

        config(['queue.default' => 'sync']);

        return [$proyecto, $revisor2, $informe, $documento];
    }
}
