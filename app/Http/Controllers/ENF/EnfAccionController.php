<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Http\Requests\ENF\StoreEnfAccionRequest;
use App\Http\Requests\ENF\UpdateEnfAccionRequest;
use App\Models\Asignatura;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfCatalogo;
use App\Models\ENF\EnfRevision;
use App\Models\PeriodoAcademico;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\EjesPrioritariosUnah;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\ENF\EnfWorkflowService;
use App\Services\FormDvus018DocumentService;
use App\Support\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PDF;

class EnfAccionController extends Controller
{
    private const FORM_CERTIFICADO_UNIVERSITARIO = 'FORM-DVUS-016';

    private const FORM_PROYECTO_ENF = 'FORM-DVUS-018';

    private const FORM_CERTIFICADO_UNIVERSITARIO_ENABLED = true;

    private const TIPO_ACCION_CERTIFICADO = 'Certificado universitario';

    private const TIPO_ACCION_ENF_VISIBLE = 'Proyecto de educacion continua';

    private const TIPOS_ACCION_FORM_018 = [
        'Proyecto de educacion continua',
        'Diplomado',
        'Congreso',
        'Seminario',
    ];

    public static function formularioCertificadoUniversitarioDisponible(): bool
    {
        return self::FORM_CERTIFICADO_UNIVERSITARIO_ENABLED;
    }

    public function tipos(): View
    {
        return view('enf.acciones.tipos', [
            'formCertificadoUniversitarioDisponible' => self::formularioCertificadoUniversitarioDisponible(),
            'tipos' => EnfCatalogo::where('tipo', 'tipo_accion_enf')
                ->whereIn('nombre', [
                    self::TIPO_ACCION_CERTIFICADO,
                    self::TIPO_ACCION_ENF_VISIBLE,
                    'Programa de educacion continua',
                ])
                ->where('activo', true)
                ->orderBy('orden')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function index(Request $request): JsonResponse|View
    {
        $acciones = EnfAccion::query()
            ->with(['tipoAccion', 'modalidad', 'centroFacultad', 'responsableRevision'])
            ->latest()
            ->paginate();

        if ($request->expectsJson()) {
            return response()->json($acciones);
        }

        return view('enf.acciones.index', compact('acciones'));
    }

    public function create(Request $request): JsonResponse|RedirectResponse|View
    {
        $esFormularioCertificado = $request->query('form') === '016';

        if ($esFormularioCertificado && ! self::FORM_CERTIFICADO_UNIVERSITARIO_ENABLED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => self::FORM_CERTIFICADO_UNIVERSITARIO.' no esta disponible temporalmente.'], 403);
            }

            return redirect()
                ->route('selectorTipoAccion', ['grupo' => 'educacion-no-formal'])
                ->with('status', self::FORM_CERTIFICADO_UNIVERSITARIO.' no esta disponible temporalmente.');
        }

        $formCode = $request->query('form') === '016'
            ? self::FORM_CERTIFICADO_UNIVERSITARIO
            : self::FORM_PROYECTO_ENF;

        if ($request->expectsJson()) {
            return response()->json(['message' => $formCode.' disponible.']);
        }

        if ($formCode === self::FORM_CERTIFICADO_UNIVERSITARIO && ! $request->boolean('nuevo')) {
            $borradorExistente = EnfAccion::query()
                ->where('codigo_formulario', self::FORM_CERTIFICADO_UNIVERSITARIO)
                ->where('creado_por_usuario_id', $request->user()?->id)
                ->whereIn('estado_flujo', ['BORRADOR', 'SUBSANACION', 'SUBSANACIÓN'])
                ->latest('updated_at')
                ->first();

            if ($borradorExistente) {
                return redirect()->route('enf.acciones.edit', $borradorExistente);
            }
        }

        $selectedTipoAccionEnfId = $request->integer('tipo_accion_enf_id')
            ?: $this->tipoAccionEnfDefaultId($formCode);

        return view(
            $formCode === self::FORM_CERTIFICADO_UNIVERSITARIO ? 'enf.acciones.create-certificado' : 'enf.acciones.create',
            $this->formViewData($formCode, $selectedTipoAccionEnfId, null, $request->boolean('nuevo'))
        );
    }

    private function formViewData(string $formCode, ?int $selectedTipoAccionEnfId = null, ?EnfAccion $accion = null, bool $clearDraftOnLoad = false): array
    {
        return [
            'formCode' => $formCode,
            'accion' => $accion,
            'initialDraft' => $accion ? $this->draftFromAccion($accion) : [],
            'tiposAccion' => VinculacionTipoAccion::where('codigo', 'EDUCACION_NO_FORMAL')->orderBy('nombre')->get(),
            'tipoAccionVinculacionEnfId' => VinculacionTipoAccion::where('codigo', 'EDUCACION_NO_FORMAL')->value('id'),
            'selectedTipoAccionEnfId' => $selectedTipoAccionEnfId,
            'tiposAccionForm018' => EnfCatalogo::query()
                ->where('tipo', 'tipo_accion_enf')
                ->whereIn('nombre', self::TIPOS_ACCION_FORM_018)
                ->where('activo', true)
                ->orderBy('orden')
                ->orderBy('nombre')
                ->get(),
            'clearDraftOnLoad' => $clearDraftOnLoad,
            'centrosFacultad' => FacultadCentro::orderBy('nombre')->get(),
            'departamentosAcademicos' => DepartamentoAcademico::orderBy('nombre')->get(),
            'carreras' => Carrera::with(['facultadCentros', 'departamentosAcademicos'])->orderBy('nombre')->get(),
            'campus' => Campus::orderBy('nombre_campus')->get(),
            'empleados' => Empleado::with(['categoria', 'departamento_academico', 'user'])
                ->orderBy('nombre_completo')
                ->limit(250)
                ->get(),
            'asignaturas' => Asignatura::orderBy('nombre')->get(),
            'periodosAcademicos' => PeriodoAcademico::orderBy('nombre')->get(),
            'catalogos' => EnfCatalogo::query()
                ->orderBy('tipo')
                ->orderBy('orden')
                ->orderBy('nombre')
                ->get()
                ->groupBy('tipo'),
            'odsList' => Od::orderBy('id')->get(),
            'metasContribuye' => MetaContribuye::with('ods')->orderBy('ods_id')->orderBy('numero_meta')->get(),
            'ejesUnah' => EjesPrioritariosUnah::orderBy('nombre')->get(),
        ];
    }

    private function tipoAccionEnfDefaultId(string $formCode): ?int
    {
        $nombre = $formCode === self::FORM_CERTIFICADO_UNIVERSITARIO
            ? self::TIPO_ACCION_CERTIFICADO
            : self::TIPO_ACCION_ENF_VISIBLE;

        return EnfCatalogo::query()
            ->where('tipo', 'tipo_accion_enf')
            ->where('nombre', $nombre)
            ->where('activo', true)
            ->value('id');
    }

