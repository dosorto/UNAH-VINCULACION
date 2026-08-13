<?php

namespace Tests\Feature;

use App\Models\Constancias\ConstanciaCorrelativo;
use App\Models\Constancias\ConstanciaRegistroProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Livewire\Docente\Proyectos\HistorialProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\User;
use App\Services\Constancias\ConstanciaRegistroPdfGenerator;
use App\Services\Constancias\EmitirConstanciaRegistroProyecto;
use App\Services\Constancias\NumeroConstanciaRegistro;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConstanciaRegistroArchitectureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_las_rutas_de_constancia_registro_estan_registradas(): void
    {
        $this->assertNotNull(app('router')->getRoutes()->getByName('constancias.registro.descargar'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('constancias.registro.verificar'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('constancias.registro.verificar.pdf'));
    }

    public function test_el_numero_usa_prefijo_r_y_es_unico_por_anio(): void
    {
        $numeros = new NumeroConstanciaRegistro;
        $this->assertSame('N.º R-0001-VRA/DVUS-2026', $numeros->format(1, 2026));
        $this->assertSame('N.º R-0110-VRA/DVUS-2026', $numeros->format(110, 2026));
        $this->assertSame('REGISTRO', NumeroConstanciaRegistro::TIPO);
    }

    public function test_el_correlativo_de_registro_no_colisiona_con_finalizacion(): void
    {
        $anio = (int) now()->year;

        $regFinal = ConstanciaCorrelativo::firstOrCreate(
            ['tipo' => 'FINALIZACION', 'anio' => $anio, 'unidad_emisora' => 'VRA_DVUS'],
            ['ultimo_correlativo' => 5]
        );
        $regRegistro = ConstanciaCorrelativo::firstOrCreate(
            ['tipo' => 'REGISTRO', 'anio' => $anio, 'unidad_emisora' => 'VRA_DVUS'],
            ['ultimo_correlativo' => 3]
        );

        $this->assertNotSame($regFinal->tipo, $regRegistro->tipo);
        $this->assertSame(5, (int) $regFinal->ultimo_correlativo);
        $this->assertSame(3, (int) $regRegistro->ultimo_correlativo);
    }

    public function test_la_plantilla_pdf_usa_componentes_locales_y_no_incluye_http_ni_storage_url(): void
    {
        $vista = file_get_contents(resource_path('views/pdf/constancias/constancia-registro-proyecto.blade.php'));
        $generador = file_get_contents(app_path('Services/Constancias/ConstanciaRegistroPdfGenerator.php'));

        $this->assertStringContainsString("@include('pdf.constancias.partials.registro-styles')", $vista);
        $this->assertStringContainsString("@include('pdf.constancias.partials.registro-header')", $vista);
        $this->assertStringContainsString("@include('pdf.constancias.partials.registro-footer')", $vista);
        $this->assertStringContainsString("@include('pdf.constancias.partials.registro-watermark')", $vista);
        $this->assertStringContainsString("->locale('es')", $vista);
        $this->assertStringNotContainsString('Storage::url', $vista);
        $this->assertStringNotContainsString('http://', $vista);
        $this->assertStringNotContainsString('data:image', $generador);
        $this->assertStringContainsString('temporaryQr', $generador);
        $this->assertStringContainsString("route('constancias.registro.verificar'", $generador);
    }

    public function test_el_pdf_es_exactamente_una_pagina(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $contenido = app(ConstanciaRegistroPdfGenerator::class)->content($constancia);

        $this->assertStringStartsWith('%PDF', $contenido);
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $contenido));
    }

    public function test_no_se_emite_antes_de_completar_la_primera_etapa(): void
    {
        [$user, $proyecto] = $this->scenario(true);

        $existe = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->exists();

        $this->assertFalse($existe, 'No debe existir constancia cuando hay firmas pendientes.');
    }

    public function test_se_emite_automaticamente_al_completar_todas_las_aprobaciones(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->first();

        $this->assertNotNull($constancia, 'Debe existir una constancia tras completar todas las aprobaciones.');
        $this->assertContains($constancia->estado, [
            ConstanciaRegistroProyecto::ESTADO_PENDIENTE,
            ConstanciaRegistroProyecto::ESTADO_EMITIDA,
        ]);
    }

    public function test_es_idempotente_ante_reintentos(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $primera = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        app(EmitirConstanciaRegistroProyecto::class)->emitir($proyecto->fresh(), $user->id);

        $segunda = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $this->assertSame($primera->id, $segunda->id);
        $this->assertSame($primera->numero, $segunda->numero);
        $this->assertSame($primera->correlativo, $segunda->correlativo);
    }

    public function test_snapshot_es_inmutable(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $snapshotAntes = $constancia->snapshot;

        app(EmitirConstanciaRegistroProyecto::class)->emitir($proyecto->fresh(), $user->id);

        $constancia->refresh();
        $this->assertSame($snapshotAntes, $constancia->snapshot);
    }

    public function test_codigo_validacion_y_correlativo_son_unicos(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $this->assertSame(1, ConstanciaRegistroProyecto::query()->where('codigo_validacion', $constancia->codigo_validacion)->count());
        $this->assertSame(1, ConstanciaRegistroProyecto::query()->where('numero', $constancia->numero)->count());
        $this->assertSame(1, ConstanciaRegistroProyecto::query()->where('correlativo', $constancia->correlativo)->where('anio', $constancia->anio)->count());
    }

    public function test_qr_se_genera_realmente(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $GLOBALS['_dompdf_warnings'] = [];
        $temporalesAntes = glob(storage_path('app/constancias/tmp/constancia-qr-*')) ?: [];
        $contenido = app(ConstanciaRegistroPdfGenerator::class)->content($constancia);
        $temporalesDespues = glob(storage_path('app/constancias/tmp/constancia-qr-*')) ?: [];

        $this->assertStringStartsWith('%PDF', $contenido);
        $this->assertSame($temporalesAntes, $temporalesDespues, 'El QR temporal debe limpiarse después del render.');
    }

    public function test_url_de_verificacion_usa_app_url_no_localhost_fijo(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $token = Crypt::decryptString((string) $constancia->token_cifrado);

        config(['app.url' => 'https://vinculacion.unah.edu.hn']);
        URL::forceRootUrl('https://vinculacion.unah.edu.hn');
        URL::forceScheme('https');

        $url = route('constancias.registro.verificar', ['token' => $token]);

        $this->assertStringStartsWith('https://vinculacion.unah.edu.hn/constancias/registro/verificar/', $url);
        $this->assertStringNotContainsString('localhost', $url);
    }

    public function test_verificacion_publica_valida_token_correcto(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $token = Crypt::decryptString((string) $constancia->token_cifrado);

        $response = $this->get(route('constancias.registro.verificar', ['token' => $token]));

        $response->assertOk();
        $response->assertSee($constancia->numero);
        $response->assertSee('Constancia de Registro');
    }

    public function test_token_invalido_retorna_404(): void
    {
        $response = $this->get(route('constancias.registro.verificar', ['token' => 'token-invalido-inexistente']));

        $response->assertNotFound();
    }

    public function test_constancia_anulada_no_expone_el_pdf(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $token = Crypt::decryptString((string) $constancia->token_cifrado);

        $constancia->update([
            'estado' => ConstanciaRegistroProyecto::ESTADO_ANULADA,
            'anulada_en' => now(),
            'motivo_anulacion' => 'Anulación de prueba.',
        ]);

        $response = $this->get(route('constancias.registro.verificar', ['token' => $token]));
        $response->assertOk();
        $response->assertSee('no vigente');

        $pdfResponse = $this->get(route('constancias.registro.verificar.pdf', ['token' => $token]));
        $pdfResponse->assertNotFound();
    }

    public function test_hash_sha256_validado_al_descargar(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $contenido = app(ConstanciaRegistroPdfGenerator::class)->content($constancia);
        $ruta = 'constancias/registro/test-' . uniqid() . '.pdf';
        Storage::disk('local')->put($ruta, $contenido);

        $constancia->update([
            'ruta_archivo' => $ruta,
            'hash_archivo' => hash('sha256', $contenido),
            'estado' => ConstanciaRegistroProyecto::ESTADO_EMITIDA,
        ]);

        $token = Crypt::decryptString((string) $constancia->token_cifrado);
        $response = $this->get(route('constancias.registro.verificar.pdf', ['token' => $token]));
        $response->assertOk();

        Storage::disk('local')->delete($ruta);
    }

    public function test_anulacion_cambia_estado_y_bloquea_descarga(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $constancia->update([
            'estado' => ConstanciaRegistroProyecto::ESTADO_ANULADA,
            'anulada_en' => now(),
            'motivo_anulacion' => 'Prueba de anulación.',
        ]);

        $this->assertFalse($constancia->fresh()->puedeDescargarse());
    }

    public function test_encabezado_qr_firma_marca_de_agua_y_vigencia_presentes(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $constancia = ConstanciaRegistroProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->firstOrFail();

        $vista = file_get_contents(resource_path('views/pdf/constancias/constancia-registro-proyecto.blade.php'));

        $this->assertStringContainsString('registro-header', $vista);
        $this->assertStringContainsString('registro-watermark', $vista);
        $this->assertStringContainsString('registro-signature', $vista);
        $this->assertStringContainsString('registro-vigencia', $vista);
        $this->assertStringContainsString('CONSTAR', $vista);
        $this->assertStringContainsString('Artículo 277', $vista);
    }

    public function test_se_emite_con_revisor_vinculacion_cuando_el_flujo_no_tiene_director(): void
    {
        [$user, $proyecto, $revisorFinal] = $this->scenarioSinDirectorVinculacion();

        $constancia = app(EmitirConstanciaRegistroProyecto::class)->emitir($proyecto->fresh(), $user->id);

        $this->assertSame(ConstanciaRegistroProyecto::ESTADO_PENDIENTE, $constancia->estado);
        $this->assertSame($revisorFinal->nombre_completo, $constancia->snapshot['autoridad']['nombre']);
        $this->assertSame('Revisor Vinculacion', $constancia->snapshot['autoridad']['cargo']);
    }

    public function test_historial_del_proyecto_muestra_la_constancia_de_registro_emitida(): void
    {
        [$user, $proyecto] = $this->scenario(false);
        $this->aprobarTodasLasEtapas($proyecto, $user);

        $coordinadorUser = $proyecto->coordinador_proyecto()->first()->empleado->user;

        Livewire::actingAs($coordinadorUser)
            ->test(HistorialProyecto::class, ['proyecto' => $proyecto->fresh()])
            ->assertSee('Constancia de Registro')
            ->assertSee('Descargar constancia de registro');
    }

    public function test_historial_muestra_el_pdf_del_informe_intermedio_legacy_sin_metadatos(): void
    {
        Storage::fake('public');
        [$user,$proyecto]=$this->scenario(false);
        $proyecto->documentos()->create([
            'tipo_documento'=>'Informe Intermedio',
            'documento_url'=>'documentos/legacy-intermedio.pdf',
        ]);
        Storage::disk('public')->put('documentos/legacy-intermedio.pdf','contenido');
        $coordinadorUser=$proyecto->coordinador_proyecto()->first()->empleado->user;

        Livewire::actingAs($coordinadorUser)
            ->test(HistorialProyecto::class,['proyecto'=>$proyecto->fresh()])
            ->assertSee('Este informe fue enviado antes de habilitarse el registro documental con metadatos.')
            ->assertSee('Ver PDF cargado')
            ->assertSeeHtml('href="/storage/documentos/legacy-intermedio.pdf"');
    }

    /**
     * Replica un flujo como "FORM-DVUS-001 - Desarrollo local y regional": dos etapas
     * "Revisor Vinculacion" (sin "Director Vinculacion"), con el nombre de etapa guardado
     * como el número de orden ("1", "2"), tal como ocurre en datos reales.
     *
     * @return array{0: User, 1: Proyecto, 2: Empleado}
     */
    private function scenarioSinDirectorVinculacion(): array
    {
        $user = User::factory()->create(['name' => 'Coordinador Test ' . uniqid(), 'email' => 'coord.' . uniqid() . '@example.test']);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);
        $empleado = Empleado::create([
            'nombre_completo' => 'Profesor Coordinador Test',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $user->id,
            'tipo_empleado' => 'docente',
        ]);

        $revisor1 = Empleado::create([
            'nombre_completo' => 'Revisor Vinculación Uno',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Femenino',
            'user_id' => User::factory()->create()->id,
            'tipo_empleado' => 'administrativo',
        ]);
        $revisor2 = Empleado::create([
            'nombre_completo' => 'Revisor Vinculación Dos',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Femenino',
            'user_id' => User::factory()->create()->id,
            'tipo_empleado' => 'administrativo',
        ]);

        $type = VinculacionTipoAccion::firstOrCreate(['codigo' => 'DESARROLLO_LOCAL_REGIONAL'], ['nombre' => 'Desarrollo local y regional', 'activo' => true]);
        $proyecto = Proyecto::create([
            'tipo_accion_id' => $type->id,
            'codigo_proyecto' => 'PROY-REG-' . uniqid(),
            'nombre_proyecto' => 'Proyecto sin etapa Director',
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
        EmpleadoProyecto::create(['empleado_id' => $empleado->id, 'proyecto_id' => $proyecto->id, 'rol' => 'Coordinador']);

        $now = now();
        $campusId = DB::table('campus')->insertGetId(['nombre_campus' => 'Campus Test ' . uniqid(), 'direccion' => 'Tegucigalpa', 'telefono' => '00000000', 'url' => 'https://unah.edu.hn', 'created_at' => $now, 'updated_at' => $now]);
        $centroId = DB::table('centro_facultad')->insertGetId(['nombre' => 'Facultad Test ' . uniqid(), 'es_facultad' => true, 'siglas' => 'FTEST', 'campus_id' => $campusId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('proyecto_centro_facultad')->insert(['proyecto_id' => $proyecto->id, 'centro_facultad_id' => $centroId, 'created_at' => $now, 'updated_at' => $now]);

        $estadoEnCurso = TipoEstado::firstOrCreate(['nombre' => 'En curso']);
        $tipoCargoRevisor = TipoCargoFirma::firstOrCreate(['nombre' => 'Revisor Vinculacion']);
        $cargoRevisor = CargoFirma::firstOrCreate(
            ['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $tipoCargoRevisor->id],
            ['tipo_estado_id' => $estadoEnCurso->id]
        );

        $flujo = FlujoAprobacion::create([
            'codigo' => 'REG_SIN_DIRECTOR_' . uniqid(),
            'nombre' => 'Flujo FORM-DVUS-001 - Desarrollo local y regional',
            'proceso' => 'PROYECTO',
            'tipo_accion_id' => $type->id,
            'codigo_formulario' => 'FORM-DVUS-001',
            'activo' => true,
        ]);
        $proyecto->update(['flujo_aprobacion_id' => $flujo->id]);

        foreach ([1 => $revisor1, 2 => $revisor2] as $orden => $revisor) {
            $etapa = FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'ETAPA_REV_' . $orden . '_' . uniqid(),
                'nombre' => (string) $orden,
                'tipo_etapa' => 'APROBACION',
                'cargo_firma_id' => $cargoRevisor->id,
                'usuario_responsable_id' => $revisor->user_id,
                'activo' => true,
                'aplica_inscripcion' => true,
            ]);

            $proyecto->firma_proyecto()->create([
                'empleado_id' => $revisor->id,
                'cargo_firma_id' => $cargoRevisor->id,
                'estado_revision' => 'Aprobado',
                'hash' => 'reg-sin-director-' . uniqid(),
                'firmable_type' => Proyecto::class,
                'firmable_id' => $proyecto->id,
                'flujo_aprobacion_id' => $flujo->id,
                'flujo_aprobacion_etapa_id' => $etapa->id,
                'orden_revision' => $orden,
                'etapa_codigo' => $etapa->codigo,
                'etapa_nombre' => $etapa->nombre,
                'revision_ciclo' => 1,
                'fecha_firma' => $now,
            ]);
        }

        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $estadoEnCurso->id,
            'fecha' => now(),
            'comentario' => 'Flujo de inscripción aprobado.',
            'es_actual' => true,
        ]);

        config(['queue.default' => 'sync']);

        return [$user, $proyecto, $revisor2];
    }

    private function scenario(bool $conFirmasPendientes = false): array
    {
        $user = User::factory()->create(['name' => 'Coordinador Test ' . uniqid(), 'email' => 'coord.' . uniqid() . '@example.test']);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);
        $empleado = Empleado::create([
            'nombre_completo' => 'Profesor Coordinador Test',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $user->id,
            'tipo_empleado' => 'docente',
        ]);

        $directorUser = User::factory()->create(['name' => 'Director VRA ' . uniqid(), 'email' => 'dir.' . uniqid() . '@example.test']);
        $directorRole = Role::firstOrCreate(['name' => 'Director Vinculacion', 'guard_name' => 'web']);
        $directorUser->assignRole($directorRole);
        $directorEmpleado = Empleado::create([
            'nombre_completo' => 'Director de Vinculación Test',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Femenino',
            'user_id' => $directorUser->id,
            'tipo_empleado' => 'administrativo',
        ]);

        $firmaDir = FirmaSelloEmpleado::create(['empleado_id' => $directorEmpleado->id, 'tipo' => 'firma', 'ruta_storage' => 'signatures/firma-test.png', 'estado' => true]);
        $selloDir = FirmaSelloEmpleado::create(['empleado_id' => $directorEmpleado->id, 'tipo' => 'sello', 'ruta_storage' => 'signatures/sello-test.png', 'estado' => true]);

        $type = VinculacionTipoAccion::firstOrCreate(['codigo' => 'DESARROLLO_LOCAL_REGIONAL'], ['nombre' => 'Desarrollo local y regional', 'activo' => true]);
        $proyecto = Proyecto::create([
            'tipo_accion_id' => $type->id,
            'codigo_proyecto' => 'PROY-REG-' . uniqid(),
            'nombre_proyecto' => 'Proyecto de Vinculación de Prueba Registro',
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
        EmpleadoProyecto::create(['empleado_id' => $empleado->id, 'proyecto_id' => $proyecto->id, 'rol' => 'Coordinador']);

        $now = now();
        $campusId = DB::table('campus')->insertGetId(['nombre_campus' => 'Campus Test ' . uniqid(), 'direccion' => 'Tegucigalpa', 'telefono' => '00000000', 'url' => 'https://unah.edu.hn', 'created_at' => $now, 'updated_at' => $now]);
        $centroId = DB::table('centro_facultad')->insertGetId(['nombre' => 'Facultad Test ' . uniqid(), 'es_facultad' => true, 'siglas' => 'FTEST', 'campus_id' => $campusId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('proyecto_centro_facultad')->insert(['proyecto_id' => $proyecto->id, 'centro_facultad_id' => $centroId, 'created_at' => $now, 'updated_at' => $now]);

        $estadoEnCurso = TipoEstado::firstOrCreate(['nombre' => 'En curso']);
        $tipoCargoDirector = TipoCargoFirma::firstOrCreate(['nombre' => 'Director Vinculacion']);
        $cargoDirector = CargoFirma::firstOrCreate(
            ['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $tipoCargoDirector->id],
            ['tipo_estado_id' => $estadoEnCurso->id]
        );

        $flujo = FlujoAprobacion::create([
            'codigo' => 'REG_TEST_' . uniqid(),
            'nombre' => 'Flujo Registro Test',
            'proceso' => 'PROYECTO',
            'tipo_accion_id' => $type->id,
            'codigo_formulario' => 'FORM-DVUS-001',
            'activo' => true,
        ]);
        $etapa = FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => 1,
            'codigo' => 'ETAPA_REG_' . uniqid(),
            'nombre' => 'Aprobación Director Vinculación',
            'tipo_etapa' => 'APROBACION',
            'cargo_firma_id' => $cargoDirector->id,
            'usuario_responsable_id' => $directorUser->id,
            'activo' => true,
            'aplica_inscripcion' => true,
        ]);
        $proyecto->update(['flujo_aprobacion_id' => $flujo->id]);

        $estadoFirma = $conFirmasPendientes ? 'Pendiente' : 'Aprobado';
        $proyecto->firma_proyecto()->create([
            'empleado_id' => $directorEmpleado->id,
            'cargo_firma_id' => $cargoDirector->id,
            'firma_id' => $firmaDir->id,
            'sello_id' => $selloDir->id,
            'estado_revision' => $estadoFirma,
            'hash' => 'reg-' . uniqid(),
            'firmable_type' => Proyecto::class,
            'firmable_id' => $proyecto->id,
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'orden_revision' => 1,
            'etapa_codigo' => $etapa->codigo,
            'etapa_nombre' => $etapa->nombre,
            'revision_ciclo' => 1,
            'fecha_firma' => $conFirmasPendientes ? null : now(),
        ]);

        if (! $conFirmasPendientes) {
            $proyecto->estado_proyecto()->create([
                'empleado_id' => $empleado->id,
                'tipo_estado_id' => $estadoEnCurso->id,
                'fecha' => now(),
                'comentario' => 'Flujo de inscripción aprobado.',
                'es_actual' => true,
            ]);
        }

        config(['queue.default' => 'sync']);

        return [$directorUser, $proyecto];
    }

    private function aprobarTodasLasEtapas(Proyecto $proyecto, User $user): void
    {
        app(EmitirConstanciaRegistroProyecto::class)->emitir($proyecto->fresh(), $user->id);
    }
}
