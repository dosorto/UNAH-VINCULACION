<?php

namespace Tests\Feature;

use App\Models\Estado\TipoEstado;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proyecto\Actividad;
use App\Models\Proyecto\AporteInstitucional;
use App\Models\Proyecto\ObjetivoEspecifico;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\ResultadoEsperado;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\User;
use App\Services\InformeFinal\InformeFinalPdfGenerator;
use App\Services\InformeFinal\InformeFinalProyectoInitializer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Auditoría de cumplimiento del INF-001 contra el formato oficial impreso
 * («INF-001-INFORME FINAL DE PROGRAMAS Y PROYECTOS DE VINCULACIÓN»,
 * Dirección de Vinculación Universidad-Sociedad).
 *
 * A diferencia de InformeFinalINF001Test —que prueba el comportamiento del
 * wizard— esta clase compara el documento y el modelo de datos contra lo que
 * el formato oficial exige, apartado por apartado. Cada aserción cita el
 * numeral del formato que la respalda.
 */
class InformeFinalInf001FormatoOficialTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Los 12 conceptos del apartado X, «Aporte de la UNAH», tal y como
     * aparecen impresos en el formato oficial.
     */
    private const CONCEPTOS_UNAH_OFICIALES = [
        'a' => 'Horas de trabajo docentes',
        'b' => 'Horas de participación de estudiantes',
        'c' => 'Contratación de personal - consultorías',
        'd' => 'Gastos de alimentación',
        'e' => 'Viáticos / estipendios',
        'f' => 'Gastos de movilización (pasajes aéreos, terrestres)',
        'g' => 'Combustible',
        'h' => 'Útiles y materiales de oficina',
        'i' => 'Gastos de impresión',
        'j' => 'Aportación insumos/materiales adquiridos por estudiantes',
        'k' => 'Costos indirectos por infraestructura universidad',
        'l' => 'Costos indirectos por servicios públicos',
    ];

    /** Los 7 conceptos del apartado X, «Aporte de la contraparte». */
    private const CONCEPTOS_CONTRAPARTE_OFICIALES = [
        'a' => 'Contratación de personal',
        'b' => 'Insumos / materiales',
        'c' => 'Gastos de movilización (combustible, viáticos, transporte)',
        'd' => 'Gastos de hospedaje',
        'e' => 'Gastos de alimentación',
        'f' => 'Gastos de impresión',
        'g' => 'Otros gastos',
    ];

    // ------------------------------------------------------------------
    // Cobertura estructural: lo que el documento sí cumple
    // ------------------------------------------------------------------

    public function test_el_documento_contiene_las_doce_secciones_del_formato(): void
    {
        $html = $this->documento();

        foreach ([
            'I. Información general del proyecto',
            'II. Equipo ejecutor del proyecto',
            'III. Cuantificación de participación de estudiantes',
            'IV. Cuantificación de participación de voluntarios',
            'V. Información de la entidad contraparte del proyecto',
            'VI. Informe de ejecución de las acciones planificadas',
            'VII. Reporte de acciones planificadas que no fueron ejecutadas',
            'VIII. Reporte de acciones emergentes',
            'IX. Reflexión',
            'X. Ejecución presupuestaria',
            'XI. Firmas',
            'XII. Anexos',
        ] as $seccion) {
            $this->assertStringContainsString($seccion, $html, "Falta la sección «{$seccion}» del formato oficial.");
        }
    }

    public function test_seccion_i_cubre_los_diez_numerales(): void
    {
        $html = $this->documento();

        foreach ([
            '1. Nombre del Programa/Proyecto',
            '2. Número de registro',
            '3. Fecha de registro',
            '4. Unidad académica ejecutora',
            'Facultad / Centro Regional / Instituto Tecnológico',
            'Carrera',
            'Programa de vinculación',
            'Línea de investigación',
            '5. Modalidad',
            '6. Alineamiento con ejes prioritarios de la UNAH',
            '7. Categoría del proyecto',
            '8. Plazo de ejecución',
            'Tiempo total del proyecto',
            '9. Beneficiarios directos',
            '10. Sitio de ejecución del proyecto',
        ] as $campo) {
            $this->assertStringContainsString($campo, $html, "Falta «{$campo}» (apartado I).");
        }
    }

    public function test_seccion_i_ofrece_las_cuatro_modalidades_y_los_cuatro_ejes(): void
    {
        $html = $this->documento();

        foreach (['Unidisciplinar', 'Multidisciplinar', 'Interdisciplinar', 'Transdisciplinar'] as $modalidad) {
            $this->assertStringContainsString($modalidad, $html, "Falta la modalidad «{$modalidad}» (apartado I.5).");
        }

        foreach ([
            'Desarrollo económico y social',
            'Democracia y gobernabilidad',
            'Población y condiciones de vida',
            'Ambiente, biodiversidad y desarrollo',
        ] as $eje) {
            $this->assertStringContainsString($eje, $html, "Falta el eje «{$eje}» (apartado I.6).");
        }
    }

    public function test_seccion_i_desglosa_beneficiarios_por_edad_y_etnia(): void
    {
        $html = $this->documento();

        foreach (['0–10', '11–18', '19–25', '26–35', '36–50', '51–65', '66–80', 'Mayor de 81'] as $rango) {
            $this->assertStringContainsString($rango, $html, "Falta el rango de edad «{$rango}» (apartado I.9).");
        }

        foreach (['Indígena', 'Afrodescendiente', 'Mestizo'] as $etnia) {
            $this->assertStringContainsString($etnia, $html, "Falta la etnia «{$etnia}» (apartado I.9).");
        }
    }

    public function test_seccion_ii_incluye_celular_del_coordinador(): void
    {
        // El formato pide CELULAR en la ficha del coordinador. No vive en
        // informe_final_equipo_docente, sino que se lee del empleado; esta
        // prueba fija esa dependencia para que nadie la rompa.
        $html = $this->documento();

        $this->assertStringContainsString('Celular', $html, 'Falta «Celular» del coordinador (apartado II).');
        $this->assertStringContainsString('99887766', $html, 'El celular del coordinador no se resuelve desde el empleado.');
    }

    public function test_seccion_iii_y_iv_cubren_los_tipos_de_participacion_oficiales(): void
    {
        $html = $this->documento();

        foreach (['Práctica de asignatura', 'Servicio Social o PPS', 'Voluntariado'] as $tipo) {
            $this->assertStringContainsString($tipo, $html, "Falta el tipo de participación estudiantil «{$tipo}» (apartado III).");
        }

        foreach (['(PH)', '(PAS)', '(PP)', '(EGR)'] as $tipo) {
            $this->assertStringContainsString($tipo, $html, "Falta el tipo de voluntario «{$tipo}» (apartado IV).");
        }

        $this->assertStringContainsString(
            'bitácora',
            $html,
            'Falta la nota sobre la bitácora de cada estudiante (apartado III).'
        );
    }

    public function test_seccion_ix_cubre_los_once_apartados_de_reflexion(): void
    {
        $html = $this->documento();

        foreach ([
            'Descripción de las dificultades',
            'Acciones realizadas para afrontar las dificultades',
            'Lecciones aprendidas',
            'Buenas prácticas',
            'Problema inicial identificado',
            'Cambios logrados con el proyecto',
            'Objetivos de Desarrollo Sostenible',
            'Mecanismos aplicados para garantizar la sostenibilidad',
            'Acciones ejecutadas por la contraparte',
            'Desafíos',
            'reforma universitaria',
            'Recomendaciones',
            'Bibliografía utilizada',
            'valoración del proyecto por la comunidad beneficiada',
        ] as $apartado) {
            $this->assertStringContainsString($apartado, $html, "Falta «{$apartado}» (apartado IX).");
        }
    }

    public function test_seccion_xi_incluye_los_cuatro_cuadros_de_firma(): void
    {
        $html = $this->documento();

        foreach ([
            'Coordinador del proyecto por la UNAH',
            'Jefe de la Unidad Académica que lidera el proyecto',
            'Coordinador(a) del Comité de Vinculación',
            'Decano(a) o Director(a) del Centro Regional',
        ] as $cuadro) {
            $this->assertStringContainsString($cuadro, $html, "Falta el cuadro de firma «{$cuadro}» (apartado XI).");
        }
    }

    // ------------------------------------------------------------------
    // Apartado X — defectos de cumplimiento
    // ------------------------------------------------------------------

    public function test_el_catalogo_presupuestario_cubre_los_doce_conceptos_del_formato(): void
    {
        // El informe final precarga sus conceptos desde aporte_institucional,
        // cuyo catálogo (AporteInstitucional::getConceptoLabelAttribute) sólo
        // define 7. Los 5 restantes no se pueden registrar de forma tipada.
        $catalogo = $this->catalogoDeConceptosUnah();

        $ausentes = [];
        foreach (self::CONCEPTOS_UNAH_OFICIALES as $letra => $concepto) {
            $clave = $this->palabraClave($concepto);
            $encontrado = collect($catalogo)->contains(
                fn (string $etiqueta) => str_contains(mb_strtolower($etiqueta), $clave)
            );

            if (! $encontrado) {
                $ausentes[] = "{$letra}) {$concepto}";
            }
        }

        $this->assertSame(
            [],
            $ausentes,
            "El catálogo presupuestario no cubre estos conceptos del apartado X:\n  - ".implode("\n  - ", $ausentes)
        );
    }

    public function test_los_costos_indirectos_se_calculan_sobre_horas_docentes_y_estudiantes(): void
    {
        // El formato es explícito: ambos costos indirectos se calculan
        // «sobre la sumatoria de los conceptos a – b», es decir, sólo sobre
        // horas docentes (a) y horas de estudiantes (b).
        [$user, $project] = $this->escenario();
        $informe = $this->initialize($project, $user);

        $informe->presupuestoDetalles()->delete();
        $informe->presupuestoDetalles()->createMany([
            ['fuente' => 'UNAH', 'concepto' => 'a) Horas de trabajo docentes', 'unidad' => 'Hra/profes', 'cantidad' => 1, 'costo_unitario' => 100000],
            ['fuente' => 'UNAH', 'concepto' => 'b) Horas de participación de estudiantes', 'unidad' => 'Hra/estud', 'cantidad' => 1, 'costo_unitario' => 20000],
            ['fuente' => 'UNAH', 'concepto' => 'd) Gastos de alimentación', 'unidad' => 'Global', 'cantidad' => 1, 'costo_unitario' => 50000],
            ['fuente' => 'UNAH', 'concepto' => 'g) Combustible', 'unidad' => 'Global', 'cantidad' => 1, 'costo_unitario' => 30000],
        ]);
        $informe->load('presupuestoDetalles');

        // 3 % de (100 000 + 20 000) = 3 600 por cada costo indirecto.
        $esperado = round((100000 + 20000) * 0.03, 2);

        $this->assertSame(
            $esperado,
            $informe->infraestructura_unah,
            'El 3 % de infraestructura debe calcularse sólo sobre los conceptos a) y b), no sobre todo el subtotal.'
        );
        $this->assertSame(
            $esperado,
            $informe->servicios_unah,
            'El 3 % de servicios públicos debe calcularse sólo sobre los conceptos a) y b), no sobre todo el subtotal.'
        );
    }

    public function test_un_concepto_de_consultoria_no_se_clasifica_como_costo_indirecto(): void
    {
        // Los costos indirectos se detectan buscando las subcadenas
        // «infraestructura» y «servicio» en un concepto de texto libre. El
        // concepto c) del formato —contratación de personal / consultorías—
        // se redacta a menudo como «servicios profesionales» y cae en la
        // coincidencia por accidente.
        [$user, $project] = $this->escenario();
        $informe = $this->initialize($project, $user);

        $informe->presupuestoDetalles()->delete();
        $informe->presupuestoDetalles()->createMany([
            ['fuente' => 'UNAH', 'concepto' => 'a) Horas de trabajo docentes', 'unidad' => 'Hra/profes', 'cantidad' => 1, 'costo_unitario' => 100000],
            ['fuente' => 'UNAH', 'concepto' => 'c) Contratación de servicios profesionales', 'unidad' => 'Global', 'cantidad' => 1, 'costo_unitario' => 40000],
        ]);
        $informe->load('presupuestoDetalles');

        $this->assertSame(
            140000.0,
            $informe->subtotal_unah_base,
            'Una consultoría (concepto c) quedó excluida de la base por contener la palabra «servicios».'
        );
        $this->assertSame(
            3000.0,
            $informe->servicios_unah,
            'Una consultoría (concepto c) se contabilizó como costo indirecto por servicios públicos.'
        );
    }

    public function test_el_aporte_de_contraparte_admite_los_siete_conceptos_del_formato(): void
    {
        [$user, $project] = $this->escenario();
        $informe = $this->initialize($project, $user);

        foreach (self::CONCEPTOS_CONTRAPARTE_OFICIALES as $letra => $concepto) {
            $informe->presupuestoDetalles()->create([
                'fuente' => 'CONTRAPARTE',
                'concepto' => "{$letra}) {$concepto}",
                'unidad' => 'Global',
                'cantidad' => 1,
                'costo_unitario' => 1000,
                'origen_fondos' => 'Fondos propios',
            ]);
        }
        $informe->load('presupuestoDetalles');

        $html = $this->documento($informe);

        foreach (self::CONCEPTOS_CONTRAPARTE_OFICIALES as $letra => $concepto) {
            $this->assertStringContainsString(
                e("{$letra}) {$concepto}"),
                $html,
                "El concepto de contraparte «{$letra}) {$concepto}» no se refleja en el documento."
            );
        }

        $this->assertStringContainsString('Origen de los fondos', $html, 'Falta la columna «Descripción del origen de los fondos» (apartado X).');
    }

    // ------------------------------------------------------------------
    // Apartado VI — defecto de estructura
    // ------------------------------------------------------------------

    public function test_cada_actividad_realizada_se_asocia_a_su_resultado(): void
    {
        // En el formato oficial, «Detalle de las actividades realizadas» es
        // una tabla dentro de cada RESULTADO. El snapshot de actividades no
        // guarda a qué resultado pertenece cada una —a diferencia de las
        // acciones emergentes, que sí lo hacen—, así que el documento las
        // imprime en una única tabla desligada de los resultados.
        $this->assertTrue(
            Schema::hasColumn('informe_final_actividades', 'informe_final_resultado_id'),
            'informe_final_actividades no puede asociar cada actividad a su resultado, '
            .'como exige el apartado VI del formato (informe_final_acciones_emergentes sí lo hace).'
        );
    }

    // ------------------------------------------------------------------
    // Capacidad de los campos de texto libre
    // ------------------------------------------------------------------

    public function test_los_textos_largos_del_proyecto_caben_en_el_informe(): void
    {
        // `programa_pertenece`, `lineas_investigacion_academica` y `region`
        // son LONGTEXT en el proyecto. Cuando sus equivalentes del informe
        // final eran VARCHAR(255), crear el borrador moría con
        // «SQLSTATE[22001] Data too long» y el INF-001 no se podía abrir.
        [$user, $proyecto] = $this->escenario();

        $largo = str_repeat('Programa de vinculación con la sociedad. ', 20); // 800 caracteres
        $proyecto->update([
            'programa_pertenece' => $largo,
            'lineas_investigacion_academica' => $largo,
            'region' => $largo,
        ]);

        $informe = $this->initialize($proyecto->refresh(), $user);

        $this->assertSame(mb_strlen($largo), mb_strlen((string) $informe->programa_vinculacion));
        $this->assertSame(mb_strlen($largo), mb_strlen((string) $informe->linea_investigacion));
        $this->assertSame(mb_strlen($largo), mb_strlen((string) $informe->region));
    }

    // ------------------------------------------------------------------
    // Aplicabilidad — quién puede llegar al formato
    // ------------------------------------------------------------------

    public function test_el_inf001_alcanza_a_las_categorias_de_proyecto_del_formato(): void
    {
        // El apartado I.7 del formato enumera siete categorías de proyecto,
        // entre ellas «Seguimiento a graduados». La lista blanca
        // TIPOS_ACCION_INF_001 sólo admite dos tipos de acción, de modo que
        // el resto recibe un 404 al abrir el informe final.
        $workflow = app(\App\Services\InformeFinal\InformeFinalProyectoWorkflowService::class);

        // EDUCACION_NO_FORMAL y PRESTACION_SERVICIOS_TECNICOS quedan fuera a
        // propósito: tienen formato de cierre propio (ENF y servicio técnico).
        $conCierrePropio = ['EDUCACION_NO_FORMAL', 'PRESTACION_SERVICIOS_TECNICOS'];

        $excluidos = [];
        foreach (\App\Models\Proyecto\VinculacionTipoAccion::query()->pluck('nombre', 'codigo') as $codigo => $nombre) {
            if (in_array($codigo, $conCierrePropio, true)) {
                continue;
            }

            $proyecto = new Proyecto;
            $proyecto->setRelation('tipoAccion', new VinculacionTipoAccion(['codigo' => $codigo]));

            if (! $workflow->aplicaInformeFinalInf001($proyecto)) {
                $excluidos[] = "{$codigo} ({$nombre})";
            }
        }

        $this->assertSame(
            [],
            $excluidos,
            "Estos tipos de acción no pueden abrir el INF-001 pese a no tener formato de cierre propio:\n  - "
            .implode("\n  - ", $excluidos)
        );
    }

    public function test_un_proyecto_sin_tipo_de_accion_puede_cerrar_su_ciclo(): void
    {
        // aplicaInformeFinalInf001() compara contra tipoAccion?->codigo: un
        // proyecto sin tipo de acción nunca entra en la lista blanca. Es el
        // estado en que se encuentran los proyectos heredados, que quedan
        // sin forma de registrar su informe final.
        [$user, $proyecto] = $this->escenario();
        $proyecto->update(['tipo_accion_id' => null]);
        $proyecto->refresh()->load('tipoAccion');

        $workflow = app(\App\Services\InformeFinal\InformeFinalProyectoWorkflowService::class);

        $this->assertTrue(
            $workflow->aplicaInformeFinalInf001($proyecto),
            'Un proyecto sin tipo de acción no puede abrir el INF-001; los proyectos heredados '
            .'necesitan un tipo por defecto o una regla de respaldo.'
        );
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    private function documento(?InformeFinalProyecto $informe = null): string
    {
        if (! $informe) {
            [$user, $project] = $this->escenario();
            $informe = $this->initialize($project, $user);
        }

        $datos = app(InformeFinalPdfGenerator::class)->viewData($informe, false);

        return view('proyectos.informe-final.partials.inf-001-document', $datos)->render();
    }

    /** Etiquetas del catálogo de conceptos que alimenta el informe final. */
    private function catalogoDeConceptosUnah(): array
    {
        $modelo = new AporteInstitucional;
        $claves = [
            'horas_trabajo_docentes',
            'horas_trabajo_estudiantes',
            'contratacion_personal',
            'gastos_alimentacion',
            'viaticos_estipendios',
            'gastos_movilizacion',
            'combustible',
            'utiles_materiales_oficina',
            'gastos_impresion',
            'insumos_estudiantes',
            'costos_indirectos_infraestructura',
            'costos_indirectos_servicios',
        ];

        $etiquetas = [];
        foreach ($claves as $clave) {
            $modelo->concepto = $clave;
            $etiqueta = $modelo->concepto_label;
            // El accesor devuelve la clave sin traducir cuando no está en el catálogo.
            if ($etiqueta !== $clave) {
                $etiquetas[$clave] = $etiqueta;
            }
        }

        return $etiquetas;
    }

    /** Palabra distintiva de cada concepto oficial, para comparar sin depender de la redacción. */
    private function palabraClave(string $concepto): string
    {
        return match (true) {
            str_contains($concepto, 'docentes') => 'docentes',
            str_contains($concepto, 'estudiantes') && str_contains($concepto, 'Horas') => 'estudiantes',
            str_contains($concepto, 'consultorías') => 'consultor',
            str_contains($concepto, 'alimentación') => 'alimentación',
            str_contains($concepto, 'Viáticos') => 'viático',
            str_contains($concepto, 'movilización') => 'movilización',
            str_contains($concepto, 'Combustible') => 'combustible',
            str_contains($concepto, 'Útiles') => 'útiles',
            str_contains($concepto, 'impresión') => 'impresión',
            str_contains($concepto, 'insumos') => 'insumos',
            str_contains($concepto, 'infraestructura') => 'infraestructura',
            default => 'servicios públicos',
        };
    }

    private function initialize(Proyecto $project, User $user): InformeFinalProyecto
    {
        return app(InformeFinalProyectoInitializer::class)->initialize($project, $user->id);
    }

    /**
     * Escenario mínimo pero completo: proyecto en curso, coordinador con
     * celular, un resultado, una actividad y un aporte institucional.
     */
    private function escenario(): array
    {
        $user = User::factory()->create([
            'name' => 'Coordinadora de vinculación',
            'email' => 'formato.oficial.'.uniqid().'@example.test',
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        $empleado = Empleado::create([
            'nombre_completo' => 'Coordinadora de vinculación',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99887766',
            'sexo' => 'Femenino',
            'user_id' => $user->id,
            'tipo_empleado' => 'docente',
        ]);

        $tipo = VinculacionTipoAccion::firstOrCreate(
            ['codigo' => 'DESARROLLO_LOCAL_REGIONAL'],
            ['nombre' => 'Desarrollo local y regional', 'activo' => true]
        );

        $proyecto = Proyecto::create([
            'tipo_accion_id' => $tipo->id,
            'codigo_proyecto' => 'PROY-FMT-'.uniqid(),
            'nombre_proyecto' => 'Auditoría de cumplimiento del formato INF-001',
            'fecha_inicio' => '2026-01-12',
            'fecha_finalizacion' => '2026-11-30',
            'objetivo_general' => 'Verificar el cumplimiento del formato oficial',
            'poblacion_participante' => 3504,
            'hombres' => 1700,
            'mujeres' => 1804,
            'mestizos_hombres' => 1700,
            'mestizos_mujeres' => 1804,
            'impacto_deseado' => 'Formato conforme',
            'total_aporte_institucional' => 200000,
        ]);

        EmpleadoProyecto::create([
            'empleado_id' => $empleado->id,
            'proyecto_id' => $proyecto->id,
            'rol' => 'Coordinador',
        ]);

        $ahora = now();
        $campus = DB::table('campus')->insertGetId([
            'nombre_campus' => 'UNAH Formato '.uniqid(),
            'direccion' => 'Tegucigalpa',
            'telefono' => '00000000',
            'url' => 'https://unah.edu.hn',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
        $centro = DB::table('centro_facultad')->insertGetId([
            'nombre' => 'Facultad de Ingeniería',
            'es_facultad' => true,
            'siglas' => 'FI',
            'campus_id' => $campus,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
        DB::table('proyecto_centro_facultad')->insert([
            'proyecto_id' => $proyecto->id,
            'centro_facultad_id' => $centro,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        $objetivo = ObjetivoEspecifico::create([
            'proyecto_id' => $proyecto->id,
            'descripcion' => 'Fortalecer la gestión comunitaria',
            'orden' => 1,
        ]);
        ResultadoEsperado::create([
            'objetivo_especifico_id' => $objetivo->id,
            'nombre_resultado' => 'Plataforma disponible',
            'nombre_indicador' => 'Una plataforma implementada',
            'nombre_medio_verificacion' => 'Acta de entrega',
            'plazo' => 'corto_plazo',
            'orden' => 1,
        ]);

        Actividad::create([
            'proyecto_id' => $proyecto->id,
            'descripcion' => 'Levantamiento de requerimientos',
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

        Presupuesto::create(['proyecto_id' => $proyecto->id, 'aporte_contraparte' => 66792.44]);

        $estado = TipoEstado::firstOrCreate(['nombre' => 'En curso']);
        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $estado->id,
            'fecha' => now(),
            'comentario' => 'Proyecto en ejecución.',
            'es_actual' => true,
        ]);

        return [$user, $proyecto];
    }
}