    private function normalizarNombreCatalogo(?string $nombre): string
    {
        return Str::of(Str::ascii((string) $nombre))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function draftFromAccion(EnfAccion $accion): array
    {
        $accion->loadMissing([
            'lugaresEjecucion.campus',
            'beneficiarios',
            'equipo',
            'participacionUniversitaria',
            'practicasAsignatura',
            'contrapartes.tipoContraparte',
            'contrapartes.instrumentoAlianza',
            'objetivosEspecificos',
            'resultados.objetivoEspecifico',
            'presupuestos.detalles',
            'cronograma',
            'certificado.tipoCertificado',
            'certificado.carreras.carrera',
            'espaciosAprendizaje',
            'documentos',
            'firmas',
            'accionCatalogos',
            'accionOds',
            'accionEjesUnah',
        ]);

        $lugar = $accion->lugaresEjecucion->first();
        $beneficiarios = $accion->beneficiarios;
        $coordinador = $accion->equipo->firstWhere('rol', 'Coordinador de la accion');
        $sistematizador = $accion->equipo->firstWhere('rol', 'Responsable de sistematizacion');
        $contraparte = $accion->contrapartes->first();
        $certificado = $accion->certificado;

        $draft = collect([
            'tipo_accion_id' => $accion->tipo_accion_id,
            'codigo_formulario' => $accion->codigo_formulario,
            'estado_flujo' => $accion->estado_flujo,
            'modalidad_id' => $accion->modalidad_id,
            'centro_facultad_id' => $accion->centro_facultad_id,
            'departamento_academico_id' => $accion->departamento_academico_id,
            'carrera_id' => $accion->carrera_id,
            'centro_facultad_ids[]' => array_values(array_filter([(string) $accion->centro_facultad_id])),
            'departamento_academico_ids[]' => array_values(array_filter([(string) $accion->departamento_academico_id])),
            'carrera_ids[]' => array_values(array_filter([(string) $accion->carrera_id])),
            'unidad_academica_responsable_texto' => $accion->unidad_academica_responsable_texto,
            'escuela_departamento_texto' => $accion->escuela_departamento_texto,
            'nombre_accion' => $accion->nombre_accion,
            'numero_edicion' => $accion->numero_edicion,
            'fecha_solicitud' => optional($accion->fecha_solicitud)->format('Y-m-d'),
            'fecha_inicio' => optional($accion->fecha_inicio)->format('Y-m-d'),
            'fecha_finalizacion' => optional($accion->fecha_finalizacion)->format('Y-m-d'),
            'resolucion_vra' => $accion->resolucion_vra,
            'resolucion_original' => $accion->resolucion_original,
            'resolucion_actualizacion' => $accion->resolucion_actualizacion,
            'horas_teoricas' => $accion->horas_teoricas,
            'horas_practicas' => $accion->horas_practicas,
            'total_horas' => $accion->total_horas,
            'carga_horaria_creditos' => $accion->carga_horaria_creditos,
            'certificado[nombre_certificado]' => $certificado?->nombre_certificado,
            'certificado[codigo_certificado]' => $certificado?->codigo_certificado,
            'certificado[tipo_certificado_id]' => $certificado?->tipo_certificado_id,
            'certificado[figura_acreditacion_id]' => $certificado?->figura_acreditacion_id,
            'certificado[horas_certificadas]' => $certificado?->horas_certificadas,
            'certificado[requisitos_emision]' => $certificado?->requisitos_emision,
            'certificado[vigencia_certificado]' => $certificado?->vigencia_certificado,
            'certificado[fecha_emision_maxima]' => optional($certificado?->fecha_emision_maxima)->format('Y-m-d'),
            'certificado[pac_certificado]' => $certificado?->pac_certificado,
            'certificado[hora_inicio]' => $certificado?->hora_inicio,
            'certificado[hora_finalizacion]' => $certificado?->hora_finalizacion,
            'certificado[dias_imparticion][]' => array_values((array) ($certificado?->dias_imparticion ?? [])),
            'campus_id' => $lugar?->campus_id,
            'departamento_id' => $lugar?->departamento_id,
            'municipio_id' => $lugar?->municipio_id,
            'nombre_lugar' => $lugar?->nombre_lugar,
            'aula_auditorio' => $lugar?->aula,
            'edificio' => $lugar?->edificio,
            'centro_lugar' => $lugar?->centro,
            'modalidad_ejecucion' => $lugar?->modalidad_ejecucion,
            'descripcion_plataformas' => $lugar?->descripcion_plataformas,
            'beneficiarios[hombres]' => $beneficiarios?->hombres,
            'beneficiarios[mujeres]' => $beneficiarios?->mujeres,
            'beneficiarios[otros]' => data_get($beneficiarios?->distribucion, 'otros'),
            'beneficiarios[total]' => $beneficiarios?->total,
            'descripcion_participantes' => $accion->descripcion_participantes ?: $beneficiarios?->descripcion,
            'coordinador[empleado_id]' => $coordinador?->empleado_id,
            'coordinador[nombre_completo]' => $coordinador?->nombre_completo,
            'coordinador[numero_empleado]' => $coordinador?->numero_empleado,
            'coordinador[correo]' => $coordinador?->correo,
            'coordinador[celular]' => $coordinador?->celular,
            'coordinador[categoria]' => $coordinador?->categoria,
            'coordinador[departamento]' => $coordinador?->departamento,
            'sistematizador[empleado_id]' => $sistematizador?->empleado_id,
            'sistematizador[nombre_completo]' => $sistematizador?->nombre_completo,
            'sistematizador[numero_empleado]' => $sistematizador?->numero_empleado,
            'sistematizador[correo]' => $sistematizador?->correo,
            'sistematizador[celular]' => $sistematizador?->celular,
            'sistematizador[categoria]' => $sistematizador?->categoria,
            'sistematizador[departamento]' => $sistematizador?->departamento,
            'contraparte[tiene_contraparte]' => $contraparte ? 'Si' : 'No',
            'contraparte[nombre]' => $contraparte?->nombre,
            'contraparte[rtn]' => $contraparte?->rtn,
            'contraparte[representante]' => $contraparte?->representante,
            'contraparte[cargo_contacto]' => $contraparte?->cargo_contacto,
            'contraparte[correo]' => $contraparte?->correo,
            'contraparte[telefono]' => $contraparte?->telefono,
            'contraparte[direccion]' => $contraparte?->direccion,
            'contraparte[tipo_contraparte_id]' => $contraparte?->tipo_contraparte_id,
            'contraparte[instrumento_alianza_id]' => $contraparte?->instrumento_alianza_id,
            'contraparte[compromisos]' => $contraparte?->compromisos,
            'resumen' => $accion->resumen,
            'definicion_problema' => $accion->definicion_problema,
            'objetivo_general' => $accion->objetivo_general,
            'objetivos_especificos[]' => $accion->objetivosEspecificos->sortBy('orden')->pluck('descripcion')->values()->all(),
            'alineamiento_reforma' => $accion->alineamiento_reforma,
            'metodologia' => $accion->metodologia,
            'logistica' => $accion->logistica,
            'bibliografia' => $accion->bibliografia,
            'ods_ids[]' => $accion->accionOds->pluck('ods_id')->filter()->map(fn ($id) => (string) $id)->unique()->values()->all(),
            'meta_contribuye_ids[]' => $accion->accionOds->pluck('meta_contribuye_id')->filter()->map(fn ($id) => (string) $id)->unique()->values()->all(),
            'eje_unah_ids[]' => $accion->accionEjesUnah->pluck('eje_prioritario_unah_id')->filter()->map(fn ($id) => (string) $id)->unique()->values()->all(),
            'genera_ingresos' => $accion->genera_ingresos ? '1' : null,
            'mecanismo_administracion' => $accion->mecanismo_administracion,
            'descripcion_excedente' => $accion->descripcion_excedente,
            'documentos_requeridos[]' => $accion->documentos->pluck('nombre')->filter()->values()->all(),
        ])->filter(fn ($value) => ! is_null($value))->all();

        foreach (['oficio_remision_decano', 'documento_perfil_programa', 'otros_documentos_respaldo'] as $tipoDocumento) {
            $draft["supervisor_documentos[{$tipoDocumento}][aplica]"] = $accion->documentos
                ->contains('tipo_documento', $tipoDocumento) ? 'Si' : 'No';
        }

        foreach ($accion->accionCatalogos as $catalogo) {
            $draft["catalogos[{$catalogo->tipo}][]"][] = (string) $catalogo->enf_catalogo_id;
        }

        foreach ($accion->accionCatalogos->where('tipo', 'plataforma_presencial')->values() as $catalogo) {
            $draft['plataformas_presencial[]'][] = (string) $catalogo->enf_catalogo_id;
        }

        foreach ($accion->accionCatalogos->where('tipo', 'plataforma_distancia')->values() as $catalogo) {
            $draft['plataformas_distancia[]'][] = (string) $catalogo->enf_catalogo_id;
        }

        foreach ($certificado?->carreras?->values() ?? [] as $index => $carrera) {
            $draft["certificado_carreras[{$index}][carrera_id]"] = $carrera->carrera_id;
            $draft["certificado_carreras[{$index}][centro_facultad_id]"] = $carrera->centro_facultad_id;
            $draft["certificado_carreras[{$index}][nombre_carrera]"] = $carrera->nombre_carrera;
            $draft["certificado_carreras[{$index}][acuerdo_consejo_universitario]"] = $carrera->acuerdo_consejo_universitario;
        }

        foreach ($accion->espaciosAprendizaje->values() as $index => $espacio) {
            $draft["espacios_aprendizaje[{$index}][nombre]"] = $espacio->nombre;
            $draft["espacios_aprendizaje[{$index}][codigo]"] = $espacio->codigo;
            $draft["espacios_aprendizaje[{$index}][creditos]"] = $espacio->creditos;
            $draft["espacios_aprendizaje[{$index}][horas]"] = $espacio->horas;
            $draft["espacios_aprendizaje[{$index}][descripcion]"] = $espacio->descripcion;
        }

        foreach ($accion->participacionUniversitaria->values() as $index => $participacion) {
            $draft["participacion_universitaria[{$index}][tipo_participacion]"] = $participacion->tipo_participacion;
            $draft["participacion_universitaria[{$index}][cantidad]"] = $participacion->cantidad;
            preg_match('/Hombres:\s*(\d+)/', (string) $participacion->descripcion, $hombresMatch);
            preg_match('/Mujeres:\s*(\d+)/', (string) $participacion->descripcion, $mujeresMatch);
            $draft["participacion_universitaria[{$index}][hombres]"] = $hombresMatch[1] ?? null;
            $draft["participacion_universitaria[{$index}][mujeres]"] = $mujeresMatch[1] ?? null;
        }

        foreach ($accion->practicasAsignatura->values() as $index => $practica) {
            $draft["practicas_asignatura[{$index}][asignatura_id]"] = $practica->asignatura_id;
            $draft["practicas_asignatura[{$index}][codigo]"] = $practica->codigo_asignatura;
            $draft["practicas_asignatura[{$index}][nombre]"] = $practica->nombre_asignatura;
            $draft["practicas_asignatura[{$index}][periodo_academico_id]"] = $practica->periodo_academico_id;
            $draft["practicas_asignatura[{$index}][periodo_academico]"] = $practica->periodo_academico_texto;
            $draft["practicas_asignatura[{$index}][matricula_total]"] = $practica->cantidad_estudiantes;
            $draft["practicas_asignatura[{$index}][hombres]"] = $practica->matricula_hombres;
            $draft["practicas_asignatura[{$index}][mujeres]"] = $practica->matricula_mujeres;
        }

        $resultadoSlots = ['corto' => 0, 'mediano' => 6, 'largo' => 11];

        foreach ($accion->resultados->sortBy('orden')->values() as $resultado) {
            [$tipo, $descripcion] = str($resultado->resultado)->explode(': ', 2)->pad(2, null)->all();
            $tipoNormalizado = $this->normalizarNombreCatalogo($tipo ?: '');
            $grupo = str_contains($tipoNormalizado, 'mediano')
                ? 'mediano'
                : (str_contains($tipoNormalizado, 'largo') || str_contains($tipoNormalizado, 'impacto') ? 'largo' : 'corto');
            $index = $resultadoSlots[$grupo]++;
            $draft["resultados[{$index}][tipo]"] = $tipo ?: 'Resultado';
            $draft["resultados[{$index}][objetivo_orden]"] = $resultado->objetivoEspecifico?->orden;
            $draft["resultados[{$index}][descripcion]"] = $descripcion ?: $resultado->resultado;
            $draft["resultados[{$index}][indicador]"] = $resultado->indicador;
        }

        foreach ($accion->presupuestos as $presupuesto) {
            $key = match ($presupuesto->tipo) {
                'ingresos' => 'presupuesto_ingresos',
                'egresos' => 'presupuesto_egresos',
                default => null,
            };

            if (! $key) {
                continue;
            }

            foreach ($presupuesto->detalles->values() as $index => $detalle) {
                $draft["{$key}[{$index}][rubro]"] = $detalle->rubro;
                $draft["{$key}[{$index}][cantidad]"] = $detalle->cantidad;
                $draft["{$key}[{$index}][costo_unitario]"] = $detalle->costo_unitario;
            }
        }

        foreach ($accion->cronograma->values() as $index => $item) {
            $draft["cronograma[{$index}][actividad]"] = $item->actividad;
            $draft["cronograma[{$index}][producto]"] = $item->producto;
            $draft["cronograma[{$index}][fecha_inicio]"] = optional($item->fecha_inicio)->format('Y-m-d');
            $draft["cronograma[{$index}][responsable]"] = $item->responsable_texto;
            $draft["cronograma[{$index}][horas_requeridas]"] = $item->horas_requeridas;
        }

        foreach ($accion->firmas->values() as $index => $firma) {
            $draft["firmas[{$index}][rol_firma]"] = $firma->rol_firma;
            $draft["firmas[{$index}][nombre_firmante]"] = $firma->nombre_firmante;
            $draft["firmas[{$index}][observaciones]"] = $firma->observaciones;
        }

        foreach (['equipo_docente' => 'Docente UNAH', 'consultores_nacionales' => 'Consultor nacional', 'consultores_internacionales' => 'Consultor internacional'] as $key => $rol) {
            foreach ($accion->equipo->where('rol', $rol)->values() as $index => $integrante) {
                foreach (['nombre_completo', 'numero_empleado', 'identidad', 'espacio_aprendizaje', 'correo', 'categoria', 'departamento', 'jornada_laboral', 'profesion', 'nacionalidad', 'ultimo_titulo', 'pais_procedencia', 'universidad_procedencia', 'perfil_docente', 'horas_contratadas'] as $field) {
                    $value = $field === 'horas_contratadas' ? $integrante->horas_dedicadas : $integrante->{$field};
                    if (filled($value)) {
                        $draft["{$key}[{$index}][{$field}]"] = $value;
                    }
                }

                $draft["{$key}[{$index}][carga_academica_pac]"] = $integrante->carga_academica_pac ? 'Si' : null;
                $draft["{$key}[{$index}][contratacion_jornada_contraria]"] = $integrante->contratacion_jornada_contraria ? 'Si' : null;
            }
        }

        return $draft;
    }

    public function store(StoreEnfAccionRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $guardarSoloBorrador = $request->boolean('guardar_solo_borrador');

        if (($validated['codigo_formulario'] ?? null) === self::FORM_CERTIFICADO_UNIVERSITARIO
            && ! self::FORM_CERTIFICADO_UNIVERSITARIO_ENABLED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => self::FORM_CERTIFICADO_UNIVERSITARIO.' no esta disponible temporalmente.'], 403);
            }

