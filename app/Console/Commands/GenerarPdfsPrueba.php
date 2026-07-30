<?php

namespace App\Console\Commands;

use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\InformeFinal\InformeFinalAccionEmergente;
use App\Models\InformeFinal\InformeFinalAccionNoEjecutada;
use App\Models\InformeFinal\InformeFinalActividad;
use App\Models\InformeFinal\InformeFinalActividadParticipante;
use App\Models\InformeFinal\InformeFinalAnexo;
use App\Models\InformeFinal\InformeFinalBeneficiario;
use App\Models\InformeFinal\InformeFinalContraparte;
use App\Models\InformeFinal\InformeFinalCooperacion;
use App\Models\InformeFinal\InformeFinalEquipoDocente;
use App\Models\InformeFinal\InformeFinalEstudiante;
use App\Models\InformeFinal\InformeFinalGrupoEstudiante;
use App\Models\InformeFinal\InformeFinalOds;
use App\Models\InformeFinal\InformeFinalPresupuestoDetalle;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\InformeFinal\InformeFinalResultado;
use App\Models\InformeFinal\InformeFinalVoluntario;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proyecto\Actividad;
use App\Models\Proyecto\AporteInstitucional;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\EntidadContraparte;
use App\Models\Proyecto\EntidadContraparteProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\ObjetivoEspecifico;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\ResultadoEsperado;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\UnidadAcademica\Campus;
use App\Models\User;
use App\Services\Constancias\EmitirConstanciaFinalizacionProyecto;
use App\Services\InformeFinal\InformeFinalPdfGenerator;
use App\Services\InformeFinal\InformeFinalProyectoInitializer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class GenerarPdfsPrueba extends Command
{
    protected $signature = 'pdf:generar-pruebas {--fresh} {--only=}';

    protected $description = 'Genera PDFs de prueba (INF-001 y Constancia de Finalizacion) con datos realistas';

    private const CODIGO_PROYECTO = 'PROY-PDF-PRUEBA';
    private const EMAIL_COORDINADOR = 'coordinador.pdf.prueba@unah.edu.hn';
    private const EMAIL_DIRECTOR = 'director.vinculacion.pdf.prueba@unah.edu.hn';
    private const NUMERO_COORD = '990099';
    private const NUMERO_DIRECTOR = '990100';
    private const DIR_SALIDA = 'pdfs-prueba';

    private ?User $user = null;
    private ?User $directorUser = null;
    private ?FlujoAprobacion $flujo = null;
    private ?FlujoAprobacionEtapa $etapaCierre = null;

    public function handle(): int
    {
        if (! in_array(app()->environment(), ['local', 'testing'], true)) {
            $this->error('Este comando solo puede ejecutarse en entornos local o testing.');
            return 1;
        }

        config(['queue.default' => 'sync']);

        $only = $this->option('only');
        $fresh = $this->option('fresh');

        if ($fresh) {
            $this->info('Limpiando escenario previo...');
            $this->limpiarEscenario();
        }

        $proyecto = Proyecto::withTrashed()->where('codigo_proyecto', self::CODIGO_PROYECTO)->first();

        if (! $proyecto) {
            $this->info('Creando escenario base...');
            $proyecto = $this->crearEscenarioBase();
        } else {
            $this->info('Escenario base ya existe, reutilizando...');
        }

        $this->user = User::where('email', self::EMAIL_COORDINADOR)->first();
        $this->directorUser = User::where('email', self::EMAIL_DIRECTOR)->first();
        $this->flujo = FlujoAprobacion::where('codigo', 'PDF-PRUEBA-FLUJO')->first();
        $this->etapaCierre = $this->flujo?->etapas()->where('aplica_cierre_proyecto', true)->first();

        $informe = $proyecto->informeFinalInf001;
        if (! $informe) {
            $this->info('Inicializando informe final...');
            $informe = $this->inicializarInforme($proyecto);
        }

        if ($informe->estado === InformeFinalProyecto::ESTADO_BORRADOR) {
            $this->info('Enriqueciendo informe final con datos realistas...');
            $this->enriquecerInforme($informe);
        }

        $salidaDir = self::DIR_SALIDA;
        if (! Storage::disk('local')->exists($salidaDir)) {
            Storage::disk('local')->makeDirectory($salidaDir);
        }

        $constancia = null;

        if ($only !== 'constancia') {
            $this->info('Generando PDF INF-001...');
            $this->generarInf001($informe);
        }

        if ($only !== 'inf001') {
            $this->info('Ejecutando cierre y emitiendo constancia...');
            $constancia = $this->ejecutarCierre($proyecto, $informe);
            if ($constancia) {
                $this->generarConstancia($constancia);
            }
        }

        $this->info('');
        $this->info('========================================');
        $this->info('  PDFs de prueba generados');
        $this->info('========================================');

        if ($only !== 'constancia') {
            $path = Storage::disk('local')->path(self::DIR_SALIDA . '/INF-001-prueba.pdf');
            $this->line("  INF-001:   {$path}  (" . $this->formatBytes(filesize($path)) . ')');
        }

        if ($constancia) {
            $path = Storage::disk('local')->path(self::DIR_SALIDA . '/Constancia-Finalizacion-prueba.pdf');
            $this->line("  Constancia: {$path}  (" . $this->formatBytes(filesize($path)) . ')');
            $this->line("  Numero:     {$constancia->numero}");
            $this->line("  Codigo val: {$constancia->codigo_validacion}");
            $url = route('constancias.finalizacion.verificar', ['token' => 'TOKEN-DESCIFRADO']);
            $this->line("  URL verify: {$url}");
        }

        $this->info('========================================');

        return 0;
    }

    private function limpiarEscenario(): void
    {
        $proyecto = Proyecto::withTrashed()->where('codigo_proyecto', self::CODIGO_PROYECTO)->first();
        if (! $proyecto) {
            $this->limpiarUsuariosYCatalogos();
            return;
        }

        DB::table('constancias_finalizacion_proyecto')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('constancia_correlativos')->where('tipo', 'finalizacion_proyecto')->delete();

        $informe = InformeFinalProyecto::withTrashed()->where('proyecto_id', $proyecto->id)->first();
        if ($informe) {
            $this->limpiarInformeFinal($informe->id);
            $informe->forceDelete();
        }

        $documentos = DB::table('proyecto_documento')->where('proyecto_id', $proyecto->id)->pluck('id');
        DB::table('firma_proyecto')->whereIn('firmable_id', $documentos)->where('firmable_type', DocumentoProyecto::class)->delete();
        DB::table('estado_proyecto')->whereIn('estadoable_id', $documentos)->where('estadoable_type', DocumentoProyecto::class)->delete();
        DB::table('proyecto_documento')->where('proyecto_id', $proyecto->id)->delete();

        DB::table('firma_proyecto')->where('firmable_id', $proyecto->id)->where('firmable_type', Proyecto::class)->delete();
        DB::table('estado_proyecto')->where('estadoable_id', $proyecto->id)->where('estadoable_type', Proyecto::class)->delete();

        DB::table('empleado_proyecto')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('actividades')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('resultado_esperado')->where('objetivo_especifico_id', function ($q) use ($proyecto) {
            $q->select('id')->from('objetivo_especifico')->where('proyecto_id', $proyecto->id);
        })->delete();
        DB::table('objetivo_especifico')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('aporte_institucional')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('presupuesto')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('entidad_contraparte_proyecto')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('proyecto_centro_facultad')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('proyecto_ods')->where('proyecto_id', $proyecto->id)->delete();
        DB::table('proyecto_meta_contribuye')->where('proyecto_id', $proyecto->id)->delete();

        $flujoId = $proyecto->flujo_aprobacion_id;
        $proyecto->forceDelete();

        if ($flujoId) {
            DB::table('flujos_aprobacion_etapas')->where('flujo_aprobacion_id', $flujoId)->delete();
            DB::table('flujos_aprobacion')->where('id', $flujoId)->delete();
        }

        $this->limpiarUsuariosYCatalogos();
        Storage::disk('local')->deleteDirectory('constancias/finalizacion/' . date('Y') . '/PROY-PDF-PRUEBA');
    }

    private function limpiarUsuariosYCatalogos(): void
    {
        foreach ([self::EMAIL_COORDINADOR, self::EMAIL_DIRECTOR] as $email) {
            $user = User::withTrashed()->where('email', $email)->first();
            if ($user) {
                $user->roles()->detach();
                $empleado = Empleado::withTrashed()->where('user_id', $user->id)->first();
                if ($empleado) {
                    DB::table('firma_sello_empleado')->where('empleado_id', $empleado->id)->delete();
                    $empleado->forceDelete();
                }
                $user->forceDelete();
            }
        }

        DB::table('centro_facultad')->where('nombre', 'UNAH Choluteca PDF-PRUEBA')->delete();
        DB::table('campus')->where('nombre_campus', 'UNAH Choluteca PDF-PRUEBA')->delete();
        $tipoCargoIds = DB::table('tipo_cargo_firma')->where('nombre', 'like', '%PDF-PRUEBA%')->pluck('id');
        if ($tipoCargoIds->isNotEmpty()) {
            DB::table('cargo_firma')->whereIn('tipo_cargo_firma_id', $tipoCargoIds)->delete();
            DB::table('tipo_cargo_firma')->whereIn('id', $tipoCargoIds)->delete();
        }
        DB::table('cargo_firma')->where('descripcion', 'like', '%PDF-PRUEBA%')->delete();
        DB::table('entidad_contraparte')->where('nombre', 'like', '%PDF-PRUEBA%')->delete();

        $dir = storage_path('app/public/firmas-sellos');
        if (is_dir($dir)) {
            foreach (glob($dir . '/*pdf-prueba*') as $file) {
                @unlink($file);
            }
        }
    }

    private function limpiarInformeFinal(int $informeId): void
    {
        $actividadIds = DB::table('informe_final_actividades')->where('informe_final_proyecto_id', $informeId)->pluck('id');
        if ($actividadIds->isNotEmpty()) {
            DB::table('informe_final_actividad_participantes')->whereIn('informe_final_actividad_id', $actividadIds)->delete();
        }

        foreach ([
            'informe_final_beneficiarios',
            'informe_final_equipo_docente',
            'informe_final_cooperacion',
            'informe_final_estudiantes',
            'informe_final_grupos_estudiantes',
            'informe_final_voluntarios',
            'informe_final_contrapartes',
            'informe_final_resultados',
            'informe_final_actividades',
            'informe_final_acciones_no_ejecutadas',
            'informe_final_acciones_emergentes',
            'informe_final_ods',
            'informe_final_presupuesto_detalles',
            'informe_final_anexos',
            'informe_final_documentos_revision',
        ] as $tabla) {
            DB::table($tabla)->where('informe_final_proyecto_id', $informeId)->delete();
        }
    }

    private function crearEscenarioBase(): Proyecto
    {
        $user = User::create([
            'name' => 'Dorian Adolfo Ordonez Osorto',
            'email' => self::EMAIL_COORDINADOR,
            'password' => bcrypt('password'),
        ]);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        $empleado = Empleado::create([
            'nombre_completo' => 'Dorian Adolfo Ordonez Osorto',
            'numero_empleado' => self::NUMERO_COORD,
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $user->id,
            'tipo_empleado' => 'docente',
        ]);

        $directorUser = User::create([
            'name' => 'Maria Elena Raudales Pineda',
            'email' => self::EMAIL_DIRECTOR,
            'password' => bcrypt('password'),
        ]);
        $directorUser->assignRole($role);

        $directorEmpleado = Empleado::create([
            'nombre_completo' => 'Maria Elena Raudales Pineda',
            'numero_empleado' => self::NUMERO_DIRECTOR,
            'celular' => '98888888',
            'sexo' => 'Femenino',
            'user_id' => $directorUser->id,
            'tipo_empleado' => 'docente',
        ]);

        $this->crearImagenesFirmaSello($directorEmpleado);

        $tipoAccion = VinculacionTipoAccion::firstOrCreate(
            ['codigo' => 'DESARROLLO_LOCAL_REGIONAL'],
            ['nombre' => 'Desarrollo local y regional', 'activo' => true]
        );

        $proyecto = Proyecto::create([
            'tipo_accion_id' => $tipoAccion->id,
            'codigo_proyecto' => self::CODIGO_PROYECTO,
            'nombre_proyecto' => 'Fortalecimiento de la gestion comunitaria mediante herramientas digitales en la Asociacion de Desarrollo de Choluteca',
            'fecha_inicio' => '2026-01-12',
            'fecha_finalizacion' => '2026-11-30',
            'objetivo_general' => 'Mejorar la gestion administrativa y financiera de las organizaciones comunitarias de base mediante la implementacion de herramientas digitales que permitan el registro, control y seguimiento de sus actividades.',
            'poblacion_participante' => 3504,
            'hombres' => 1700,
            'mujeres' => 1804,
            'mestizos_hombres' => 1700,
            'mestizos_mujeres' => 1804,
            'impacto_deseado' => 'Organizaciones comunitarias con mayor capacidad de gestion, transparencia y rendicion de cuentas mediante el uso de tecnologia apropiada.',
            'definicion_problema' => 'Las organizaciones comunitarias de base presentan debilidades en el registro y control de sus actividades administrativas y financieras, limitando su capacidad de gestion y rendicion de cuentas.',
            'alineamiento_reforma' => 'El proyecto se alinea con el eje de vinculacion universidad-sociedad establecido en la reforma universitaria de la UNAH, promoviendo el desarrollo comunitario a traves del transfondo de conocimiento tecnologico.',
            'bibliografia' => 'UNAH (2024). Politica de Vinculacion Universidad-Sociedad. Tegucigalpa: Direccion de Vinculacion Universidad-Sociedad.',
            'total_aporte_institucional' => 200000,
            'metodologia' => 'El proyecto se desarrollo bajo un enfoque de investigacion-accion participativa, combinando talleres de capacitacion con asistencia tecnica permanente.',
        ]);

        EmpleadoProyecto::create([
            'empleado_id' => $empleado->id,
            'proyecto_id' => $proyecto->id,
            'rol' => 'Coordinador',
        ]);

        $now = now();
        $campusId = DB::table('campus')->insertGetId([
            'nombre_campus' => 'UNAH Choluteca PDF-PRUEBA',
            'direccion' => 'Choluteca, Honduras',
            'telefono' => '2782-0000',
            'url' => 'https://www.unah.edu.hn',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $centroId = DB::table('centro_facultad')->insertGetId([
            'nombre' => 'UNAH Choluteca PDF-PRUEBA',
            'es_facultad' => false,
            'siglas' => 'CURLP',
            'campus_id' => $campusId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('proyecto_centro_facultad')->insert([
            'proyecto_id' => $proyecto->id,
            'centro_facultad_id' => $centroId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $contraparte = EntidadContraparte::create([
            'nombre' => 'Asociacion Comunitaria de Desarrollo PDF-PRUEBA',
            'tipo_entidad' => 'sociedad_civil',
            'nombre_contacto' => 'Juan Carlos Mendoza Lopez',
            'cargo_contacto' => 'Presidente Junta Directiva',
            'correo' => 'asociacion.desarrollo@example.test',
            'telefono' => '2782-1111',
        ]);
        EntidadContraparteProyecto::create([
            'proyecto_id' => $proyecto->id,
            'entidad_contraparte_id' => $contraparte->id,
            'nombre' => $contraparte->nombre,
            'tipo_entidad' => $contraparte->tipo_entidad,
            'nombre_contacto' => $contraparte->nombre_contacto,
            'cargo_contacto' => $contraparte->cargo_contacto,
            'correo' => $contraparte->correo,
            'telefono' => $contraparte->telefono,
            'descripcion_acuerdos' => 'Acompanar y validar los resultados del proyecto, facilitar espacios de reunion y proporcionar informacion sobre las necesidades de gestion de la organizacion.',
        ]);

        $objetivo = ObjetivoEspecifico::create([
            'proyecto_id' => $proyecto->id,
            'descripcion' => 'Disenar e implementar una plataforma digital para la gestion administrativa y financiera de la organizacion comunitaria.',
            'orden' => 1,
        ]);
        ResultadoEsperado::create([
            'objetivo_especifico_id' => $objetivo->id,
            'nombre_resultado' => 'Plataforma digital de gestion administrativa implementada y operativa.',
            'nombre_indicador' => 'Una plataforma digital funcional instalada y en uso.',
            'nombre_medio_verificacion' => 'Acta de entrega-recepcion de la plataforma.',
            'plazo' => 'corto_plazo',
            'orden' => 1,
        ]);

        Actividad::create([
            'proyecto_id' => $proyecto->id,
            'descripcion' => 'Levantamiento de requerimientos con la junta directiva de la asociacion.',
            'fecha_inicio' => '2026-01-12',
            'fecha_finalizacion' => '2026-02-15',
            'horas' => 80,
        ]);

        AporteInstitucional::create([
            'proyecto_id' => $proyecto->id,
            'concepto' => 'horas_trabajo_docentes',
            'unidad' => 'hra_profes',
            'cantidad' => 1,
            'costo_unitario' => 200000,
            'costo_total' => 200000,
        ]);

        Presupuesto::create([
            'proyecto_id' => $proyecto->id,
            'aporte_contraparte' => 66792.44,
        ]);

        $estadoNormal = TipoEstado::firstOrCreate(['nombre' => 'En curso']);
        $estadoCierre = TipoEstado::firstOrCreate(['nombre' => 'Revision cierre INF-001']);
        $estadoAprobado = TipoEstado::firstOrCreate(['nombre' => 'Aprobado']);

        $tipoCargoNormal = TipoCargoFirma::firstOrCreate(['nombre' => 'Revisor normal PDF-PRUEBA']);
        $tipoCargoCierre = TipoCargoFirma::firstOrCreate(['nombre' => 'Director Vinculacion']);
        $tipoCargoCoord = TipoCargoFirma::firstOrCreate(['nombre' => 'Coordinador Proyecto PDF-PRUEBA']);

        $cargoNormal = CargoFirma::create([
            'descripcion' => 'Proyecto PDF-PRUEBA',
            'tipo_cargo_firma_id' => $tipoCargoNormal->id,
            'tipo_estado_id' => $estadoNormal->id,
        ]);
        $cargoCierre = CargoFirma::create([
            'descripcion' => 'Proyecto PDF-PRUEBA cierre',
            'tipo_cargo_firma_id' => $tipoCargoCierre->id,
            'tipo_estado_id' => $estadoCierre->id,
        ]);

        $flujo = FlujoAprobacion::create([
            'codigo' => 'PDF-PRUEBA-FLUJO',
            'nombre' => 'Flujo PDF-PRUEBA',
            'proceso' => 'PROYECTO',
            'tipo_accion_id' => $tipoAccion->id,
            'codigo_formulario' => 'FORM-DVUS-001',
            'activo' => true,
        ]);

        $etapaNormal = FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => 1,
            'codigo' => 'PDF-PRUEBA-NORMAL',
            'nombre' => 'Aprobacion normal',
            'tipo_etapa' => 'APROBACION',
            'cargo_firma_id' => $cargoNormal->id,
            'usuario_responsable_id' => $user->id,
            'activo' => true,
            'aplica_inscripcion' => true,
            'aplica_cierre_proyecto' => false,
        ]);
        $etapaCierre = FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => 2,
            'codigo' => 'PDF-PRUEBA-CIERRE',
            'nombre' => 'Aprobacion cierre',
            'tipo_etapa' => 'APROBACION',
            'cargo_firma_id' => $cargoCierre->id,
            'usuario_responsable_id' => $user->id,
            'activo' => true,
            'aplica_inscripcion' => true,
            'aplica_cierre_proyecto' => true,
        ]);

        $proyecto->update(['flujo_aprobacion_id' => $flujo->id]);

        $proyecto->firma_proyecto()->create([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargoNormal->id,
            'estado_revision' => 'Aprobado',
            'hash' => 'pdf-prueba-normal-' . uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapaNormal->id,
            'orden_revision' => 1,
            'etapa_codigo' => $etapaNormal->codigo,
            'etapa_nombre' => $etapaNormal->nombre,
            'revision_ciclo' => 1,
            'fecha_firma' => now(),
        ]);
        $proyecto->firma_proyecto()->create([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $cargoCierre->id,
            'estado_revision' => 'Aprobado',
            'hash' => 'pdf-prueba-cierre-' . uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapaCierre->id,
            'orden_revision' => 2,
            'etapa_codigo' => $etapaCierre->codigo,
            'etapa_nombre' => $etapaCierre->nombre,
            'revision_ciclo' => 1,
            'fecha_firma' => now(),
        ]);

        $this->ponerEstado($proyecto, $empleado, 'En curso');

        return $proyecto->fresh();
    }

    private function crearImagenesFirmaSello(Empleado $empleado): void
    {
        $dir = storage_path('app/public/firmas-sellos');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $firmaPath = 'firmas-sellos/firma-pdf-prueba.png';
        $selloPath = 'firmas-sellos/sello-pdf-prueba.png';

        $this->crearPngFirma($dir . '/firma-pdf-prueba.png');
        $this->crearPngSello($dir . '/sello-pdf-prueba.png');

        FirmaSelloEmpleado::create([
            'empleado_id' => $empleado->id,
            'tipo' => 'firma',
            'ruta_storage' => $firmaPath,
            'estado' => true,
        ]);
        FirmaSelloEmpleado::create([
            'empleado_id' => $empleado->id,
            'tipo' => 'sello',
            'ruta_storage' => $selloPath,
            'estado' => true,
        ]);
    }

    private function crearPngFirma(string $ruta): void
    {
        $img = imagecreatetruecolor(220, 70);
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);
        $ink = imagecolorallocate($img, 20, 30, 80);
        imagestring($img, 5, 15, 8, 'M. E. Raudales Pineda', $ink);
        imagestring($img, 2, 15, 35, 'Director DVUS - UNAH', $ink);
        imagepng($img, $ruta);
        imagedestroy($img);
    }

    private function crearPngSello(string $ruta): void
    {
        $img = imagecreatetruecolor(120, 120);
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);
        $ink = imagecolorallocate($img, 20, 30, 80);
        imageellipse($img, 60, 60, 100, 100, $ink);
        imageellipse($img, 60, 60, 90, 90, $ink);
        imagestring($img, 3, 25, 48, 'DVUS', $ink);
        imagestring($img, 2, 15, 62, 'UNAH', $ink);
        imagepng($img, $ruta);
        imagedestroy($img);
    }

    private function inicializarInforme(Proyecto $proyecto): InformeFinalProyecto
    {
        $this->actingAs($this->user);
        return app(InformeFinalProyectoInitializer::class)->initialize($proyecto, $this->user->id);
    }

    private function actingAs(User $user): void
    {
        auth()->login($user);
    }

    private function enriquecerInforme(InformeFinalProyecto $informe): void
    {
        DB::transaction(function () use ($informe): void {
            $informe->equipoDocente()->delete();
            $informe->cooperacion()->delete();
            $informe->estudiantes()->delete();
            $informe->gruposEstudiantes()->delete();
            $informe->voluntarios()->delete();
            $informe->contrapartes()->delete();
            $informe->resultados()->delete();
            $informe->actividades()->delete();
            $informe->accionesNoEjecutadas()->delete();
            $informe->accionesEmergentes()->delete();
            $informe->ods()->delete();
            $informe->presupuestoDetalles()->delete();
            $informe->anexos()->delete();
            if ($informe->beneficiarios) {
                $informe->beneficiarios()->delete();
            }

            $this->actualizarRootInforme($informe);
            $this->crearBeneficiarios($informe);
            $this->crearEquipoDocente($informe);
            $this->crearEstudiantes($informe);
            $this->crearVoluntarios($informe);
            $this->crearCooperacion($informe);
            $this->crearContrapartes($informe);
            $this->crearResultados($informe);
            $this->crearActividades($informe);
            $this->crearAccionesNoEjecutadas($informe);
            $this->crearAccionesEmergentes($informe);
            $this->crearOds($informe);
            $this->crearPresupuestoDetalles($informe);
            $this->crearAnexos($informe);
        });
    }

    private function actualizarRootInforme(InformeFinalProyecto $informe): void
    {
        $informe->update([
            'estado' => InformeFinalProyecto::ESTADO_COMPLETO,
            'fecha_cierre' => '2026-12-01',
            'confirmacion_veracidad' => true,
            'numero_registro' => self::CODIGO_PROYECTO,
            'transformacion_lograda' => 'La organizacion comunitaria cuenta ahora con una plataforma digital que les permite llevar registro de sus miembros, ingresos, egresos y actividades. Se logro digitalizar el 90% de los procesos que anteriormente se realizaban manualmente. La junta directiva recibio capacitacion y puede operar la herramienta de forma autonoma. Se observo una reduccion del 75% en el tiempo de elaboracion de informes mensuales y un aumento significativo en la transparencia hacia los benefactores.',
            'mecanismos_sostenibilidad' => 'Se conformo un comite local de soporte tecnico integrado por tres miembros de la comunidad capacitados durante el proyecto. La plataforma utiliza software libre por lo que no implica costos de licenciamiento. Se elaboro un manual de usuario en español que fue entregado a la junta directiva. La UNAH mantendra acompanamiento tecnico mediante visitas trimestrales durante el primer año post-cierre.',
            'acciones_contraparte_sostenibilidad' => 'La contraparte se comprometio a asignar un espacio fisico con acceso a internet para el funcionamiento de la plataforma, cubrir los costos de energia electrica y proporcionar el mantenimiento preventivo del equipo informatico entregado.',
            'lecciones_aprendidas' => '1. La participacion activa de la junta directiva desde el levantamiento de requerimientos fue clave para el exito de la herramienta. 2. La capacitacion presencial con ejercicios practicos resulto mas efectiva que las sesiones virtuales. 3. Es fundamental disenar interfaces sencillas adaptadas al nivel de alfabetizacion digital de los usuarios finales. 4. La celebracion de hitos motiva a los participantes y refuerza el compromiso.',
            'buenas_practicas' => '1. Uso de metodologia agil con sprints quincenales. 2. Documentacion continua del proceso mediante actas y reportes fotograficos. 3. Validacion de avances con la comunidad al final de cada sprint. 4. Transferencia de conocimiento gradual mediante talleres de fortalecimiento de capacidades.',
            'problema_inicial' => 'Las organizaciones comunitarias de base presentan debilidades en el registro y control de sus actividades administrativas y financieras, limitando su capacidad de gestion, transparencia y rendicion de cuentas ante sus agremiados y benefactores.',
            'dificultades' => '1. Resistencia inicial al cambio por parte de algunos miembros de la junta directiva. 2. Limitaciones de conectividad a internet en la sede de la asociacion. 3. Dificultades para coordinar reuniones con todos los miembros debido a sus ocupaciones laborales.',
            'acciones_dificultades' => '1. Se realizaron sesiones de sensibilizacion sobre los beneficios de la digitalizacion. 2. Se gestiono con la alcaldia municipal la instalacion de un punto de acceso a internet. 3. Se ajusto el cronograma de reuniones a horarios compatibles con la disponibilidad de los miembros.',
            'desafios' => 'Garantizar la sostenibilidad tecnica de la plataforma mas alla del periodo del proyecto, considerando las limitaciones de recursos de la organizacion comunitaria. Se requiere acompanamiento continuo para asegurar la actualizacion de datos y el mantenimiento del sistema.',
            'respuesta_reforma_universitaria' => 'El proyecto encarna el proposito de la reforma universitaria de la UNAH al vincular la academia con las necesidades reales del desarrollo local. Los estudiantes aplicaron conocimientos de ingenieria de software, bases de datos y gestion de proyectos en un contexto comunitario real, fortaleciendo su formacion profesional con responsabilidad social.',
            'recomendaciones' => '1. Gestionar la firma de un convenio formal entre la UNAH y la asociacion para garantizar el acompanamiento post-cierre. 2. Replicar la experiencia en otras organizaciones comunitarias de la region. 3. Incluir un modulo de capacitacion en alfabetizacion digital como componente previo a la implementacion tecnologica. 4. Evaluar la viabilidad de un sistema de soporte remoto.',
            'bibliografia' => '1. UNAH (2024). Politica de Vinculacion Universidad-Sociedad. Tegucigalpa. 2. Tapia, M. (2023). Gestion comunitaria y tecnologia: experiencias de vinculacion. Editorial Universitaria. 3. CEPAL (2022). Digitalizacion de organizaciones de la sociedad civil. Santiago de Chile. 4. Valladares, R. (2021). Metodologias participativas en proyectos de desarrollo local. Honduras.',
            'observaciones_finales' => 'El proyecto cumplio con los objetivos planteados y demostro el potencial de la vinculacion universidad-sociedad para contribuir al desarrollo comunitario mediante el uso de tecnologia apropiada.',
            'valoracion_total_beneficiarios' => 3504,
            'valoracion_muestra' => 3504,
            'valoracion_excelente' => 2100,
            'valoracion_muy_buena' => 980,
            'valoracion_regular' => 350,
            'valoracion_mala' => 74,
        ]);
    }

    private function crearBeneficiarios(InformeFinalProyecto $informe): void
    {
        InformeFinalBeneficiario::create([
            'informe_final_proyecto_id' => $informe->id,
            'hombres' => 1700,
            'mujeres' => 1804,
            'edad_0_10' => 0,
            'edad_11_18' => 210,
            'edad_19_25' => 1800,
            'edad_26_35' => 620,
            'edad_36_50' => 520,
            'edad_51_65' => 280,
            'edad_66_80' => 74,
            'edad_81_mas' => 0,
            'indigena_hombres' => 0,
            'indigena_mujeres' => 0,
            'afrodescendiente_hombres' => 0,
            'afrodescendiente_mujeres' => 0,
            'mestizo_hombres' => 1700,
            'mestizo_mujeres' => 1804,
        ]);
    }

    private function crearEquipoDocente(InformeFinalProyecto $informe): void
    {
        $coordinador = InformeFinalEquipoDocente::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Dorian Adolfo Ordonez Osorto',
            'numero_empleado' => self::NUMERO_COORD,
            'correo' => self::EMAIL_COORDINADOR,
            'categoria' => 'Tiempo Completo',
            'departamento' => 'Departamento de Informatica',
            'sexo' => 'Masculino',
            'horas_dedicadas' => 320,
            'tipo_participacion' => 'Coordinador',
            'es_coordinador' => true,
            'estado_participacion' => 'activo',
        ]);

        InformeFinalEquipoDocente::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Carmen Rosa Sanchez Martinez',
            'numero_empleado' => '990101',
            'correo' => 'carmen.sanchez@unah.edu.hn',
            'categoria' => 'Tiempo Completo',
            'departamento' => 'Departamento de Informatica',
            'sexo' => 'Femenino',
            'horas_dedicadas' => 180,
            'tipo_participacion' => 'Docente colaborador',
            'es_coordinador' => false,
            'estado_participacion' => 'activo',
        ]);

        InformeFinalEquipoDocente::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Roberto Carlos Mendoza Cruz',
            'numero_empleado' => '990102',
            'correo' => 'roberto.mendoza@unah.edu.hn',
            'categoria' => 'Profesor por Hora',
            'departamento' => 'Departamento de Sistemas',
            'sexo' => 'Masculino',
            'horas_dedicadas' => 120,
            'tipo_participacion' => 'Docente colaborador',
            'es_coordinador' => false,
            'estado_participacion' => 'activo',
        ]);

        InformeFinalEquipoDocente::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Ana Lucia Pineda Funez',
            'numero_empleado' => '990103',
            'correo' => 'ana.pineda@unah.edu.hn',
            'categoria' => 'Personal Administrativo',
            'departamento' => 'Unidad de Vinculacion',
            'sexo' => 'Femenino',
            'horas_dedicadas' => 80,
            'tipo_participacion' => 'Apoyo administrativo',
            'es_coordinador' => false,
            'estado_participacion' => 'activo',
        ]);
    }

    private function crearEstudiantes(InformeFinalProyecto $informe): void
    {
        $grupo1 = InformeFinalGrupoEstudiante::create([
            'informe_final_proyecto_id' => $informe->id,
            'tipo_participacion' => 'practica_asignatura',
            'periodo_academico' => 'PN-2026',
            'hombres_planificados' => 7,
            'mujeres_planificadas' => 5,
        ]);

        $estudiantesPractica = [
            ['Carlos Eduardo Flores Martinez', 'Masculino', '2025-1001-001', 120],
            ['Sofia Isabel Reyes Cruz', 'Femenino', '2025-1001-002', 120],
            ['Jose Daniel Martinez Lopez', 'Masculino', '2025-1001-003', 120],
            ['Valeria Fernanda Castro Pineda', 'Femenino', '2025-1001-004', 120],
            ['Luis Alfredo Herrera Mendoza', 'Masculino', '2025-1001-005', 120],
            ['Mariana Alejandra Torres Funez', 'Femenino', '2025-1001-006', 120],
            ['Diego Alejandro Ponce Cruz', 'Masculino', '2025-1001-007', 120],
            ['Gabriela Rosemary Mejia Ordonez', 'Femenino', '2025-1001-008', 120],
            ['Fernando Jose Maldonado Reyes', 'Masculino', '2025-1001-009', 120],
            ['Daniela Paola Nunez Castillo', 'Femenino', '2025-1001-010', 120],
            ['Andres Felipe Cardona Santos', 'Masculino', '2025-1001-011', 120],
            ['Claudia Maria Bonilla Vasquez', 'Femenino', '2025-1001-012', 120],
        ];

        foreach ($estudiantesPractica as [$nombre, $sexo, $cuenta, $horas]) {
            InformeFinalEstudiante::create([
                'informe_final_proyecto_id' => $informe->id,
                'informe_final_grupo_estudiante_id' => $grupo1->id,
                'nombre' => $nombre,
                'sexo' => $sexo,
                'numero_cuenta' => $cuenta,
                'carrera' => 'Ingenieria en Sistemas',
                'tipo_participacion' => 'practica_asignatura',
                'horas_dedicadas' => $horas,
                'cantidad' => 1,
                'estado_participacion' => 'activo',
            ]);
        }

        $grupo2 = InformeFinalGrupoEstudiante::create([
            'informe_final_proyecto_id' => $informe->id,
            'tipo_participacion' => 'pps_servicio_social',
            'periodo_academico' => 'PN-2026',
            'hombres_planificados' => 4,
            'mujeres_planificadas' => 4,
        ]);

        $estudiantesPPS = [
            ['Mario Roberto Santos Funez', 'Masculino', '2024-2001-001', 240],
            ['Elena Carolina Castillo Mendoza', 'Femenino', '2024-2001-002', 240],
            ['Javier Antonio Mejia Pineda', 'Masculino', '2024-2001-003', 240],
            ['Paola Andrea Funez Cruz', 'Femenino', '2024-2001-004', 240],
            ['Kevin Alexander Lopez Reyes', 'Masculino', '2024-2001-005', 240],
            ['Theresa Maria Raudales Santos', 'Femenino', '2024-2001-006', 240],
            ['Manuel Jose Castro Funez', 'Masculino', '2024-2001-007', 240],
            ['Beatriz Elena Martinez Ponce', 'Femenino', '2024-2001-008', 240],
        ];

        foreach ($estudiantesPPS as [$nombre, $sexo, $cuenta, $horas]) {
            InformeFinalEstudiante::create([
                'informe_final_proyecto_id' => $informe->id,
                'informe_final_grupo_estudiante_id' => $grupo2->id,
                'nombre' => $nombre,
                'sexo' => $sexo,
                'numero_cuenta' => $cuenta,
                'carrera' => 'Ingenieria en Sistemas',
                'tipo_participacion' => 'pps_servicio_social',
                'horas_dedicadas' => $horas,
                'cantidad' => 1,
                'estado_participacion' => 'activo',
            ]);
        }

        $grupo3 = InformeFinalGrupoEstudiante::create([
            'informe_final_proyecto_id' => $informe->id,
            'tipo_participacion' => 'voluntariado',
            'periodo_academico' => 'PN-2026',
            'hombres_planificados' => 3,
            'mujeres_planificadas' => 2,
        ]);

        $estudiantesVol = [
            ['Ricardo Antonio Ponce Martinez', 'Masculino', '2023-3001-001', 60],
            ['Jimena Patricia Santos Reyes', 'Femenino', '2023-3001-002', 60],
            ['Bruno Esteban Funez Castillo', 'Masculino', '2023-3001-003', 60],
            ['Renata Sofia Maldonado Cruz', 'Femenino', '2023-3001-004', 60],
            ['Pablo Andres Mejia Ordonez', 'Masculino', '2023-3001-005', 60],
        ];

        foreach ($estudiantesVol as [$nombre, $sexo, $cuenta, $horas]) {
            InformeFinalEstudiante::create([
                'informe_final_proyecto_id' => $informe->id,
                'informe_final_grupo_estudiante_id' => $grupo3->id,
                'nombre' => $nombre,
                'sexo' => $sexo,
                'numero_cuenta' => $cuenta,
                'carrera' => 'Ingenieria en Sistemas',
                'tipo_participacion' => 'voluntariado',
                'horas_dedicadas' => $horas,
                'cantidad' => 1,
                'estado_participacion' => 'activo',
            ]);
        }
    }

    private function crearVoluntarios(InformeFinalProyecto $informe): void
    {
        InformeFinalVoluntario::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Roberto Carlos Mendoza Cruz',
            'sexo' => 'Masculino',
            'identidad' => '0801-1985-01234',
            'departamento' => 'Departamento de Sistemas',
            'tipo' => 'profesor_hora',
            'horas_dedicadas' => 120,
            'estado_participacion' => 'activo',
        ]);

        InformeFinalVoluntario::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Ana Lucia Pineda Funez',
            'sexo' => 'Femenino',
            'identidad' => '0801-1990-05678',
            'departamento' => 'Unidad de Vinculacion',
            'tipo' => 'pas',
            'horas_dedicadas' => 80,
            'estado_participacion' => 'activo',
        ]);

        InformeFinalVoluntario::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Jorge Antonio Martinez Reyes',
            'sexo' => 'Masculino',
            'identidad' => '0801-1978-09012',
            'departamento' => 'Departamento de Informatica',
            'tipo' => 'profesor_permanente',
            'horas_dedicadas' => 60,
            'estado_participacion' => 'activo',
        ]);
    }

    private function crearCooperacion(InformeFinalProyecto $informe): void
    {
        InformeFinalCooperacion::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Emily Johnson',
            'pasaporte' => 'P12345678',
            'correo' => 'emily.johnson@utexas.edu',
            'pais' => 'Estados Unidos',
            'universidad' => 'University of Texas at Austin',
            'horas_dedicadas' => 40,
            'estado_participacion' => 'activo',
        ]);

        InformeFinalCooperacion::create([
            'informe_final_proyecto_id' => $informe->id,
            'nombre' => 'Jean Dubois',
            'pasaporte' => 'FR9876543',
            'correo' => 'jean.dubois@univ-lyon.fr',
            'pais' => 'Francia',
            'universidad' => 'Universite de Lyon',
            'horas_dedicadas' => 30,
            'estado_participacion' => 'activo',
        ]);
    }

    private function crearContrapartes(InformeFinalProyecto $informe): void
    {
        InformeFinalContraparte::create([
            'informe_final_proyecto_id' => $informe->id,
            'existe_apoyo' => true,
            'nombre' => 'Asociacion Comunitaria de Desarrollo de Choluteca',
            'tipo' => 'sociedad_civil',
            'contacto' => 'Juan Carlos Mendoza Lopez',
            'correo' => 'asociacion.desarrollo@example.test',
            'cargo' => 'Presidente Junta Directiva',
            'telefono' => '2782-1111',
            'tipo_instrumento' => 'carta_formal',
            'compromisos_asumidos' => 'Facilitar espacios de reunion, proporcionar informacion sobre necesidades, asignar miembros de la comunidad para capacitacion, y dar seguimiento a la implementacion de la plataforma.',
            'compromisos_cumplidos' => 'Todos los compromisos se cumplieron satisfactoriamente. La asociacion asigno cinco miembros para capacitacion, facilito el local para talleres y proporciono la informacion requerida para el levantamiento de requerimientos.',
            'territorio' => 'Choluteca, Choluteca',
            'aporte_monetario' => 25000,
            'aporte_especie' => 41792.44,
            'origen' => 'PLANIFICADO',
        ]);

        InformeFinalContraparte::create([
            'informe_final_proyecto_id' => $informe->id,
            'existe_apoyo' => true,
            'nombre' => 'Alcaldia Municipal de Choluteca',
            'tipo' => 'gobierno_municipal',
            'contacto' => 'Lic. Pedro Antonio Santos Cruz',
            'correo' => 'vinculacion@choluteca.gob.hn',
            'cargo' => 'Coordinador de Desarrollo Comunitario',
            'telefono' => '2782-2222',
            'tipo_instrumento' => 'convenio_marco',
            'compromisos_asumidos' => 'Instalacion de punto de acceso a internet en la sede de la asociacion, difusion del proyecto en medios municipales, y apoyo logistico para eventos comunitarios.',
            'compromisos_cumplidos' => 'Se instalo el acceso a internet, se difundo el proyecto en el programa radial municipal y se brindo apoyo logistico para el evento de cierre.',
            'territorio' => 'Choluteca, Choluteca',
            'aporte_monetario' => 15000,
            'aporte_especie' => 5000,
            'origen' => 'PLANIFICADO',
        ]);
    }

    private function crearResultados(InformeFinalProyecto $informe): void
    {
        InformeFinalResultado::create([
            'informe_final_proyecto_id' => $informe->id,
            'objetivo_especifico' => 'Disenar e implementar una plataforma digital para la gestion administrativa y financiera.',
            'resultado_planificado' => 'Plataforma digital de gestion administrativa implementada y operativa.',
            'indicador_propuesto' => 'Una plataforma digital funcional instalada y en uso.',
            'meta_numerica' => 1.00,
            'unidad_medida' => 'Plataforma',
            'valor_alcanzado' => 1.00,
            'porcentaje_cumplimiento' => 100.00,
            'estado' => 'alcanzado',
            'producto_logrado' => 'Plataforma web de gestion administrativa y financiera implementada con modulos de registro de miembros, control de ingresos y egresos, generacion de reportes y seguimiento de actividades.',
            'observaciones' => 'La plataforma fue entregada e instalada en la sede de la asociacion. Se realizo capacitacion a 5 miembros de la junta directiva.',
        ]);

        InformeFinalResultado::create([
            'informe_final_proyecto_id' => $informe->id,
            'objetivo_especifico' => 'Capacitar a los miembros de la junta directiva en el uso de la herramienta digital.',
            'resultado_planificado' => '30 miembros de la comunidad capacitados en el uso de la plataforma.',
            'indicador_propuesto' => 'Numero de miembros capacitados que utilizan la plataforma de forma autonoma.',
            'meta_numerica' => 30.00,
            'unidad_medida' => 'Personas',
            'valor_alcanzado' => 28.00,
            'porcentaje_cumplimiento' => 93.33,
            'estado' => 'parcialmente_alcanzado',
            'producto_logrado' => '28 personas capacitadas en el uso de la plataforma. 2 personas no completaron la capacitacion por motivos de salud.',
            'observaciones' => 'Se logro una tasa de adopcion del 93.3%. Los dos miembros que no completaron la capacitacion recibiran acompanamiento adicional.',
        ]);

        InformeFinalResultado::create([
            'informe_final_proyecto_id' => $informe->id,
            'objetivo_especifico' => 'Elaborar documentacion y manuales de usuario para la sostenibilidad del sistema.',
            'resultado_planificado' => 'Manual de usuario en español entregado a la junta directiva.',
            'indicador_propuesto' => 'Un manual de usuario impreso y digital entregado.',
            'meta_numerica' => 1.00,
            'unidad_medida' => 'Manual',
            'valor_alcanzado' => 1.00,
            'porcentaje_cumplimiento' => 100.00,
            'estado' => 'alcanzado',
            'producto_logrado' => 'Manual de usuario de 45 paginas en español, version impresa y digital, entregado a la junta directiva. Incluye guias paso a paso con capturas de pantalla.',
            'observaciones' => 'El manual fue validado con los usuarios finales durante la ultima sesion de capacitacion.',
        ]);
    }

    private function crearActividades(InformeFinalProyecto $informe): void
    {
        $actividades = [
            [
                'actividad_planificada' => 'Levantamiento de requerimientos con la junta directiva.',
                'actividad_realizada' => 'Se realizaron tres sesiones de levantamiento de requerimientos con la junta directiva. Se identificaron los modulos necesarios: registro de miembros, control financiero, reportes y seguimiento de actividades.',
                'responsable' => 'Dorian Adolfo Ordonez Osorto',
                'fecha_inicial' => '2026-01-12',
                'fecha_final' => '2026-02-15',
                'horas_dedicadas' => 80,
                'medio_verificacion' => 'Acta de reunion de levantamiento de requerimientos.',
                'estado' => 'ejecutada',
                'participantes' => [
                    ['Dorian Adolfo Ordonez Osorto', 'docente', 'Responsable principal', 40, true],
                    ['Carlos Eduardo Flores Martinez', 'estudiante', 'Documentador', 24, false],
                    ['Sofia Isabel Reyes Cruz', 'estudiante', 'Analista', 16, false],
                ],
            ],
            [
                'actividad_planificada' => 'Diseno de la arquitectura de la plataforma.',
                'actividad_realizada' => 'Se diseno la arquitectura de la plataforma utilizando el framework Laravel para el backend y Vue.js para el frontend. Se definio el modelo de datos y la estructura de modulos.',
                'responsable' => 'Carmen Rosa Sanchez Martinez',
                'fecha_inicial' => '2026-02-16',
                'fecha_final' => '2026-03-15',
                'horas_dedicadas' => 120,
                'medio_verificacion' => 'Documento de arquitectura de software.',
                'estado' => 'ejecutada',
                'participantes' => [
                    ['Carmen Rosa Sanchez Martinez', 'docente', 'Responsable principal', 60, true],
                    ['Jose Daniel Martinez Lopez', 'estudiante', 'Desarrollador', 36, false],
                    ['Valeria Fernanda Castro Pineda', 'estudiante', 'Disenadora BD', 24, false],
                ],
            ],
            [
                'actividad_planificada' => 'Desarrollo del modulo de registro de miembros.',
                'actividad_realizada' => 'Se desarrollo el modulo de registro de miembros con funcionalidades de alta, baja, modificacion y consulta. Incluye validacion de datos y exportacion a PDF.',
                'responsable' => 'Carlos Eduardo Flores Martinez',
                'fecha_inicial' => '2026-03-16',
                'fecha_final' => '2026-04-30',
                'horas_dedicadas' => 160,
                'medio_verificacion' => 'Codigo fuente en repositorio Git.',
                'estado' => 'ejecutada',
                'participantes' => [
                    ['Carlos Eduardo Flores Martinez', 'estudiante', 'Responsable principal', 80, true],
                    ['Luis Alfredo Herrera Mendoza', 'estudiante', 'Desarrollador', 48, false],
                    ['Mariana Alejandra Torres Funez', 'estudiante', 'Tester', 32, false],
                ],
            ],
            [
                'actividad_planificada' => 'Desarrollo del modulo de control financiero.',
                'actividad_realizada' => 'Se desarrollo el modulo de control de ingresos y egresos con categorizacion, reportes mensuales y graficos visuales.',
                'responsable' => 'Sofia Isabel Reyes Cruz',
                'fecha_inicial' => '2026-04-15',
                'fecha_final' => '2026-06-15',
                'horas_dedicadas' => 200,
                'medio_verificacion' => 'Codigo fuente en repositorio Git.',
                'estado' => 'ejecutada',
                'participantes' => [
                    ['Sofia Isabel Reyes Cruz', 'estudiante', 'Responsable principal', 100, true],
                    ['Diego Alejandro Ponce Cruz', 'estudiante', 'Desarrollador', 60, false],
                    ['Gabriela Rosemary Mejia Ordonez', 'estudiante', 'Tester', 40, false],
                ],
            ],
            [
                'actividad_planificada' => 'Capacitacion a miembros de la junta directiva.',
                'actividad_realizada' => 'Se realizaron 5 talleres de capacitacion presencial con la junta directiva. Cada taller tuvo una duracion de 4 horas y cubrio un modulo especifico de la plataforma.',
                'responsable' => 'Ana Lucia Pineda Funez',
                'fecha_inicial' => '2026-07-01',
                'fecha_final' => '2026-09-15',
                'horas_dedicadas' => 240,
                'medio_verificacion' => 'Listas de asistencia a talleres y fotografias.',
                'estado' => 'ejecutada',
                'participantes' => [
                    ['Ana Lucia Pineda Funez', 'docente', 'Responsable principal', 80, true],
                    ['Roberto Carlos Mendoza Cruz', 'voluntario', 'Co-facilitador', 60, false],
                    ['Mario Roberto Santos Funez', 'estudiante', 'Apoyo logistico', 40, false],
                    ['Elena Carolina Castillo Mendoza', 'estudiante', 'Apoyo logistico', 40, false],
                ],
            ],
            [
                'actividad_planificada' => 'Pruebas de la plataforma con usuarios finales.',
                'actividad_realizada' => 'Se realizaron pruebas de usabilidad con 15 miembros de la asociacion. Se recolecto feedback y se realizaron ajustes en la interfaz.',
                'responsable' => 'Carmen Rosa Sanchez Martinez',
                'fecha_inicial' => '2026-09-16',
                'fecha_final' => '2026-10-15',
                'horas_dedicadas' => 80,
                'medio_verificacion' => 'Reporte de pruebas de usabilidad.',
                'estado' => 'ejecutada',
                'participantes' => [
                    ['Carmen Rosa Sanchez Martinez', 'docente', 'Responsable principal', 40, true],
                    ['Fernando Jose Maldonado Reyes', 'estudiante', 'Observador', 24, false],
                    ['Daniela Paola Nunez Castillo', 'estudiante', 'Observador', 16, false],
                ],
            ],
            [
                'actividad_planificada' => 'Elaboracion del manual de usuario.',
                'actividad_realizada' => 'Se elaboro un manual de usuario de 45 paginas en español con capturas de pantalla, guias paso a paso y glosario de terminos.',
                'responsable' => 'Ana Lucia Pineda Funez',
                'fecha_inicial' => '2026-10-01',
                'fecha_final' => '2026-10-30',
                'horas_dedicadas' => 60,
                'medio_verificacion' => 'Manual de usuario impreso y digital.',
                'estado' => 'ejecutada',
                'participantes' => [
                    ['Ana Lucia Pineda Funez', 'docente', 'Responsable principal', 30, true],
                    ['Claudia Maria Bonilla Vasquez', 'estudiante', 'Redactora', 20, false],
                    ['Andres Felipe Cardona Santos', 'estudiante', 'Disenador grafico', 10, false],
                ],
            ],
            [
                'actividad_planificada' => 'Evento de entrega y cierre del proyecto.',
                'actividad_realizada' => 'Se realizo el evento de entrega formal de la plataforma y el manual de usuario a la junta directiva. Conto con la participacion de representantes de la alcaldia municipal y la comunidad.',
                'responsable' => 'Dorian Adolfo Ordonez Osorto',
                'fecha_inicial' => '2026-11-20',
                'fecha_final' => '2026-11-30',
                'horas_dedicadas' => 40,
                'medio_verificacion' => 'Acta de entrega-recepcion y album fotografico.',
                'estado' => 'ejecutada',
                'participantes' => [
                    ['Dorian Adolfo Ordonez Osorto', 'docente', 'Responsable principal', 20, true],
                    ['Jorge Antonio Martinez Reyes', 'voluntario', 'Coordinador logistico', 10, false],
                    ['Paola Andrea Funez Cruz', 'estudiante', 'Apoyo', 10, false],
                ],
            ],
        ];

        foreach ($actividades as $data) {
            $participantes = $data['participantes'];
            unset($data['participantes']);

            $data['informe_final_proyecto_id'] = $informe->id;
            $data['origen'] = 'planificada';

            $actividad = InformeFinalActividad::create($data);

            $orden = 0;
            foreach ($participantes as $p) {
                InformeFinalActividadParticipante::create([
                    'informe_final_actividad_id' => $actividad->id,
                    'tipo' => $p[1],
                    'nombre' => $p[0],
                    'rol' => $p[2],
                    'horas_dedicadas' => $p[3],
                    'es_responsable' => $p[4],
                    'orden' => $orden++,
                    'estado_participacion' => 'activo',
                    'origen' => 'PLANIFICADO',
                ]);
            }
        }
    }

    private function crearAccionesNoEjecutadas(InformeFinalProyecto $informe): void
    {
        InformeFinalAccionNoEjecutada::create([
            'informe_final_proyecto_id' => $informe->id,
            'resultado_previsto' => 'Integracion de la plataforma con sistema bancario para conciliacion automatica.',
            'actividad_planificada' => 'Desarrollo de modulo de integracion bancaria via API.',
            'explicacion' => 'La actividad no se ejecuto debido a que el banco local no disponia de una API publica para integracion. Los requisitos de seguridad bancaria exigian un proceso de certificacion que excedia el alcance y el tiempo del proyecto.',
            'afectacion_proyecto' => 'La conciliacion bancaria se realiza manualmente mediante exportacion de datos. No afecta la funcionalidad principal pero requiere tiempo adicional del tesorero de la asociacion.',
            'responsable' => 'Carmen Rosa Sanchez Martinez',
            'impacto' => 'medio',
        ]);
    }

    private function crearAccionesEmergentes(InformeFinalProyecto $informe): void
    {
        InformeFinalAccionEmergente::create([
            'informe_final_proyecto_id' => $informe->id,
            'actividad_realizada' => 'Taller sobre seguridad informatica y buenas practicas en el manejo de contrasenas.',
            'justificacion' => 'Durante las pruebas de usabilidad se detecto que los usuarios tenian practicas inseguras de manejo de contrasenas. Se considero necesario abordar este tema para proteger la informacion de la asociacion.',
            'responsables' => 'Roberto Carlos Mendoza Cruz, Jorge Antonio Martinez Reyes',
            'fecha' => '2026-09-20',
            'horas' => 8,
        ]);

        InformeFinalAccionEmergente::create([
            'informe_final_proyecto_id' => $informe->id,
            'actividad_realizada' => 'Configuracion de respaldo automatico en la nube para la base de datos de la plataforma.',
            'justificacion' => 'Se identifico el riesgo de perdida de informacion por fallas hardware. Se implemento un sistema de respaldo diario automatico en Google Drive como medida de seguridad adicional.',
            'responsables' => 'Carlos Eduardo Flores Martinez, Luis Alfredo Herrera Mendoza',
            'fecha' => '2026-10-10',
            'horas' => 12,
        ]);
    }

    private function crearOds(InformeFinalProyecto $informe): void
    {
        $odsData = [
            [18, 1, 'Fin de la pobreza', 1, 'Meta 1.1: Para 2030, erradicar la pobreza extrema para todas las personas en el mundo.'],
            [22, 2, 'Igualdad de genero', 38, 'Meta 5.1: Poner fin a todas las formas de discriminacion contra todas las mujeres y las niñas.'],
            [26, 3, 'Industria, innovacion e infraestructura', 72, 'Meta 9.1: Desarrollar infraestructuras fiables, sostenibles, resilientes y de calidad.'],
            [34, 4, 'Alianzas para lograr los objetivos', 150, 'Meta 17.1: Fortalecer la movilizacion de recursos internos.'],
        ];

        foreach ($odsData as [$odsId, $orden, $nombre, $metaId, $metaTexto]) {
            InformeFinalOds::create([
                'informe_final_proyecto_id' => $informe->id,
                'ods_id' => $odsId,
                'meta_contribuye_id' => $metaId,
                'meta_ods' => $metaTexto,
                'descripcion_aporte' => 'El proyecto contribuye a esta meta mediante el fortalecimiento de capacidades de gestion en organizaciones comunitarias, promoviendo el uso de tecnologia para el desarrollo local.',
                'evidencia' => 'Plataforma digital implementada, manual de usuario entregado, actas de capacitacion.',
                'nivel_contribucion' => 'directa',
                'origen' => 'PLANIFICADO',
            ]);
        }
    }

    private function crearPresupuestoDetalles(InformeFinalProyecto $informe): void
    {
        InformeFinalPresupuestoDetalle::create([
            'informe_final_proyecto_id' => $informe->id,
            'fuente' => 'UNAH',
            'concepto' => 'Horas de trabajo docente',
            'unidad' => 'hra_profes',
            'cantidad' => 1,
            'costo_unitario' => 200000,
            'origen_fondos' => 'registro_proyecto',
        ]);

        InformeFinalPresupuestoDetalle::create([
            'informe_final_proyecto_id' => $informe->id,
            'fuente' => 'UNAH',
            'concepto' => 'Materiales y suministros de oficina',
            'unidad' => 'lote',
            'cantidad' => 1,
            'costo_unitario' => 15000,
            'origen_fondos' => 'fondos_propios',
        ]);

        InformeFinalPresupuestoDetalle::create([
            'informe_final_proyecto_id' => $informe->id,
            'fuente' => 'UNAH',
            'concepto' => 'Transporte y viaticos',
            'unidad' => 'viaje',
            'cantidad' => 17,
            'costo_unitario' => 500,
            'origen_fondos' => 'fondos_propios',
        ]);

        InformeFinalPresupuestoDetalle::create([
            'informe_final_proyecto_id' => $informe->id,
            'fuente' => 'UNAH',
            'concepto' => 'Impresion y reproduccion de materiales',
            'unidad' => 'lote',
            'cantidad' => 1,
            'costo_unitario' => 3200,
            'origen_fondos' => 'fondos_propios',
        ]);

        InformeFinalPresupuestoDetalle::create([
            'informe_final_proyecto_id' => $informe->id,
            'fuente' => 'CONTRAPARTE',
            'concepto' => 'Aporte en efectivo - Asociacion Comunitaria',
            'unidad' => 'aporte',
            'cantidad' => 1,
            'costo_unitario' => 25000,
            'origen_fondos' => 'contrapartes_proyecto',
        ]);

        InformeFinalPresupuestoDetalle::create([
            'informe_final_proyecto_id' => $informe->id,
            'fuente' => 'CONTRAPARTE',
            'concepto' => 'Aporte en especie - Alcaldia Municipal',
            'unidad' => 'aporte',
            'cantidad' => 1,
            'costo_unitario' => 20000,
            'origen_fondos' => 'contrapartes_proyecto',
        ]);
    }

    private function crearAnexos(InformeFinalProyecto $informe): void
    {
        $anexos = [
            ['fotografias', 'Album fotografico de las actividades del proyecto', null, '2026-11-30'],
            ['actas', 'Acta de entrega-recepcion de la plataforma digital', null, '2026-11-28'],
            ['manuales', 'Manual de usuario de la plataforma digital', null, '2026-10-30'],
            ['encuestas', 'Resultados de encuestas de satisfaccion aplicadas a beneficiarios', null, '2026-11-15'],
            ['materiales', 'Presentaciones utilizadas en talleres de capacitacion', null, '2026-09-15'],
        ];

        $orden = 0;
        foreach ($anexos as [$tipo, $descripcion, $archivo, $fecha]) {
            InformeFinalAnexo::create([
                'informe_final_proyecto_id' => $informe->id,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'fecha' => $fecha,
                'orden' => $orden++,
                'categoria' => 'documento_general',
                'origen' => 'INFORME',
            ]);
        }
    }

    private function ejecutarCierre(Proyecto $proyecto, InformeFinalProyecto $informe): ?ConstanciaFinalizacionProyecto
    {
        $empleado = $this->user->empleado;
        $directorEmpleado = $this->directorUser->empleado;

        $firmaSello = FirmaSelloEmpleado::where('empleado_id', $directorEmpleado->id)->get();
        $firmaId = $firmaSello->where('tipo', 'firma')->first()?->id;
        $selloId = $firmaSello->where('tipo', 'sello')->first()?->id;

        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Final',
            'documento_url' => 'documentos/final-prueba.pdf',
        ]);

        $tipoCargoDirector = TipoCargoFirma::where('nombre', 'Director Vinculacion')->first();
        $cargoCierre = CargoFirma::where('tipo_cargo_firma_id', $tipoCargoDirector->id)->first();

        $documento->firma_documento()->create([
            'empleado_id' => $directorEmpleado->id,
            'cargo_firma_id' => $cargoCierre->id,
            'firma_id' => $firmaId,
            'sello_id' => $selloId,
            'estado_revision' => 'Aprobado',
            'hash' => 'pdf-prueba-doc-cierre-' . uniqid(),
            'flujo_aprobacion_id' => $this->flujo->id,
            'flujo_aprobacion_etapa_id' => $this->etapaCierre->id,
            'orden_revision' => 1,
            'etapa_codigo' => $this->etapaCierre->codigo,
            'etapa_nombre' => $this->etapaCierre->nombre,
            'revision_ciclo' => 1,
            'fecha_firma' => now(),
        ]);

        $tipoCargoCoord = TipoCargoFirma::where('nombre', 'Coordinador Proyecto PDF-PRUEBA')->first();
        if ($tipoCargoCoord) {
            $cargoCoord = CargoFirma::where('tipo_cargo_firma_id', $tipoCargoCoord->id)->first();
            if (! $cargoCoord) {
                $cargoCoord = CargoFirma::create([
                    'descripcion' => 'Proyecto PDF-PRUEBA coord',
                    'tipo_cargo_firma_id' => $tipoCargoCoord->id,
                    'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => 'Aprobado'])->id,
                ]);
            }
            $documento->firma_documento()->create([
                'empleado_id' => $empleado->id,
                'cargo_firma_id' => $cargoCoord->id,
                'estado_revision' => 'Aprobado',
                'hash' => 'pdf-prueba-doc-coord-' . uniqid(),
                'flujo_aprobacion_id' => $this->flujo->id,
                'flujo_aprobacion_etapa_id' => $this->etapaCierre->id,
                'orden_revision' => 2,
                'etapa_codigo' => $this->etapaCierre->codigo,
                'etapa_nombre' => 'Coordinador Proyecto',
                'revision_ciclo' => 1,
                'fecha_firma' => now(),
            ]);
        }

        $this->ponerEstado($documento, $empleado, 'Aprobado');
        $this->ponerEstado($proyecto, $empleado, 'Finalizado');

        $informe->update(['fecha_cierre' => '2026-12-01']);

        $existente = ConstanciaFinalizacionProyecto::where('informe_final_proyecto_id', $informe->id)->first();
        if ($existente && $existente->estado === ConstanciaFinalizacionProyecto::ESTADO_EMITIDA) {
            $this->info('  Constancia ya emitida, reutilizando...');
            return $existente;
        }

        $constancia = app(EmitirConstanciaFinalizacionProyecto::class)->emitir(
            $proyecto->fresh(),
            $informe->fresh(),
            $documento->fresh(),
            $this->user->id
        );

        return $constancia->fresh();
    }

    private function ponerEstado(Proyecto|DocumentoProyecto $model, Empleado $empleado, string $nombre): void
    {
        $tipoEstado = TipoEstado::firstOrCreate(['nombre' => $nombre]);

        $relation = $model instanceof DocumentoProyecto ? 'estado_documento' : 'estado_proyecto';

        $model->{$relation}()->update(['es_actual' => false]);

        EstadoProyecto::withoutEvents(function () use ($model, $relation, $empleado, $tipoEstado): void {
            $model->{$relation}()->create([
                'empleado_id' => $empleado->id,
                'tipo_estado_id' => $tipoEstado->id,
                'fecha' => now(),
                'comentario' => 'Estado de prueba generado por comando.',
                'es_actual' => true,
            ]);
        });
    }

    private function generarInf001(InformeFinalProyecto $informe): void
    {
        $informe = $informe->fresh();
        $generator = app(InformeFinalPdfGenerator::class);
        $pdf = $generator->make($informe);
        $contenido = $pdf->output();

        $ruta = self::DIR_SALIDA . '/INF-001-prueba.pdf';
        Storage::disk('local')->put($ruta, $contenido);
    }

    private function generarConstancia(ConstanciaFinalizacionProyecto $constancia): void
    {
        if ($constancia->estado !== ConstanciaFinalizacionProyecto::ESTADO_EMITIDA || ! $constancia->ruta_archivo) {
            $this->warn('  La constancia no fue emitida correctamente. Estado: ' . $constancia->estado);
            return;
        }

        $contenido = Storage::disk('local')->get($constancia->ruta_archivo);
        $ruta = self::DIR_SALIDA . '/Constancia-Finalizacion-prueba.pdf';
        Storage::disk('local')->put($ruta, $contenido);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}
