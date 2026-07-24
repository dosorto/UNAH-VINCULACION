<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Estudiante\Estudiante;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proyecto\Actividad;
use App\Models\Proyecto\Anexo;
use App\Models\Proyecto\AporteInstitucional;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\EjesPrioritariosUnah;
use App\Models\Proyecto\EntidadContraparte;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\InstrumenFormalizacion;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\ObjetivoEspecifico;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\ResultadoEsperado;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\InformeFinal\InformeFinalProyectoInitializer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProyectoTransformacionDigitalSeeder extends Seeder
{
    use WithoutModelEvents;

    public const PROJECT_CODE = 'NEXO-TEST-TD-OROCUINA';

    public const BASE_PROJECT_NAME = 'Transformación Digital en patronatos y juntas de agua en barrios de Orocuina: Un Sistema de Información para la administración fácil y transparente de fondos en las comunidades';

    public const FLOW_CODE = 'PROYECTO_FORM_DVUS_001';

    private const PASSWORD = 'Prueba1234!';

    private const FILE_PREFIX = 'testing/proyecto-transformacion-digital';

    private const SCENARIOS = [
        'borrador',
        'revision',
        'subsanacion',
        'en_curso',
        'inf001_borrador',
        'inf001_revision',
        'finalizado',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Este seeder solo puede ejecutarse en entornos local o testing.');
        }

        $scenario = Str::lower(trim((string) env('NEXO_SEED_SCENARIO', 'en_curso')));

        if (! in_array($scenario, self::SCENARIOS, true)) {
            throw new RuntimeException(sprintf(
                'Escenario "%s" no válido. Use: %s.',
                $scenario,
                implode(', ', self::SCENARIOS)
            ));
        }

        DB::transaction(function () use ($scenario): void {
            $catalogs = $this->resolveCatalogs();
            $people = $this->seedPeople($catalogs['centro'], $catalogs['departamento_academico']);
            $flow = $this->resolveFlow($catalogs['tipo_accion']);
            $project = $this->seedProyectoBase($catalogs, $flow, $people);

            $this->seedEquipo($project, $people);
            $this->seedContraparte($project);
            $this->seedGrupoEstudiantes($project, $catalogs, $people);
            $this->seedActividades($project, $people);
            $this->seedMarcoLogico($project);
            $this->seedPresupuesto($project);
            $this->seedAnexos($project);
            $this->seedScenario($project, $scenario, $people);
        }, 3);
    }

    private function resolveCatalogs(): array
    {
        $tipoAccion = VinculacionTipoAccion::firstOrCreate(
            ['codigo' => 'DESARROLLO_LOCAL_REGIONAL'],
            ['nombre' => 'Desarrollo local y regional', 'activo' => true, 'orden' => 1]
        );
        $modalidad = Modalidad::firstOrCreate(['nombre' => 'Unidisciplinar']);
        $centro = FacultadCentro::query()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%unah%choluteca%'])
            ->orderBy('id')
            ->first();
        $departamentoAcademico = DepartamentoAcademico::query()
            ->where('centro_facultad_id', $centro?->id)
            ->whereRaw('LOWER(nombre) LIKE ?', ['%sistemas%choluteca%'])
            ->orderBy('id')
            ->first();
        $carrera = Carrera::query()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%sistemas%choluteca%'])
            ->orderBy('id')
            ->first();

        if (! $centro || ! $departamentoAcademico || ! $carrera) {
            throw new RuntimeException(
                'No se encontró la unidad académica de Ingeniería en Sistemas de UNAH Choluteca. Ejecute primero los seeders académicos.'
            );
        }

        $categoria = Categoria::firstOrCreate(['nombre' => 'Desarrollo Local']);
        $eje = EjesPrioritariosUnah::firstOrCreate(['nombre' => 'Población y condiciones de vida']);
        $ods = Od::firstOrCreate(['nombre' => '11. Ciudades y comunidades sostenibles']);
        $departamento = \App\Models\Demografia\Departamento::query()
            ->where('nombre', 'Choluteca')
            ->firstOrFail();
        $municipio = \App\Models\Demografia\Municipio::query()
            ->where('nombre', 'Orocuina')
            ->where('departamento_id', $departamento->id)
            ->firstOrFail();
        $asignatura = Asignatura::updateOrCreate(
            ['codigo' => 'IS-802'],
            [
                'nombre' => 'Ingeniería de Software',
                'carrera_id' => $carrera->id,
                'departamento_academico_id' => $departamentoAcademico->id,
                'creditos_academicos' => 4,
                'horas_academicas' => 80,
                'activa' => true,
            ]
        );

        return compact(
            'tipoAccion',
            'modalidad',
            'centro',
            'departamentoAcademico',
            'carrera',
            'categoria',
            'eje',
            'ods',
            'departamento',
            'municipio',
            'asignatura'
        ) + [
            'tipo_accion' => $tipoAccion,
            'departamento_academico' => $departamentoAcademico,
        ];
    }

    private function seedPeople(FacultadCentro $centro, DepartamentoAcademico $departamento): array
    {
        return [
            'coordinador' => $this->upsertPerson(
                'Coordinador Transformación Digital',
                'coordinador.transformacion@nexo.test',
                'NEXO-TD-001',
                ['Coordinador Proyecto'],
                $centro,
                $departamento,
                'Masculino'
            ),
            'revisor' => $this->upsertPerson(
                'Revisor Vinculación de Prueba',
                'revisor.vinculacion@nexo.test',
                'NEXO-TD-002',
                ['Jefe Departamento', 'Revisor Vinculacion'],
                $centro,
                $departamento,
                'Femenino'
            ),
            'director' => $this->upsertPerson(
                'Director Vinculación de Prueba',
                'director.vinculacion@nexo.test',
                'NEXO-TD-003',
                ['Director centro', 'Director Vinculacion'],
                $centro,
                $departamento,
                'Masculino'
            ),
        ];
    }

    private function upsertPerson(
        string $name,
        string $email,
        string $employeeNumber,
        array $roleNames,
        FacultadCentro $centro,
        DepartamentoAcademico $departamento,
        string $sexo
    ): Empleado {
        $roles = collect($roleNames)
            ->map(fn (string $roleName) => Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]));
        $user = User::withTrashed()->where('email', $email)->first() ?? new User();

        if ($user->trashed()) {
            $user->restore();
        }

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'active_role_id' => $roles->first()->id,
        ])->save();
        $user->syncRoles($roles);

        if ($email === 'coordinador.transformacion@nexo.test') {
            $permission = Permission::firstOrCreate([
                'name' => 'docente.crear-proyecto',
                'guard_name' => 'web',
            ]);
            $user->givePermissionTo($permission);
        }

        $employee = Empleado::withTrashed()
            ->where('numero_empleado', $employeeNumber)
            ->first() ?? new Empleado();

        if ($employee->exists && (int) $employee->user_id !== (int) $user->id) {
            throw new RuntimeException("El número de empleado de prueba {$employeeNumber} pertenece a otro usuario.");
        }

        if ($employee->trashed()) {
            $employee->restore();
        }

        $employee->forceFill([
            'user_id' => $user->id,
            'nombre_completo' => $name,
            'numero_empleado' => $employeeNumber,
            'celular' => '99999999',
            'jornada_laboral' => 'Seeder de pruebas',
            'sexo' => $sexo,
            'centro_facultad_id' => $centro->id,
            'departamento_academico_id' => $departamento->id,
            'tipo_empleado' => 'docente',
        ])->save();

        return $employee->fresh('user.roles');
    }

    private function resolveFlow(VinculacionTipoAccion $tipoAccion): FlujoAprobacion
    {
        $flow = FlujoAprobacion::where('codigo', self::FLOW_CODE)->first();

        if ($flow) {
            return $flow;
        }

        $flow = FlujoAprobacion::create([
            'codigo' => self::FLOW_CODE,
            'nombre' => 'Flujo FORM-DVUS-001 - Desarrollo local y regional',
            'proceso' => 'PROYECTO',
            'tipo_accion_id' => $tipoAccion->id,
            'codigo_formulario' => 'FORM-DVUS-001',
            'descripcion' => 'Flujo reproducible de desarrollo para FORM-DVUS-001.',
            'activo' => true,
        ]);

        $reviewState = TipoEstado::firstOrCreate(['nombre' => 'En revision']);
        $closureState = TipoEstado::firstOrCreate(['nombre' => 'Revisión cierre INF-001']);
        $type = TipoCargoFirma::firstOrCreate(['nombre' => 'Revisor Vinculacion']);
        $normalCargo = CargoFirma::firstOrCreate(
            ['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $type->id],
            ['tipo_estado_id' => $reviewState->id]
        );
        $closureCargo = CargoFirma::firstOrCreate(
            ['descripcion' => 'Informe Final', 'tipo_cargo_firma_id' => $type->id],
            ['tipo_estado_id' => $closureState->id]
        );
        $definitions = [
            ['NORMAL_COORDINACION', 'Revisión de coordinación', 'Coordinador Proyecto', $normalCargo, false],
            ['NORMAL_JEFATURA', 'Revisión de jefatura', 'Jefe Departamento', $normalCargo, false],
            ['NORMAL_DIRECCION', 'Revisión de dirección de centro', 'Director centro', $normalCargo, false],
            ['CIERRE_REVISION', 'Revisión de cierre', 'Revisor Vinculacion', $closureCargo, true],
            ['CIERRE_DIRECCION', 'Aprobación final de cierre', 'Director Vinculacion', $closureCargo, true],
        ];

        foreach ($definitions as $index => [$code, $name, $roleName, $cargo, $closure]) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $flow->etapas()->create([
                'orden' => $index + 1,
                'codigo' => $code,
                'nombre' => $name,
                'tipo_etapa' => 'APROBACION',
                'rol_revisor_id' => $role->id,
                'cargo_firma_id' => $cargo->id,
                'activo' => true,
                'aplica_inscripcion' => true,
                'aplica_cierre_proyecto' => $closure,
                'aplica_informe_intermedio' => false,
                'permite_edicion' => true,
                'permite_rechazo' => true,
                'alcance_academico' => FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
                'multiplicidad_revision' => FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO,
            ]);
        }

        return $flow->fresh('etapas');
    }

    private function seedProyectoBase(array $catalogs, FlujoAprobacion $flow, array $people): Proyecto
    {
        $project = Proyecto::withTrashed()->where('codigo_proyecto', self::PROJECT_CODE)->first();

        if (! $project) {
            $project = new Proyecto(['codigo_proyecto' => self::PROJECT_CODE]);
        }

        if ($project->trashed()) {
            $project->restore();
        }

        $project->fill([
            'nombre_proyecto' => self::BASE_PROJECT_NAME.' [PRUEBA]',
            'tipo_accion_id' => $catalogs['tipo_accion']->id,
            'modalidad_id' => $catalogs['modalidad']->id,
            'programa_pertenece' => 'Proyecto de desarrollo local y transformación digital',
            'lineas_investigacion_academica' => 'Transformación Digital',
            'resumen' => 'Proyecto de prueba para analizar, diseñar y desarrollar una aplicación de gestión comunitaria para patronatos y juntas administradoras de agua.',
            'descripcion_participantes' => 'Participan docentes y estudiantes de Ingeniería en Sistemas de UNAH Choluteca junto con una contraparte comunitaria ficticia.',
            'definicion_problema' => 'Los procesos administrativos y de control se gestionan de forma manual y mediante registros dispersos.',
            'objetivo_general' => 'Fortalecer la gestión administrativa comunitaria mediante una aplicación informática para patronatos y juntas administradoras de agua.',
            'fecha_inicio' => '2026-01-12',
            'fecha_finalizacion' => '2026-11-30',
            'poblacion_participante' => 3504,
            'hombres' => 1736,
            'mujeres' => 1768,
            'indigenas_hombres' => 0,
            'indigenas_mujeres' => 0,
            'afroamericanos_hombres' => 0,
            'afroamericanos_mujeres' => 0,
            'mestizos_hombres' => 1736,
            'mestizos_mujeres' => 1768,
            'pais' => ['Honduras'],
            'region' => [],
            'aldea' => 'No aplica',
            'caserio' => 'No aplica',
            'impacto_deseado' => 'Una aplicación funcional y procesos comunitarios más transparentes.',
            'alineamiento_reforma' => 'Fortalece la vinculación Universidad-Sociedad y la pertinencia social del quehacer universitario.',
            'metodologia' => 'Metodología aplicada y participativa con levantamiento de información y desarrollo ágil.',
            'bibliografia' => 'Pressman y Maxim, Ingeniería del software; Sommerville, Ingeniería del software.',
            'total_aporte_institucional' => 259792.44,
            'fecha_registro' => '2026-01-12',
            'flujo_aprobacion_id' => $flow->id,
            'responsable_revision_id' => $people['revisor']->id,
        ]);
        $project->save();

        $this->syncPivot('proyecto_centro_facultad', [
            'proyecto_id' => $project->id,
            'centro_facultad_id' => $catalogs['centro']->id,
        ]);
        $this->syncPivot('proyecto_depto_ac', [
            'proyecto_id' => $project->id,
            'departamento_academico_id' => $catalogs['departamento_academico']->id,
        ]);
        $this->syncPivot('proyecto_carrera', [
            'proyecto_id' => $project->id,
            'carrera_id' => $catalogs['carrera']->id,
        ]);
        $this->syncPivot('proyecto_categoria', [
            'proyecto_id' => $project->id,
            'categoria_id' => $catalogs['categoria']->id,
        ]);
        $this->syncPivot('eje_prioritario_proyecto', [
            'proyecto_id' => $project->id,
            'ejes_prioritarios_unah_id' => $catalogs['eje']->id,
        ]);
        $this->syncPivot('proyecto_ods', [
            'proyecto_id' => $project->id,
            'ods_id' => $catalogs['ods']->id,
        ]);
        $this->syncPivot('proyecto_departamento', [
            'proyecto_id' => $project->id,
            'departamento_id' => $catalogs['departamento']->id,
        ]);
        $this->syncPivot('proyecto_municipio', [
            'proyecto_id' => $project->id,
            'municipio_id' => $catalogs['municipio']->id,
        ]);
        $this->syncPivot('proyecto_asignatura', [
            'proyecto_id' => $project->id,
            'asignatura_id' => $catalogs['asignatura']->id,
        ]);

        return $project->fresh();
    }

    private function seedEquipo(Proyecto $project, array $people): void
    {
        foreach ([
            [$people['coordinador'], 'Coordinador'],
            [$people['revisor'], 'Integrante'],
            [$people['director'], 'Integrante'],
        ] as [$employee, $role]) {
            EmpleadoProyecto::updateOrCreate(
                ['proyecto_id' => $project->id, 'empleado_id' => $employee->id],
                ['rol' => $role]
            );
        }
    }

    private function seedContraparte(Proyecto $project): void
    {
        $counterpart = EntidadContraparte::updateOrCreate(
            ['proyecto_id' => $project->id, 'nombre' => 'Patronato Pro Mejoramiento de Orocuina'],
            [
                'tipo_entidad' => 'sociedad_civil',
                'nombre_contacto' => 'Representante Comunitaria de Prueba',
                'cargo_contacto' => 'Presidencia',
                'correo' => 'patronato.orocuina@nexo.test',
                'telefono' => '00000000',
                'descripcion_acuerdos' => 'Facilitar información, validar requerimientos, participar en capacitaciones y aportar L 7,000.00 en logística.',
            ]
        );

        InstrumenFormalizacion::updateOrCreate(
            [
                'entidad_contraparte_id' => $counterpart->id,
                'tipo_documento' => 'carta_intenciones',
            ],
            [
                'documento_url' => self::FILE_PREFIX.'/carta-intenciones-prueba.pdf',
                'nombre_archivo' => 'carta-intenciones-prueba.pdf',
            ]
        );
        $this->writeDummyPdf('carta-intenciones-prueba.pdf');
    }

    private function seedGrupoEstudiantes(Proyecto $project, array $catalogs, array $people): void
    {
        $student = Estudiante::withTrashed()->where('cuenta', 'NEXO-TD-0001')->first()
            ?? new Estudiante();

        if ($student->trashed()) {
            $student->restore();
        }

        $student->fill([
            'nombre' => 'Estudiante',
            'apellido' => 'Transformación Digital',
            'cuenta' => 'NEXO-TD-0001',
            'sexo' => 'Masculino',
            'user_id' => $people['coordinador']['user']->id,
            'centro_facultad_id' => $catalogs['centro']->id,
            'carrera_id' => $catalogs['carrera']->id,
        ])->save();

        EstudianteProyecto::updateOrCreate(
            [
                'proyecto_id' => $project->id,
                'estudiante_id' => $student->id,
                'tipo_participacion_estudiante' => 'Practica Asignatura',
            ],
            [
                'carrera_id' => $catalogs['carrera']->id,
                'asignatura_id' => $catalogs['asignatura']->id,
                'periodo_academico_id' => 'Primer Periodo',
                'cantidad_estudiantes_hombres' => 4,
                'cantidad_estudiantes_mujeres' => 0,
                'total_estudiantes' => 4,
            ]
        );
    }

    private function seedActividades(Proyecto $project, array $people): void
    {
        $activities = [
            ['Formulación de la propuesta', '2026-01-12', '2026-01-30', 40],
            ['Negociación inicial con cooperantes y beneficiarios', '2026-01-20', '2026-02-15', 30],
            ['Organización de equipos de trabajo', '2026-02-01', '2026-02-20', 20],
            ['Levantamiento y análisis de requerimientos', '2026-02-15', '2026-03-31', 80],
            ['Planificación del proyecto', '2026-03-01', '2026-03-20', 30],
            ['Diseño del sistema y base de datos', '2026-03-21', '2026-04-30', 100],
            ['Desarrollo de la aplicación', '2026-05-01', '2026-08-31', 360],
            ['Presentación de avances e informe intermedio', '2026-07-01', '2026-07-15', 24],
            ['Pruebas piloto y ajustes', '2026-09-01', '2026-09-30', 80],
            ['Capacitación a usuarios', '2026-10-01', '2026-10-15', 40],
            ['Mantenimiento y soporte técnico', '2026-10-16', '2026-11-15', 60],
            ['Encuestas de satisfacción', '2026-11-01', '2026-11-20', 24],
            ['Entrega e informe final', '2026-11-21', '2026-11-30', 40],
        ];

        foreach ($activities as [$description, $start, $end, $hours]) {
            $activity = Actividad::updateOrCreate(
                ['proyecto_id' => $project->id, 'descripcion' => $description],
                ['fecha_inicio' => $start, 'fecha_finalizacion' => $end, 'horas' => $hours]
            );

            foreach ([$people['coordinador'], $people['revisor']] as $employee) {
                $this->syncPivot('actividad_empleado', [
                    'actividad_id' => $activity->id,
                    'empleado_id' => $employee->id,
                ]);
            }
        }
    }

    private function seedMarcoLogico(Proyecto $project): void
    {
        $rows = [
            ['Analizar los requerimientos funcionales y no funcionales.', 'Requerimientos del sistema identificados y documentados.', 'Un documento validado', 'Documento de requerimientos'],
            ['Diseñar la arquitectura y el modelo de base de datos.', 'Diseño técnico y modelo de datos disponibles.', 'Un diseño aprobado', 'Documento de diseño'],
            ['Desarrollar una aplicación para la gestión administrativa.', 'Aplicación desarrollada con módulos funcionales.', 'Una aplicación funcional', 'Repositorio y acta de demostración'],
            ['Validar el funcionamiento mediante pruebas piloto.', 'Sistema validado y ajustado.', 'Una prueba piloto completada', 'Informe de pruebas'],
            ['Capacitar al personal comunitario.', 'Personal capacitado en el uso del sistema.', 'Dos jornadas realizadas', 'Listas de asistencia'],
            ['Comparar resultados con la línea base.', 'Informe final del proyecto elaborado.', 'Un informe presentado', 'INF-001'],
        ];

        foreach ($rows as $index => [$objectiveText, $resultText, $indicator, $verification]) {
            $objective = ObjetivoEspecifico::updateOrCreate(
                ['proyecto_id' => $project->id, 'orden' => $index + 1],
                ['descripcion' => $objectiveText]
            );
            ResultadoEsperado::updateOrCreate(
                ['objetivo_especifico_id' => $objective->id, 'orden' => 1],
                [
                    'nombre_resultado' => $resultText,
                    'nombre_indicador' => $indicator,
                    'nombre_medio_verificacion' => $verification,
                    'plazo' => $index < 2 ? 'corto_plazo' : ($index < 4 ? 'mediano_plazo' : 'largo_plazo'),
                ]
            );
        }
    }

    private function seedPresupuesto(Proyecto $project): void
    {
        $contributions = [
            ['horas_trabajo_docentes', 'hra_profes', 1232, 186, 229152],
            ['horas_trabajo_estudiantes', 'hra_estud', 360, 58, 20880],
            ['gastos_movilizacion', 'global', 5, 500, 2500],
            ['utiles_materiales_oficina', 'global', 5, 100, 500],
            ['gastos_impresion', 'global', 0, 0, 0],
            ['costos_indirectos_infraestructura', 'porcentaje', 80.10, 42.20, 3380.22],
            ['costos_indirectos_servicios', 'porcentaje', 80.10, 42.20, 3380.22],
        ];

        foreach ($contributions as [$concept, $unit, $quantity, $unitCost, $total]) {
            AporteInstitucional::updateOrCreate(
                ['proyecto_id' => $project->id, 'concepto' => $concept],
                [
                    'unidad' => $unit,
                    'cantidad' => $quantity,
                    'costo_unitario' => $unitCost,
                    'costo_total' => $total,
                ]
            );
        }

        Presupuesto::updateOrCreate(
            ['proyecto_id' => $project->id],
            [
                'aporte_internacionales' => 0,
                'aporte_otras_universidades' => 0,
                'aporte_contraparte' => 7000,
                'aporte_comunidad' => 0,
                'otros_aportes' => 0,
            ]
        );
    }

    private function seedAnexos(Proyecto $project): void
    {
        $path = self::FILE_PREFIX.'/anexo-general-prueba.pdf';
        Anexo::updateOrCreate(
            ['proyecto_id' => $project->id, 'documento_url' => $path],
            []
        );
        $this->writeDummyPdf('anexo-general-prueba.pdf');
    }

    private function seedScenario(Proyecto $project, string $scenario, array $people): void
    {
        match ($scenario) {
            'borrador' => $this->seedBorrador($project, $people['coordinador']),
            'revision' => $this->seedRevision($project, $people),
            'subsanacion' => $this->seedSubsanacion($project, $people),
            'en_curso' => $this->seedFlujoNormalAprobado($project, $people),
            'inf001_borrador' => $this->seedInformeFinalBorrador($project, $people),
            'inf001_revision' => $this->seedInformeFinalEnRevision($project, $people),
            'finalizado' => $this->seedInformeFinalAprobado($project, $people),
        };
    }

    private function seedBorrador(Proyecto $project, Empleado $actor): void
    {
        $this->anularFirmasNormales($project);
        $this->seedStateHistory($project, $actor, [['Borrador', 'Proyecto de prueba guardado como borrador.']]);
    }

    private function seedRevision(Proyecto $project, array $people): void
    {
        $this->seedNormalSignatures($project, $people, 'Pendiente');
        $this->seedStateHistory($project, $people['coordinador'], [
            ['Borrador', 'Proyecto de prueba creado como borrador.'],
            ['En revision', 'Proyecto de prueba enviado a revisión.'],
        ]);
    }

    private function seedSubsanacion(Proyecto $project, array $people): void
    {
        $signatures = $this->seedNormalSignatures($project, $people, 'Pendiente');
        $first = $signatures->first();
        $first?->update(['estado_revision' => 'Rechazado', 'fecha_firma' => now()]);
        $this->seedStateHistory($project, $people['revisor'], [
            ['Borrador', 'Proyecto de prueba creado como borrador.'],
            ['En revision', 'Proyecto de prueba enviado a revisión.'],
            ['Subsanacion', 'Corrija los requerimientos y el cronograma antes de reenviar.'],
        ]);
    }

    private function seedFlujoNormalAprobado(Proyecto $project, array $people): void
    {
        $this->seedNormalSignatures($project, $people, 'Aprobado');
        $this->seedStateHistory($project, $people['coordinador'], [
            ['Borrador', 'Proyecto de prueba creado como borrador.'],
            ['En revision', 'Proyecto de prueba enviado a revisión.'],
            ['En curso', 'Todas las etapas normales del flujo fueron aprobadas.'],
        ]);
    }

    private function seedNormalSignatures(Proyecto $project, array $people, string $status)
    {
        $stages = $project->flujoEtapasActivasOrdenadas(Proyecto::FLUJO_INSCRIPCION);

        if ($stages->isEmpty()) {
            throw new RuntimeException('El flujo FORM-DVUS-001 no tiene etapas normales activas.');
        }

        $employees = [$people['coordinador'], $people['revisor'], $people['director']];

        return $stages->values()->map(function (FlujoAprobacionEtapa $stage, int $index) use ($project, $employees, $status) {
            return $project->guardarFirmaDeEtapa(
                $stage,
                $employees[$index % count($employees)],
                [
                    'estado_revision' => $status,
                    'firma_id' => null,
                    'sello_id' => null,
                    'fecha_firma' => $status === 'Aprobado' ? now() : null,
                    'hash' => 'seed-normal-'.$stage->codigo,
                ],
                null,
                1
            );
        });
    }

    private function anularFirmasNormales(Proyecto $project): void
    {
        $stageIds = $project->flujoEtapasActivasOrdenadas(Proyecto::FLUJO_INSCRIPCION)->pluck('id');
        $project->firma_proyecto()
            ->whereIn('flujo_aprobacion_etapa_id', $stageIds)
            ->whereNull('deleted_at')
            ->update(['estado_revision' => 'Anulado']);
    }

    private function seedInformeFinalBorrador(Proyecto $project, array $people): InformeFinalProyecto
    {
        $this->seedFlujoNormalAprobado($project, $people);
        $report = app(InformeFinalProyectoInitializer::class)
            ->initialize($project->fresh(), $people['coordinador']->user_id);
        $this->populateReport($report);
        $report->update([
            'estado' => InformeFinalProyecto::ESTADO_BORRADOR,
            'updated_by' => $people['coordinador']->user_id,
        ]);

        return $report->fresh();
    }

    private function seedInformeFinalEnRevision(Proyecto $project, array $people): InformeFinalProyecto
    {
        $report = $this->seedInformeFinalBorrador($project, $people);
        $document = $this->seedClosureDocument($project, $people, 'Pendiente');
        $report->update(['estado' => InformeFinalProyecto::ESTADO_COMPLETO]);
        $this->seedDocumentState($document, $people['coordinador'], 'En revision', 'INF-001 de prueba enviado al flujo de cierre.');

        return $report->fresh();
    }

    private function seedInformeFinalAprobado(Proyecto $project, array $people): InformeFinalProyecto
    {
        $report = $this->seedInformeFinalBorrador($project, $people);
        $document = $this->seedClosureDocument($project, $people, 'Aprobado');
        $report->update([
            'estado' => InformeFinalProyecto::ESTADO_COMPLETO,
            'fecha_cierre' => '2026-11-30',
        ]);
        $this->seedDocumentState($document, $people['director'], 'Aprobado', 'INF-001 de prueba aprobado.');
        $this->seedStateHistory($project, $people['director'], [
            ['Borrador', 'Proyecto de prueba creado como borrador.'],
            ['En revision', 'Proyecto de prueba enviado a revisión.'],
            ['En curso', 'Todas las etapas normales del flujo fueron aprobadas.'],
            ['Finalizado', 'Proyecto de prueba finalizado mediante INF-001.'],
        ]);

        return $report->fresh();
    }

    private function populateReport(InformeFinalProyecto $report): void
    {
        $report->update([
            'transformacion_lograda' => 'La comunidad dispone de una aplicación de prueba para organizar sus procesos administrativos.',
            'confirmacion_veracidad' => true,
            'aporte_beneficiarios' => 0,
            'otros_aportes' => 0,
        ]);
        $report->voluntarios()->updateOrCreate(
            ['identidad' => 'NEXO-VOL-001'],
            [
                'nombre' => 'Voluntaria Comunitaria de Prueba',
                'sexo' => 'Femenino',
                'departamento' => 'Choluteca',
                'tipo' => 'egresado',
                'horas_dedicadas' => 24,
            ]
        );
        $report->resultados()->each(fn ($result) => $result->update([
            'valor_alcanzado' => 1,
            'porcentaje_cumplimiento' => 100,
            'estado' => 'alcanzado',
            'producto_logrado' => 'Producto de prueba completado.',
        ]));
        $report->actividades()->each(fn ($activity) => $activity->update([
            'actividad_realizada' => $activity->actividad_planificada,
            'estado' => 'ejecutada',
            'medio_verificacion' => 'Registro de prueba del seeder.',
        ]));
        $report->contrapartes()->each(fn ($counterpart) => $counterpart->update([
            'compromisos_cumplidos' => 'La contraparte participó en validaciones y capacitaciones de prueba.',
            'aporte_monetario' => 7000,
        ]));
        $photo = self::FILE_PREFIX.'/fotografia-proyecto-prueba.pdf';
        $report->anexos()->updateOrCreate(
            ['categoria' => 'fotografia', 'archivo' => $photo],
            [
                'tipo' => 'fotografias',
                'descripcion' => 'Evidencia visual ficticia del proyecto.',
                'nombre_archivo' => 'fotografia-proyecto-prueba.pdf',
                'origen' => 'INFORME',
                'fecha' => '2026-11-30',
                'orden' => 1,
            ]
        );
        $this->writeDummyPdf('fotografia-proyecto-prueba.pdf');
    }

    private function seedClosureDocument(Proyecto $project, array $people, string $status): DocumentoProyecto
    {
        $path = self::FILE_PREFIX.'/inf-001-prueba.pdf';
        $document = DocumentoProyecto::updateOrCreate(
            ['proyecto_id' => $project->id, 'tipo_documento' => 'Informe Final'],
            ['documento_url' => $path]
        );
        $this->writeDummyPdf('inf-001-prueba.pdf');
        $stages = $project->flujoEtapasActivasOrdenadas(Proyecto::FLUJO_CIERRE_PROYECTO);

        if ($stages->isEmpty()) {
            throw new RuntimeException('El flujo FORM-DVUS-001 no tiene etapas de cierre activas.');
        }

        $fallback = [$people['revisor'], $people['director']];

        foreach ($stages->values() as $index => $stage) {
            $employee = $fallback[$index % count($fallback)];
            $project->guardarFirmaDeEtapa(
                $stage,
                $employee,
                [
                    'estado_revision' => $status,
                    'firma_id' => null,
                    'sello_id' => null,
                    'fecha_firma' => $status === 'Aprobado' ? now() : null,
                    'hash' => 'seed-cierre-'.$stage->codigo,
                ],
                $document,
                1
            );
        }

        return $document->fresh();
    }

    private function seedStateHistory(Proyecto $project, Empleado $actor, array $history): void
    {
        $project->estado_proyecto()->update(['es_actual' => false]);

        foreach ($history as $index => [$stateName, $comment]) {
            $type = TipoEstado::firstOrCreate(['nombre' => $stateName]);
            EstadoProyecto::updateOrCreate(
                [
                    'estadoable_type' => Proyecto::class,
                    'estadoable_id' => $project->id,
                    'comentario' => '[Seeder TD] '.$comment,
                ],
                [
                    'empleado_id' => $actor->id,
                    'tipo_estado_id' => $type->id,
                    'fecha' => now(),
                    'es_actual' => $index === array_key_last($history),
                ]
            );
        }
    }

    private function seedDocumentState(
        DocumentoProyecto $document,
        Empleado $actor,
        string $stateName,
        string $comment
    ): void {
        $document->estado_documento()->update(['es_actual' => false]);
        $type = TipoEstado::firstOrCreate(['nombre' => $stateName]);
        EstadoProyecto::updateOrCreate(
            [
                'estadoable_type' => DocumentoProyecto::class,
                'estadoable_id' => $document->id,
                'comentario' => '[Seeder TD] '.$comment,
            ],
            [
                'empleado_id' => $actor->id,
                'tipo_estado_id' => $type->id,
                'fecha' => now(),
                'es_actual' => true,
            ]
        );
    }

    private function syncPivot(string $table, array $keys): void
    {
        $values = ['updated_at' => now()];

        if (DB::getSchemaBuilder()->hasColumn($table, 'created_at')) {
            $values['created_at'] = now();
        }

        if (DB::getSchemaBuilder()->hasColumn($table, 'deleted_at')) {
            $values['deleted_at'] = null;
        }

        DB::table($table)->updateOrInsert($keys, $values);
    }

    private function writeDummyPdf(string $filename): void
    {
        Storage::disk('public')->put(
            self::FILE_PREFIX.'/'.$filename,
            "%PDF-1.4\n% Archivo ficticio generado por ProyectoTransformacionDigitalSeeder.\n%%EOF\n"
        );
    }
}