            return redirect()
                ->route('selectorTipoAccion', ['grupo' => 'educacion-no-formal'])
                ->with('status', self::FORM_CERTIFICADO_UNIVERSITARIO.' no esta disponible temporalmente.');
        }

        if ($request->filled('borrador_autoguardado_id')) {
            try {
                [$accion, $flujoIniciado] = DB::transaction(function () use ($request, $validated, $guardarSoloBorrador) {
                    $record = EnfAccion::findOrFail($request->integer('borrador_autoguardado_id'));

                    abort_unless($this->usuarioPuedeEditarBorrador($request->user(), $record), 403);

                    $this->actualizarRegistroFormulario($record, $validated, $request, true);
                    $flujoIniciado = ! $guardarSoloBorrador && app(EnfWorkflowService::class)->enviarInscripcion(
                        $record->fresh(),
                        $request->user(),
                        $request->input('destinatarios', [])
                    );

                    return [$record->fresh(), $flujoIniciado];
                });
            } catch (\RuntimeException $exception) {
                return $this->respuestaErrorFlujo($request, $exception);
            }

            if (! $request->expectsJson()) {
                return $this->redireccionDespuesDeGuardar($request, $flujoIniciado, false);
            }

            return response()->json($accion->fresh(), 201);
        }

        try {
            [$accion, $flujoIniciado] = DB::transaction(function () use ($request, $validated, $guardarSoloBorrador) {
                $accionFields = $this->accionStoreFields();

                $data = array_intersect_key($validated, array_flip($accionFields));
                $data['codigo_formulario'] = $data['codigo_formulario'] ?? self::FORM_PROYECTO_ENF;
                $data['estado_flujo'] = 'BORRADOR';
                $data['genera_ingresos'] = $request->boolean('genera_ingresos');
                if (empty($data['total_horas'])) {
                    $data['total_horas'] = (int) ($data['horas_teoricas'] ?? 0) + (int) ($data['horas_practicas'] ?? 0);
                }
                $data['creado_por_usuario_id'] = $request->user()?->id;
                $data['modificado_por_usuario_id'] = $request->user()?->id;

                $accion = EnfAccion::create($data);

                $this->guardarRelacionesFormulario($accion, $validated, $request);
                $flujoIniciado = ! $guardarSoloBorrador && app(EnfWorkflowService::class)->enviarInscripcion(
                    $accion->fresh(),
                    $request->user(),
                    $request->input('destinatarios', [])
                );

                return [$accion->fresh(), $flujoIniciado];
            });
        } catch (\RuntimeException $exception) {
            return $this->respuestaErrorFlujo($request, $exception);
        }

        if (! $request->expectsJson()) {
            return $this->redireccionDespuesDeGuardar($request, $flujoIniciado, false);
        }

        return response()->json($accion->fresh(), 201);
    }

    public function autoguardarBorrador(Request $request, ?int $accion = null): JsonResponse
    {
        $data = $this->draftDataFromRequest($request);
        // La ruta de creación nunca debe confiar en un ID enviado por el navegador:
        // podría pertenecer a otro borrador conservado en localStorage.
        $recordId = $accion;

        $record = DB::transaction(function () use ($request, $data, $recordId): EnfAccion {
            if ($recordId) {
                $record = EnfAccion::findOrFail($recordId);

                abort_unless($this->usuarioPuedeEditarBorrador($request->user(), $record), 403);
                abort_unless(
                    ($data['codigo_formulario'] ?? self::FORM_PROYECTO_ENF) === $record->codigo_formulario,
                    409
                );

                $this->actualizarRegistroFormulario($record, $data, $request, true);

                return $record->fresh();
            }

            $accionData = array_intersect_key($data, array_flip($this->accionStoreFields()));
            $accionData['codigo_formulario'] = $accionData['codigo_formulario'] ?? self::FORM_PROYECTO_ENF;
            $accionData['estado_flujo'] = 'BORRADOR';
            $accionData['genera_ingresos'] = $request->boolean('genera_ingresos');
            $accionData['creado_por_usuario_id'] = $request->user()?->id;
            $accionData['modificado_por_usuario_id'] = $request->user()?->id;

            if (empty($accionData['total_horas'])) {
                $accionData['total_horas'] = (int) ($accionData['horas_teoricas'] ?? 0) + (int) ($accionData['horas_practicas'] ?? 0);
            }

            $record = EnfAccion::create($accionData);
            $this->guardarRelacionesFormulario($record, $data, $request);

            return $record->fresh();
        });

        return response()->json([
            'id' => $record->id,
            'estado_flujo' => $record->estado_flujo,
            'edit_url' => route('enf.acciones.edit', $record),
            'autosave_url' => route('enf.acciones.autoguardar-borrador.update', $record),
        ]);
    }

    public function destinatariosInscripcion(Request $request, int $accion, EnfWorkflowService $workflow): JsonResponse
    {
        $record = EnfAccion::findOrFail($accion);

        abort_unless($this->usuarioPuedeEditarBorrador($request->user(), $record), 403);

        $etapas = $workflow->destinatariosSeleccionables($record, EnfAccion::PROCESO_INSCRIPCION)
            ->map(function (array $opcion): array {
                /** @var FlujoAprobacionEtapa $etapa */
                $etapa = $opcion['etapa'];

                return [
                    'id' => $etapa->id,
                    'nombre' => $etapa->nombre,
                    'codigo' => $etapa->codigo,
                    'orden' => $etapa->orden,
                    'rol_nombre' => $opcion['rol_requerido'] ?? $etapa->rolRevisor?->name,
                    'candidatos' => $opcion['usuarios']
                        ->map(fn (User $user): array => [
                            'user_id' => $user->id,
                            'nombre' => $user->empleado?->nombre_completo ?? $user->name,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        return response()->json(['etapas' => $etapas]);
    }

    public function enviarBorradorRevision(Request $request, int $accion): RedirectResponse
    {
        $record = EnfAccion::findOrFail($accion);

        abort_unless($this->usuarioPuedeEditarBorrador($request->user(), $record), 403);

        try {
            $flujoIniciado = app(EnfWorkflowService::class)->enviarInscripcion(
                $record->fresh(),
                $request->user(),
                $request->input('destinatarios', [])
            );
        } catch (\Throwable $exception) {
            return redirect()
                ->route('enf.acciones.edit', $record)
                ->withErrors(['flujo' => $exception->getMessage()]);
        }

        return redirect()
            ->route('proyectosDocente')
            ->with('status', $flujoIniciado
                ? 'Accion de Educacion No Formal enviada a revisión.'
                : 'Accion de Educacion No Formal guardada como borrador. No hay flujo de revisión activo para este formulario.');
    }

    private function accionStoreFields(): array
    {
        return [
            'codigo_formulario',
            'tipo_accion_id',
            'modalidad_id',
            'centro_facultad_id',
            'departamento_academico_id',
            'carrera_id',
            'unidad_academica_responsable_texto',
            'escuela_departamento_texto',
            'nombre_accion',
            'numero_edicion',
            'fecha_solicitud',
            'fecha_inicio',
            'fecha_finalizacion',
            'resolucion_vra',
            'resolucion_original',
            'resolucion_actualizacion',
            'horas_teoricas',
            'horas_practicas',
            'total_horas',
            'carga_horaria_creditos',
            'resumen',
            'impacto_esperado',
            'descripcion_participantes',
            'definicion_problema',
            'objetivo_general',
            'alineamiento_reforma',
            'metodologia',
            'logistica',
            'bibliografia',
            'genera_ingresos',
            'mecanismo_administracion',
            'descripcion_excedente',
            'estado_flujo',
            'revision_ciclo',
            'responsable_revision_id',
            'fecha_aprobacion',
            'fecha_registro',
            'numero_libro',
            'numero_tomo',
            'numero_folio',
            'numero_registro',
        ];
    }

    private function draftDataFromRequest(Request $request): array
    {
        $data = $request->except(['_token', '_method', 'borrador_autoguardado_id']);
        $data['codigo_formulario'] = $data['codigo_formulario'] ?? self::FORM_PROYECTO_ENF;
        $data['estado_flujo'] = 'BORRADOR';
        $data['nombre_accion'] = filled($data['nombre_accion'] ?? null)
            ? $data['nombre_accion']
            : 'Borrador sin título';

        foreach ([
            'tipo_accion_id',
            'modalidad_id',
            'centro_facultad_id',
            'departamento_academico_id',
            'carrera_id',
            'responsable_revision_id',
        ] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = is_numeric($data[$field]) ? (int) $data[$field] : null;
        }

        foreach ([
            'numero_edicion' => 1,
            'horas_teoricas' => 0,
            'horas_practicas' => 0,
            'total_horas' => 0,
            'carga_horaria_creditos' => 0,
            'revision_ciclo' => 0,
        ] as $field => $default) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = is_numeric($data[$field]) ? (int) $data[$field] : $default;
        }

        foreach ([
            'fecha_solicitud',
            'fecha_inicio',
            'fecha_finalizacion',
            'fecha_aprobacion',
            'fecha_registro',
        ] as $field) {
            if (! array_key_exists($field, $data) || blank($data[$field])) {
                $data[$field] = null;

                continue;
            }

            $data[$field] = strtotime((string) $data[$field]) !== false ? $data[$field] : null;
        }

        return $data;
    }

    private function actualizarRegistroFormulario(EnfAccion $record, array $data, Request $request, bool $mantenerBorrador = false): void
    {
        $accionData = array_intersect_key($data, array_flip($this->accionStoreFields()));
        $accionData['codigo_formulario'] = $accionData['codigo_formulario'] ?? self::FORM_PROYECTO_ENF;
        $accionData['genera_ingresos'] = $request->boolean('genera_ingresos');
        $accionData['modificado_por_usuario_id'] = $request->user()?->id;

        if ($mantenerBorrador) {
            $accionData['estado_flujo'] = in_array(strtoupper((string) $record->estado_flujo), ['SUBSANACION', 'SUBSANACIÓN'], true)
                ? 'SUBSANACION'
                : 'BORRADOR';
        }

        if (empty($accionData['total_horas'])) {
            $accionData['total_horas'] = (int) ($accionData['horas_teoricas'] ?? 0) + (int) ($accionData['horas_practicas'] ?? 0);
        }

        $record->update($accionData);
        $this->limpiarRelacionesFormulario($record);
        $this->guardarRelacionesFormulario($record->fresh(), $data, $request);
    }

    private function usuarioPuedeEditarBorrador(?User $user, EnfAccion $accion): bool
    {
        return $user
            && (int) $accion->creado_por_usuario_id === (int) $user->id
            && in_array($accion->estado_flujo, ['BORRADOR', 'SUBSANACION', 'SUBSANACIÓN'], true);
    }

    private function guardarRelacionesFormulario(EnfAccion $accion, array $data, Request $request): void
    {
        $catalogosData = $data['catalogos'] ?? [];

        if (
            filled($data['campus_id'] ?? null)
            || filled($data['departamento_id'] ?? null)
            || filled($data['municipio_id'] ?? null)
            || filled($data['nombre_lugar'] ?? null)
            || filled($data['aula_auditorio'] ?? null)
            || filled($data['edificio'] ?? null)
            || filled($data['centro_lugar'] ?? null)
            || filled($data['modalidad_ejecucion'] ?? null)
            || filled($data['descripcion_plataformas'] ?? null)
            || filled($data['plataformas'] ?? [])
            || filled($data['plataformas_presencial'] ?? [])
            || filled($data['plataformas_distancia'] ?? [])
            || filled($catalogosData['plataforma_teledocencia'] ?? [])
            || filled($catalogosData['plataforma_campus_virtual'] ?? [])
        ) {
            $platformIds = collect($data['plataformas'] ?? [])
                ->merge($data['plataformas_presencial'] ?? [])
                ->merge($data['plataformas_distancia'] ?? [])
                ->merge($catalogosData['plataforma_teledocencia'] ?? [])
                ->merge($catalogosData['plataforma_campus_virtual'] ?? [])
                ->filter()
                ->unique()
                ->values();

            $direccion = collect([
                $data['aula_auditorio'] ?? null,
                $data['edificio'] ?? null,
                $data['centro_lugar'] ?? null,
                $platformIds->isNotEmpty()
                    ? 'Plataformas: '.EnfCatalogo::whereIn('id', $platformIds)->pluck('nombre')->implode(', ')
                    : null,
            ])->filter()->implode(' | ');

            $accion->lugaresEjecucion()->create([
                'campus_id' => $data['campus_id'] ?? null,
                'departamento_id' => $data['departamento_id'] ?? null,
                'municipio_id' => $data['municipio_id'] ?? null,
                'nombre_lugar' => $data['nombre_lugar'] ?? null,
                'aula' => $data['aula_auditorio'] ?? null,
                'edificio' => $data['edificio'] ?? null,
                'centro' => $data['centro_lugar'] ?? null,
                'direccion' => $direccion ?: null,
                'modalidad_ejecucion' => $data['modalidad_ejecucion'] ?? null,
                'descripcion_plataformas' => $data['descripcion_plataformas'] ?? null,
            ]);
        }

        foreach ($catalogosData as $tipo => $catalogoIds) {
            foreach (array_filter($catalogoIds ?? []) as $catalogoId) {
                $accion->accionCatalogos()->create([
                    'enf_catalogo_id' => $catalogoId,
                    'tipo' => $tipo,
                ]);
            }
        }

        foreach ([
            'plataformas' => 'plataforma',
            'plataformas_presencial' => 'plataforma_presencial',
            'plataformas_distancia' => 'plataforma_distancia',
        ] as $inputName => $tipo) {
            foreach (array_filter($data[$inputName] ?? []) as $catalogoId) {
                $accion->accionCatalogos()->create([
                    'enf_catalogo_id' => $catalogoId,
                    'tipo' => $tipo,
                ]);
            }
        }

        $beneficiarios = $data['beneficiarios'] ?? [];
        if (array_filter($beneficiarios, fn ($value) => filled($value))) {
            $hombres = (int) ($beneficiarios['hombres'] ?? 0);
            $mujeres = (int) ($beneficiarios['mujeres'] ?? 0);
            $otros = (int) ($beneficiarios['otros'] ?? 0);

            $accion->beneficiarios()->create([
                'hombres' => $hombres,
                'mujeres' => $mujeres,
                'total' => (int) ($beneficiarios['total'] ?? ($hombres + $mujeres + $otros)),
                'descripcion' => $beneficiarios['descripcion'] ?? null,
                'distribucion' => ['otros' => $otros],
            ]);
        }

        foreach (['coordinador' => 'Coordinador de la accion', 'sistematizador' => 'Responsable de sistematizacion'] as $key => $rol) {
            $equipo = $data[$key] ?? [];
            if (array_filter($equipo, fn ($value) => filled($value))) {
                $accion->equipo()->create([
                    'empleado_id' => $equipo['empleado_id'] ?? null,
                    'nombre_completo' => $equipo['nombre_completo'] ?? null,
                    'rol' => $rol,
                    'responsabilidades' => $equipo['responsabilidades'] ?? null,
                    'es_coordinador' => $key === 'coordinador',
                    'numero_empleado' => $equipo['numero_empleado'] ?? null,
                    'identidad' => $equipo['identidad'] ?? null,
                    'correo' => $equipo['correo'] ?? null,
                    'celular' => $equipo['celular'] ?? null,
                    'categoria' => $equipo['categoria'] ?? null,
                    'departamento' => $equipo['departamento'] ?? null,
                ]);
            }
        }

        foreach (['equipo_docente' => 'Docente UNAH', 'consultores_nacionales' => 'Consultor nacional', 'consultores_internacionales' => 'Consultor internacional'] as $key => $rol) {
            foreach (($data[$key] ?? []) as $integrante) {
                if (! array_filter($integrante, fn ($value) => filled($value))) {
                    continue;
                }

                $accion->equipo()->create([
                    'nombre_completo' => $integrante['nombre_completo'] ?? null,
                    'rol' => $rol,
                    'horas_dedicadas' => (int) ($integrante['horas_contratadas'] ?? 0),
                    'numero_empleado' => $integrante['numero_empleado'] ?? null,
                    'identidad' => $integrante['identidad'] ?? null,
                    'correo' => $integrante['correo'] ?? null,
                    'categoria' => $integrante['categoria'] ?? null,
                    'departamento' => $integrante['departamento'] ?? null,
                    'espacio_aprendizaje' => $integrante['espacio_aprendizaje'] ?? null,
                    'jornada_laboral' => $integrante['jornada_laboral'] ?? null,
                    'profesion' => $integrante['profesion'] ?? null,
                    'nacionalidad' => $integrante['nacionalidad'] ?? null,
                    'ultimo_titulo' => $integrante['ultimo_titulo'] ?? null,
                    'pais_procedencia' => $integrante['pais_procedencia'] ?? null,
                    'universidad_procedencia' => $integrante['universidad_procedencia'] ?? null,
                    'perfil_docente' => $integrante['perfil_docente'] ?? null,
                    'carga_academica_pac' => ($integrante['carga_academica_pac'] ?? null) === 'Si',
                    'contratacion_jornada_contraria' => ($integrante['contratacion_jornada_contraria'] ?? null) === 'Si',
                ]);
            }
        }

        foreach (($data['participacion_universitaria'] ?? []) as $item) {
            if (! filled($item['tipo_participacion'] ?? null) && ! filled($item['cantidad'] ?? null)) {
                continue;
            }

            $accion->participacionUniversitaria()->create([
                'tipo_participacion' => $item['tipo_participacion'] ?? null,
                'cantidad' => (int) ($item['cantidad'] ?? 0),
                'descripcion' => collect([
                    filled($item['hombres'] ?? null) ? 'Hombres: '.$item['hombres'] : null,
                    filled($item['mujeres'] ?? null) ? 'Mujeres: '.$item['mujeres'] : null,
                ])->filter()->implode(' | ') ?: null,
            ]);
        }

        foreach (($data['practicas_asignatura'] ?? []) as $item) {
            if (! array_filter($item, fn ($value) => filled($value))) {
                continue;
            }

            $accion->practicasAsignatura()->create([
                'asignatura_id' => $item['asignatura_id'] ?? null,
                'periodo_academico_id' => $item['periodo_academico_id'] ?? null,
                'codigo_asignatura' => $item['codigo'] ?? null,
                'nombre_asignatura' => $item['nombre'] ?? null,
                'periodo_academico_texto' => $item['periodo_academico'] ?? null,
                'cantidad_estudiantes' => (int) ($item['hombres'] ?? 0) + (int) ($item['mujeres'] ?? 0),
                'matricula_hombres' => (int) ($item['hombres'] ?? 0),
                'matricula_mujeres' => (int) ($item['mujeres'] ?? 0),
            ]);
        }

        $contraparte = $data['contraparte'] ?? [];
        if (($contraparte['tiene_contraparte'] ?? null) === 'Si' || filled($contraparte['nombre'] ?? null)) {
            $contraparte['tiene_contraparte'] = ($contraparte['tiene_contraparte'] ?? null) === 'Si';
            $accion->contrapartes()->create($contraparte);
        }

        $this->guardarCertificadoUniversitario($accion, $data);

        $objetivoIds = [];
        foreach (array_filter($data['objetivos_especificos'] ?? []) as $index => $descripcion) {
            $objetivoIds[] = $accion->objetivosEspecificos()->create([
                'orden' => $index + 1,
                'descripcion' => $descripcion,
            ])->id;
        }

        foreach (($data['resultados'] ?? []) as $index => $resultado) {
            if (! filled($resultado['descripcion'] ?? null)) {
                continue;
            }

            $accion->resultados()->create([
                'enf_objetivo_especifico_id' => $objetivoIds[max(0, (int) ($resultado['objetivo_orden'] ?? 1) - 1)] ?? ($objetivoIds[0] ?? null),
                'orden' => $index + 1,
                'resultado' => trim(($resultado['tipo'] ?? 'Resultado').': '.$resultado['descripcion']),
                'indicador' => $resultado['indicador'] ?? null,
            ]);
        }

        foreach (array_filter($data['ods_ids'] ?? []) as $odsId) {
            $accion->accionOds()->create(['ods_id' => $odsId]);
        }

        foreach (array_filter($data['meta_contribuye_ids'] ?? []) as $metaId) {
            $meta = MetaContribuye::find($metaId);
            $accion->accionOds()->create([
                'ods_id' => $meta?->ods_id,
                'meta_contribuye_id' => $metaId,
            ]);
        }

        foreach (array_filter($data['eje_unah_ids'] ?? []) as $ejeId) {
            $accion->accionEjesUnah()->create(['eje_prioritario_unah_id' => $ejeId]);
        }

        foreach (['ingresos' => 'presupuesto_ingresos', 'egresos' => 'presupuesto_egresos'] as $tipo => $key) {
            $detalles = collect($data[$key] ?? [])
                ->filter(fn ($detalle) => filled($detalle['rubro'] ?? null));

            if ($detalles->isEmpty()) {
                continue;
            }

            $presupuesto = $accion->presupuestos()->create(['tipo' => $tipo]);
            $total = 0;

            foreach ($detalles as $detalle) {
                $cantidad = (float) ($detalle['cantidad'] ?? 0);
                $costoUnitario = (float) ($detalle['costo_unitario'] ?? 0);
                $subtotal = $cantidad * $costoUnitario;
                $total += $subtotal;

                $presupuesto->detalles()->create([
                    'rubro' => $detalle['rubro'],
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costoUnitario,
                    'total' => $subtotal,
                ]);
            }

            $presupuesto->update([
                'monto_solicitado' => $total,
                'monto_aprobado' => $total,
            ]);
        }

        $aporteUnah = collect($data['aporte_unah'] ?? [])
            ->filter(fn ($detalle) => filled($detalle['rubro'] ?? null));

        if ($aporteUnah->isNotEmpty()) {
            $presupuesto = $accion->presupuestos()->create([
                'tipo' => 'aporte_unah',
                'fuente_financiamiento' => 'UNAH',
            ]);
            $total = 0;

            foreach ($aporteUnah as $detalle) {
                $cantidad = (float) ($detalle['cantidad'] ?? 0);
                $costoUnitario = (float) ($detalle['costo_unitario'] ?? 0);
                $subtotal = $cantidad * $costoUnitario;
                $total += $subtotal;

                $presupuesto->detalles()->create([
                    'rubro' => $detalle['rubro'],
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costoUnitario,
                    'total' => $subtotal,
                ]);
            }

            $presupuesto->update([
                'monto_solicitado' => $total,
                'monto_aprobado' => $total,
            ]);
        }

        foreach (($data['cronograma'] ?? []) as $item) {
            if (! filled($item['actividad'] ?? null)) {
                continue;
            }

            $accion->cronograma()->create([
                'actividad' => $item['actividad'],
                'producto' => $item['producto'] ?? null,
                'fecha_inicio' => $item['fecha_inicio'] ?? null,
                'fecha_finalizacion' => $item['fecha_finalizacion'] ?? null,
                'responsable_texto' => $item['responsable'] ?? null,
                'horas_requeridas' => (int) ($item['horas_requeridas'] ?? 0),
            ]);
        }

        if ($request->hasFile('documentos_archivos.descripcion_plan_estudios')) {
            $file = $request->file('documentos_archivos.descripcion_plan_estudios');
            $path = $file->store('enf/documentos', 'public');

            $accion->documentos()->create([
                'tipo_documento' => 'descripcion_plan_estudios',
                'nombre' => 'Descripciones minimas del plan de estudios oficial',
                'ruta' => $path,
                'mime_type' => $file->getClientMimeType(),
                'tamano_bytes' => $file->getSize(),
                'subido_por_usuario_id' => $request->user()?->id,
                'descripcion' => 'Adjunto requerido por FORM-DVUS-016.',
            ]);
        }

        foreach (($data['firmas'] ?? []) as $firma) {
            if (! filled($firma['rol_firma'] ?? null) && ! filled($firma['nombre_firmante'] ?? null)) {
                continue;
            }

            $accion->firmas()->create([
                'rol_firma' => $firma['rol_firma'] ?? null,
                'nombre_firmante' => $firma['nombre_firmante'] ?? null,
                'observaciones' => $firma['observaciones'] ?? null,
            ]);
        }

        $supervisorDocumentMap = [
            'Oficio de remisión del Decano/Director Centro Regional' => 'oficio_remision_decano',
            'Documento perfil del programa de formación' => 'documento_perfil_programa',
            'Otros (detallar)' => 'otros_documentos_respaldo',
        ];

        foreach ($supervisorDocumentMap as $documento => $slug) {
            if (data_get($data, "supervisor_documentos.{$slug}.aplica") !== 'Si') {
                continue;
            }

            if (! $request->hasFile("supervisor_documentos_archivos.{$slug}")) {
                continue;
            }

            $file = $request->file("supervisor_documentos_archivos.{$slug}");
            $this->reemplazarDocumentoSupervisor($accion, $slug, [
                'tipo_documento' => $slug,
                'nombre' => $documento,
                'ruta' => $file->store('enf/documentos', 'public'),
                'mime_type' => $file->getClientMimeType(),
                'tamano_bytes' => $file->getSize(),
                'subido_por_usuario_id' => $request->user()?->id,
                'descripcion' => 'Documento adjunto desde el paso Supervisor.',
            ]);
        }
    }

    private function reemplazarDocumentoSupervisor(EnfAccion $accion, string $tipoDocumento, array $payload): void
    {
        $documentos = $accion->documentos()
            ->where('tipo_documento', $tipoDocumento)
            ->latest('id')
            ->get();
        $documentoActual = $documentos->first();
        $rutasAnteriores = $documentos->pluck('ruta')->filter()->unique();

        if ($documentoActual) {
            $documentoActual->update($payload);
        } else {
            $documentoActual = $accion->documentos()->create($payload);
        }

        $documentos->skip(1)->each->forceDelete();

        $rutasAnteriores
            ->reject(fn (string $ruta) => $ruta === $documentoActual->ruta)
            ->each(fn (string $ruta) => Storage::disk('public')->delete($ruta));
    }

    private function guardarCertificadoUniversitario(EnfAccion $accion, array $data): void
    {
        $certificadoData = $data['certificado'] ?? [];
        $esForm016 = ($data['codigo_formulario'] ?? null) === self::FORM_CERTIFICADO_UNIVERSITARIO;

        if (
            ! $esForm016
            && ! array_filter($certificadoData, fn ($value) => filled($value))
            && empty($data['certificado_carreras'])
            && empty($data['espacios_aprendizaje'])
        ) {
            return;
        }

        $certificado = $accion->certificado()->create([
            'tipo_certificado_id' => $certificadoData['tipo_certificado_id'] ?? null,
            'nombre_certificado' => filled($certificadoData['nombre_certificado'] ?? null)
                ? $certificadoData['nombre_certificado']
                : $accion->nombre_accion,
            'codigo_certificado' => $certificadoData['codigo_certificado'] ?? null,
            'figura_acreditacion_id' => $certificadoData['figura_acreditacion_id'] ?? null,
            'horas_certificadas' => (int) ($certificadoData['horas_certificadas'] ?? $accion->total_horas),
            'requisitos_emision' => $certificadoData['requisitos_emision'] ?? null,
            'vigencia_certificado' => $certificadoData['vigencia_certificado'] ?? null,
            'fecha_emision_maxima' => $certificadoData['fecha_emision_maxima'] ?? null,
            'pac_certificado' => $certificadoData['pac_certificado'] ?? null,
            'hora_inicio' => $certificadoData['hora_inicio'] ?? null,
            'hora_finalizacion' => $certificadoData['hora_finalizacion'] ?? null,
            'dias_imparticion' => array_values(array_filter($certificadoData['dias_imparticion'] ?? [])),
        ]);

        foreach (($data['certificado_carreras'] ?? []) as $carrera) {
            if (! array_filter($carrera, fn ($value) => filled($value))) {
                continue;
            }

            $certificado->carreras()->create([
                'carrera_id' => $carrera['carrera_id'] ?? null,
                'centro_facultad_id' => $carrera['centro_facultad_id'] ?? null,
                'nombre_carrera' => $carrera['nombre_carrera'] ?? null,
                'acuerdo_consejo_universitario' => $carrera['acuerdo_consejo_universitario'] ?? null,
            ]);
        }

        foreach (($data['espacios_aprendizaje'] ?? []) as $espacio) {
            if (! filled($espacio['nombre'] ?? null)) {
                continue;
            }

            $accion->espaciosAprendizaje()->create([
                'nombre' => $espacio['nombre'],
                'codigo' => $espacio['codigo'] ?? null,
                'creditos' => (int) ($espacio['creditos'] ?? 0),
                'horas' => (int) ($espacio['horas'] ?? 0),
                'descripcion' => $espacio['descripcion'] ?? null,
                'activo' => true,
            ]);
        }
    }

    public function show(Request $request, int $accion, EnfWorkflowService $workflow): JsonResponse|View
    {
        $record = EnfAccion::with($this->form018Relations())->findOrFail($accion);

        if ($request->expectsJson()) {
            return response()->json($record);
        }

        $revisionActual = $this->revisionActual($record);

        return view('enf.acciones.show', [
            'accion' => $record,
            'revisionActual' => $revisionActual,
            'puedeRevisar' => $revisionActual
                ? $this->usuarioPuedeRevisar($request->user(), $revisionActual)
                : false,
            'puedeReenviar' => $this->usuarioPuedeReenviar($request->user(), $record),
            'puedeGestionarInformeIntermedio' => $workflow->puedeGestionarInformeIntermedio($record, $request->user()),
            'puedeGestionarInformeFinal' => $workflow->puedeGestionarInformeFinal($record, $request->user()),
            'revisionActualIntermedio' => $workflow->revisionActualQuery($record, EnfAccion::PROCESO_INFORME_INTERMEDIO)->orderBy('orden')->first(),
            'revisionActualFinal' => $workflow->revisionActualQuery($record, EnfAccion::PROCESO_INFORME_FINAL)->orderBy('orden')->first(),
            'cierreInformeFinal' => $workflow->resumenInformeFinal($record, $request->user()),
            'opcionesDestinatariosIntermedio' => $workflow->destinatariosSeleccionables($record, EnfAccion::PROCESO_INFORME_INTERMEDIO),
            'opcionesDestinatariosFinal' => $workflow->destinatariosSeleccionables($record, EnfAccion::PROCESO_INFORME_FINAL),
        ]);
    }

    public function verPdf(EnfAccion $accion): View
    {
        $record = $accion->loadMissing($this->form018Relations());
        abort_unless($record->codigo_formulario === self::FORM_PROYECTO_ENF, 404);

        return view('enf.acciones.pdf-viewer', ['accion' => $record]);
    }

    public function contenidoPdf(EnfAccion $accion, FormDvus018DocumentService $documents)
    {
        return $this->form018PdfResponse($accion, $documents, 'inline');
    }

    public function descargarPdf(EnfAccion $accion, FormDvus018DocumentService $documents)
    {
        $record = $accion->loadMissing($this->form018Relations());

        $formCode = $record->codigo_formulario ?? null;

        abort_unless(in_array($formCode, [self::FORM_PROYECTO_ENF, self::FORM_CERTIFICADO_UNIVERSITARIO], true), 404);

        if ($formCode === self::FORM_PROYECTO_ENF) {
            return $this->documentResponse($documents, $record, 'attachment');
        }

        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $view = 'enf.acciones.partials.form-016-document';
        $orientation = 'portrait';

        $pdf = PDF::loadView($view, [
            'accion' => $record,
            'isPdf' => true,
        ])->setPaper('letter', $orientation)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'Arial')
            ->setOption('dpi', 96);

        return $pdf->download("{$formCode}-{$record->id}.pdf");
    }

    private function form018PdfResponse(EnfAccion $accion, FormDvus018DocumentService $documents, string $disposition)
    {
        $record = $accion->loadMissing($this->form018Relations());
        abort_unless($record->codigo_formulario === self::FORM_PROYECTO_ENF, 404);

        return $this->documentResponse($documents, $record, $disposition);
    }

    private function documentResponse(FormDvus018DocumentService $documents, EnfAccion $record, string $disposition)
    {
        $pdfPath = $documents->generatePdf($record);
        $filename = "FORM-DVUS-018-{$record->id}.pdf";
        $response = response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'X-Content-SHA256' => $documents->hash($pdfPath),
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
        $response->headers->set('Content-Disposition', $disposition.'; filename="'.$filename.'"');

        return $response;
    }

    private function form018Relations(): array
    {
        return [
            'tipoAccion',
            'modalidad',
            'centroFacultad',
            'departamentoAcademico',
            'carrera',
            'lugaresEjecucion.campus',
            'beneficiarios',
            'equipo',
            'participacionUniversitaria',
            'practicasAsignatura.asignatura',
            'practicasAsignatura.periodoAcademico',
            'contrapartes.tipoContraparte',
            'contrapartes.instrumentoAlianza',
            'objetivosEspecificos',
            'resultados',
            'presupuestos.detalles',
            'cronograma',
            'certificado.tipoCertificado',
            'certificado.figuraAcreditacion',
            'certificado.carreras.carrera',
            'certificado.carreras.centroFacultad',
            'espaciosAprendizaje',
            'informeFinal.documentosRevision',
            'informeFinal.constanciaFinalizacion',
            'informeIntermedio',
            'constanciaRegistro',
            'constanciaFinalizacion',
            'sistematizacion',
            'documentos',
            'firmas',
            'accionCatalogos.catalogo',
            'ods',
            'metasContribuye',
            'ejesUnah',
            'revisiones.flujoEtapa.rolRevisor',
        ];
    }

    public function aprobarRevision(Request $request, int $accion, int $revision): RedirectResponse
    {
        $record = EnfAccion::with(['revisiones.flujoEtapa.rolRevisor'])->findOrFail($accion);
        $revisionActual = $this->revisionActual($record);

        abort_unless($revisionActual && (int) $revisionActual->id === $revision, 403);
        abort_unless($this->usuarioPuedeRevisar($request->user(), $revisionActual), 403);

        app(EnfWorkflowService::class)->aprobarRevision(
            $revisionActual->fresh(['accion.revisiones', 'flujoEtapa.rolRevisor']),
            $request->user(),
            $request->input('observaciones')
        );

        return redirect()
            ->route('enf.acciones.show', $record)
            ->with('status', 'Etapa ENF aprobada correctamente.');
    }

    public function subsanarRevision(Request $request, int $accion, int $revision): RedirectResponse
    {
        $request->validate([
            'observaciones' => ['required', 'string', 'min:5'],
        ], [
            'observaciones.required' => 'Debe indicar la observación de subsanación.',
            'observaciones.min' => 'La observación de subsanación debe tener al menos :min caracteres.',
        ]);

        $record = EnfAccion::with(['revisiones.flujoEtapa.rolRevisor'])->findOrFail($accion);
        $revisionActual = $this->revisionActual($record);

        abort_unless($revisionActual && (int) $revisionActual->id === $revision, 403);
        abort_unless($this->usuarioPuedeRevisar($request->user(), $revisionActual), 403);

        app(EnfWorkflowService::class)->subsanarRevision(
            $revisionActual->fresh(['accion.revisiones', 'flujoEtapa.rolRevisor']),
            $request->input('observaciones'),
            $request->user()
        );

        return redirect()
            ->route('enf.acciones.show', $record)
            ->with('status', 'El registro fue enviado a subsanación.');
    }

    public function reenviarRevision(Request $request, int $accion): RedirectResponse
    {
        $record = EnfAccion::findOrFail($accion);

        abort_unless($this->usuarioPuedeReenviar($request->user(), $record), 403);

        try {
            app(EnfWorkflowService::class)->enviarInscripcion(
                $record,
                $request->user(),
                $request->input('destinatarios', [])
            );
        } catch (\Throwable $exception) {
            return redirect()
                ->route('enf.acciones.show', $record)
                ->withErrors(['flujo' => $exception->getMessage()]);
        }

        return redirect()
            ->route('enf.acciones.show', $record)
            ->with('status', 'El registro fue reenviado a revisión.');
    }

    private function revisionActual(EnfAccion $accion): ?EnfRevision
    {
        if ($accion->estado_flujo !== 'EN_REVISION') {
            return null;
        }

        return $accion->revisiones
            ->where('proceso', EnfAccion::PROCESO_INSCRIPCION)
            ->where('revision_ciclo', (int) $accion->revision_ciclo)
            ->whereIn('estado', $this->estadosRevisionPendiente())
            ->sortBy('orden')
            ->first();
    }

    private function usuarioPuedeRevisar(?User $user, EnfRevision $revision): bool
    {
        $activeRole = $user?->activeRole;

        if (! $user || ! $activeRole) {
            return false;
        }

        if (filled($revision->rol_requerido) && $revision->rol_requerido !== $activeRole->name) {
            return false;
        }

        if ($revision->asignado_usuario_id) {
            return (int) $revision->asignado_usuario_id === (int) $user->id;
        }

        if ($revision->responsable_usuario_id) {
            return (int) $revision->responsable_usuario_id === (int) $user->id;
        }

        return filled($revision->rol_requerido) && $revision->rol_requerido === $activeRole->name;
    }

    private function usuarioPuedeReenviar(?User $user, EnfAccion $accion): bool
    {
        return $user
            && (int) $accion->creado_por_usuario_id === (int) $user->id
            && in_array($accion->estado_flujo, ['BORRADOR', 'SUBSANACION'], true);
    }

    private function estadosRevisionPendiente(): array
    {
        return ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];
    }

    public function update(UpdateEnfAccionRequest $request, int $accion): JsonResponse|RedirectResponse
    {
        $record = EnfAccion::findOrFail($accion);
        $validated = $request->validated();
        $guardarSoloBorrador = $request->boolean('guardar_solo_borrador');

        abort_unless($this->usuarioPuedeEditarBorrador($request->user(), $record), 403);

        try {
            $flujoIniciado = DB::transaction(function () use ($record, $validated, $request, $guardarSoloBorrador): bool {
                $this->actualizarRegistroFormulario($record, $validated, $request, true);

                return ! $guardarSoloBorrador && app(EnfWorkflowService::class)->enviarInscripcion(
                    $record->fresh(),
                    $request->user(),
                    $request->input('destinatarios', [])
                );
            });
        } catch (\RuntimeException $exception) {
            return $this->respuestaErrorFlujo($request, $exception);
        }

        if (! $request->expectsJson()) {
            return $this->redireccionDespuesDeGuardar($request, $flujoIniciado, true);
        }

        return response()->json($record->fresh());
    }

    private function redireccionDespuesDeGuardar(Request $request, bool $flujoIniciado, bool $actualizado): RedirectResponse
    {
        if ($request->boolean('guardar_solo_borrador')) {
            $codigoFormulario = $request->string('codigo_formulario')->toString() ?: self::FORM_PROYECTO_ENF;
            $mostrarComoActualizado = $actualizado && ! $request->boolean('es_nuevo_borrador');
            $mensaje = $mostrarComoActualizado
                ? "El borrador {$codigoFormulario} se actualizó correctamente."
                : "El borrador {$codigoFormulario} se guardó correctamente.";

            Notification::make()
                ->title($mostrarComoActualizado ? 'Borrador actualizado' : 'Borrador guardado')
                ->body($mensaje)
                ->success()
                ->send();

            return redirect()
                ->route('proyectosDocente', ['tipo' => 'educacion_no_formal'])
                ->with('status', $mensaje);
        }

        $mensaje = $flujoIniciado
            ? 'Acción de Educación No Formal enviada a revisión.'
            : 'Acción de Educación No Formal guardada como borrador. No hay flujo de revisión activo para este formulario.';

        return redirect()
            ->route('proyectosDocente', ['tipo' => 'educacion_no_formal'])
            ->with('status', $mensaje);
    }

    private function respuestaErrorFlujo(Request $request, \RuntimeException $exception): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return redirect()->back()->withInput()->withErrors(['flujo' => $exception->getMessage()]);
    }

    private function limpiarRelacionesFormulario(EnfAccion $accion): void
    {
        $this->forceDeleteRelation($accion->lugaresEjecucion());
        $this->forceDeleteRelation($accion->accionCatalogos());
        $this->forceDeleteRelation($accion->beneficiarios());
        $this->forceDeleteRelation($accion->equipo());
        $this->forceDeleteRelation($accion->participacionUniversitaria());
        $this->forceDeleteRelation($accion->practicasAsignatura());
        $this->forceDeleteRelation($accion->contrapartes());
        $this->forceDeleteRelation($accion->objetivosEspecificos());
        $this->forceDeleteRelation($accion->resultados());
        $this->forceDeleteRelation($accion->accionOds());
        $this->forceDeleteRelation($accion->accionEjesUnah());
        $this->forceDeleteRelation($accion->cronograma());
        $this->forceDeleteRelation($accion->espaciosAprendizaje());
        $this->forceDeleteRelation($accion->firmas());

        $accion->presupuestos()->with('detalles')->get()->each(function ($presupuesto): void {
            $this->forceDeleteRelation($presupuesto->detalles());
            $presupuesto->forceDelete();
        });

        $accion->documentos()
            ->withTrashed()
            ->where(function ($query): void {
                $query->where('ruta', 'pendiente')
                    ->orWhereNull('ruta');
            })
            ->get()
            ->each(fn ($documento) => $documento->forceDelete());

        if ($accion->certificado) {
            $this->forceDeleteRelation($accion->certificado->carreras());
            $accion->certificado->forceDelete();
        }
    }

    private function forceDeleteRelation($relation): void
    {
        $relation
            ->withTrashed()
            ->get()
            ->each(fn ($record) => $record->forceDelete());
    }

    public function edit(Request $request, int $accion): JsonResponse|View
    {
        $record = EnfAccion::with([
            'lugaresEjecucion',
            'beneficiarios',
            'equipo',
            'participacionUniversitaria',
            'practicasAsignatura',
            'contrapartes',
            'objetivosEspecificos',
            'resultados',
            'presupuestos.detalles',
            'cronograma',
            'certificado.tipoCertificado',
            'certificado.carreras.carrera',
            'espaciosAprendizaje',
            'documentos',
            'firmas',
            'accionCatalogos',
            'accionOds',
            'accionEjesUnah',
        ])->findOrFail($accion);

        if ($request->expectsJson()) {
            return response()->json($record);
        }

        $puedeEditarBorrador = $request->user()
            && (int) $record->creado_por_usuario_id === (int) $request->user()->id
            && in_array($record->estado_flujo, ['BORRADOR', 'SUBSANACION', 'SUBSANACIÓN'], true);

        $puedeAbrirActualizacionEnCurso = $request->user()
            && (int) $record->creado_por_usuario_id === (int) $request->user()->id
            && in_array($record->codigo_formulario, [self::FORM_CERTIFICADO_UNIVERSITARIO, self::FORM_PROYECTO_ENF], true)
            && $record->estado_flujo === 'APROBADO';

        abort_unless($puedeEditarBorrador || $puedeAbrirActualizacionEnCurso, 403);

        $formCode = ($record->codigo_formulario ?? null) === self::FORM_CERTIFICADO_UNIVERSITARIO
            ? self::FORM_CERTIFICADO_UNIVERSITARIO
            : self::FORM_PROYECTO_ENF;

        $selectedTipoAccionEnfId = $record->accionCatalogos
            ->first(fn ($catalogo) => $catalogo->tipo === 'tipo_accion_enf')
            ?->enf_catalogo_id
            ?: $this->tipoAccionEnfDefaultId($formCode);

        return view(
            $formCode === self::FORM_CERTIFICADO_UNIVERSITARIO ? 'enf.acciones.create-certificado' : 'enf.acciones.create',
            $this->formViewData($formCode, $selectedTipoAccionEnfId, $record)
        );
    }

    public function destroy(int $accion): JsonResponse
    {
        EnfAccion::findOrFail($accion)->delete();

        return response()->json(status: 204);
    }
}
