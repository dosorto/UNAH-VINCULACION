<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Mail\EnfRevisionAsignada;
use App\Http\Requests\ENF\StoreEnfAccionRequest;
use App\Http\Requests\ENF\UpdateEnfAccionRequest;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfCatalogo;
use App\Models\ENF\EnfRevision;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\EjesPrioritariosUnah;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Models\Personal\Empleado;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EnfAccionController extends Controller
{
    private const TIPO_ACCION_ENF_VISIBLE = 'Programa de educacion continua';

    public function tipos(): View
    {
        return view('enf.acciones.tipos', [
            'tipos' => EnfCatalogo::where('tipo', 'tipo_accion_enf')
                ->where('nombre', self::TIPO_ACCION_ENF_VISIBLE)
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

    public function create(Request $request): JsonResponse|View
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Formulario ENF disponible.']);
        }

        return view('enf.acciones.create', [
            'tiposAccion' => VinculacionTipoAccion::where('codigo', 'EDUCACION_NO_FORMAL')->orderBy('nombre')->get(),
            'selectedTipoAccionEnfId' => $request->integer('tipo_accion_enf_id') ?: null,
            'clearDraftOnLoad' => $request->boolean('nuevo') && ! $request->session()->hasOldInput(),
            'programasAprobados' => $this->programasAprobadosEducacionContinua(),
            'modalidades' => Modalidad::orderBy('nombre')->get(),
            'centrosFacultad' => FacultadCentro::orderBy('nombre')->get(),
            'departamentosAcademicos' => DepartamentoAcademico::orderBy('nombre')->get(),
            'carreras' => Carrera::orderBy('nombre')->get(),
            'campus' => Campus::orderBy('nombre_campus')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'municipios' => Municipio::orderBy('nombre')->get(),
            'empleados' => Empleado::orderBy('nombre_completo')->limit(250)->get(),
            'catalogos' => EnfCatalogo::query()
                ->orderBy('tipo')
                ->orderBy('orden')
                ->orderBy('nombre')
                ->get()
                ->groupBy('tipo'),
            'odsList' => Od::orderBy('id')->get(),
            'metasContribuye' => MetaContribuye::with('ods')->orderBy('ods_id')->orderBy('numero_meta')->get(),
            'ejesUnah' => EjesPrioritariosUnah::orderBy('nombre')->get(),
        ]);
    }

    private function programasAprobadosEducacionContinua()
    {
        return EnfAccion::query()
            ->with(['modalidad', 'centroFacultad', 'departamentoAcademico', 'carrera', 'accionCatalogos.catalogo'])
            ->whereIn('estado_flujo', ['APROBADO', 'Aprobado', 'aprobado'])
            ->whereHas('accionCatalogos.catalogo', function ($query) {
                $query->where('tipo', 'tipo_accion_enf')
                    ->where('nombre', self::TIPO_ACCION_ENF_VISIBLE);
            })
            ->orderBy('nombre_accion')
            ->get();
    }

    public function store(StoreEnfAccionRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $accion = DB::transaction(function () use ($request, $validated) {
            $accionFields = [
                'codigo_formulario',
                'tipo_accion_id',
                'modalidad_id',
                'centro_facultad_id',
                'departamento_academico_id',
                'carrera_id',
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
                'resumen',
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

            $data = array_intersect_key($validated, array_flip($accionFields));
            $data['genera_ingresos'] = $request->boolean('genera_ingresos');
            $data['total_horas'] = $data['total_horas']
                ?? ((int) ($data['horas_teoricas'] ?? 0) + (int) ($data['horas_practicas'] ?? 0));
            $data['creado_por_usuario_id'] = $request->user()?->id;
            $data['modificado_por_usuario_id'] = $request->user()?->id;

            $accion = EnfAccion::create($data);

            $this->guardarRelacionesFormulario($accion, $validated);
            $this->iniciarFlujoRevision($accion);

            return $accion;
        });

        if (! $request->expectsJson()) {
            return redirect()
                ->route('enf.acciones.show', $accion)
                ->with('status', 'Accion de Educacion No Formal registrada correctamente.');
        }

        return response()->json($accion->fresh(), 201);
    }

    private function iniciarFlujoRevision(EnfAccion $accion): void
    {
        $flujo = $this->resolverFlujo($accion);

        if (! $flujo || $flujo->etapas->isEmpty()) {
            return;
        }

        $nextCycle = ((int) $accion->revision_ciclo) + 1;
        $createdRevisions = collect();

        foreach ($flujo->etapas as $stage) {
            $defaultReviewer = $this->resolverRevisorPredeterminado($stage);
            $requiresAssignment = (bool) ($stage->requiere_asignacion ?? false);

            $createdRevisions->push(EnfRevision::create([
                'enf_accion_id' => $accion->id,
                'flujo_aprobacion_etapa_id' => $stage->id,
                'revision_ciclo' => $nextCycle,
                'orden' => $stage->orden,
                'etapa_codigo' => $stage->codigo,
                'etapa_nombre' => $stage->nombre,
                'rol_requerido' => $stage->rolRevisor?->name,
                'responsable_usuario_id' => $stage->usuario_responsable_id,
                'asignado_usuario_id' => $requiresAssignment ? null : $defaultReviewer?->id,
                'estado' => $requiresAssignment
                    ? 'PENDIENTE_ASIGNACION'
                    : ($defaultReviewer ? 'ASIGNADO' : 'PENDIENTE'),
            ]));
        }

        $accion->update([
            'estado_flujo' => 'EN_REVISION',
            'revision_ciclo' => $nextCycle,
            'flujo_aprobacion_id' => $flujo->id,
        ]);

        $firstRevision = $createdRevisions->sortBy('orden')->first();

        if ($firstRevision) {
            $this->notificarRevision($accion->fresh(), $firstRevision->fresh('flujoEtapa.rolRevisor'));
        }
    }

    private function resolverFlujo(EnfAccion $accion): ?FlujoAprobacion
    {
        return FlujoAprobacion::query()
            ->with([
                'etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden'),
                'etapas.rolRevisor',
                'etapas.usuarioResponsable',
            ])
            ->where('proceso', 'PROYECTO')
            ->where('tipo_accion_id', $accion->tipo_accion_id)
            ->where('activo', true)
            ->first();
    }

    private function resolverRevisorPredeterminado(FlujoAprobacionEtapa $stage): ?User
    {
        if ($stage->usuario_responsable_id && ! $stage->requiere_asignacion) {
            return $stage->usuarioResponsable;
        }

        if (! $stage->rolRevisor?->name) {
            return null;
        }

        return User::role($stage->rolRevisor->name)->orderBy('name')->first();
    }

    private function notificarRevision(EnfAccion $accion, EnfRevision $revision): void
    {
        $users = collect();

        if ($revision->asignado_usuario_id) {
            $users = User::query()->whereKey($revision->asignado_usuario_id)->get();
        } elseif ($revision->flujoEtapa?->rolRevisor?->name) {
            $users = User::role($revision->flujoEtapa->rolRevisor->name)->orderBy('name')->get();
        }

        $emails = $users->pluck('email')->filter()->unique()->values();

        if ($emails->isEmpty()) {
            return;
        }

        try {
            Mail::to($emails->all())->queue(new EnfRevisionAsignada($accion, $revision));
        } catch (\Throwable $exception) {
            Log::warning('No se pudo notificar la revisión ENF.', [
                'enf_accion_id' => $accion->id,
                'revision_id' => $revision->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function guardarRelacionesFormulario(EnfAccion $accion, array $data): void
    {
        if (
            filled($data['campus_id'] ?? null)
            || filled($data['departamento_id'] ?? null)
            || filled($data['municipio_id'] ?? null)
            || filled($data['nombre_lugar'] ?? null)
            || filled($data['aula_auditorio'] ?? null)
            || filled($data['edificio'] ?? null)
            || filled($data['modalidad_ejecucion'] ?? null)
            || filled($data['plataformas'] ?? [])
        ) {
            $direccion = collect([
                $data['aula_auditorio'] ?? null,
                $data['edificio'] ?? null,
                collect($data['plataformas'] ?? [])->filter()->isNotEmpty()
                    ? 'Plataformas: '.EnfCatalogo::whereIn('id', $data['plataformas'])->pluck('nombre')->implode(', ')
                    : null,
            ])->filter()->implode(' | ');

            $accion->lugaresEjecucion()->create([
                'campus_id' => $data['campus_id'] ?? null,
                'departamento_id' => $data['departamento_id'] ?? null,
                'municipio_id' => $data['municipio_id'] ?? null,
                'nombre_lugar' => $data['nombre_lugar'] ?? null,
                'direccion' => $direccion ?: null,
                'modalidad_ejecucion' => $data['modalidad_ejecucion'] ?? null,
            ]);
        }

        foreach (($data['catalogos'] ?? []) as $tipo => $catalogoIds) {
            foreach (array_filter($catalogoIds ?? []) as $catalogoId) {
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
                'otros' => $otros,
                'total' => (int) ($beneficiarios['total'] ?? ($hombres + $mujeres + $otros)),
                'descripcion' => $beneficiarios['descripcion'] ?? null,
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
                ]);
            }
        }

        $contraparte = $data['contraparte'] ?? [];
        if (filled($contraparte['nombre'] ?? null)) {
            $accion->contrapartes()->create($contraparte);
        }

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
                'enf_objetivo_especifico_id' => $objetivoIds[0] ?? null,
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

        foreach (($data['cronograma'] ?? []) as $item) {
            if (! filled($item['actividad'] ?? null)) {
                continue;
            }

            $accion->cronograma()->create([
                'actividad' => $item['actividad'],
                'fecha_inicio' => $item['fecha_inicio'] ?? null,
                'fecha_finalizacion' => $item['fecha_finalizacion'] ?? null,
                'descripcion' => filled($item['responsable'] ?? null) ? 'Responsable: '.$item['responsable'] : null,
            ]);
        }

        foreach (array_filter($data['documentos_requeridos'] ?? []) as $documento) {
            $accion->documentos()->create([
                'tipo_documento' => 'requerido_form_018',
                'nombre' => $documento,
                'ruta' => 'pendiente',
            ]);
        }
    }

    public function show(Request $request, int $accion): JsonResponse|View
    {
        $record = EnfAccion::with([
            'tipoAccion',
            'modalidad',
            'centroFacultad',
            'departamentoAcademico',
            'carrera',
            'lugaresEjecucion',
            'beneficiarios',
            'equipo',
            'contrapartes',
            'objetivosEspecificos',
            'resultados',
            'presupuestos.detalles',
            'cronograma',
            'certificado',
            'informeFinal',
            'sistematizacion',
            'documentos',
            'firmas',
        ])->findOrFail($accion);

        if ($request->expectsJson()) {
            return response()->json($record);
        }

        return view('enf.acciones.show', ['accion' => $record]);
    }

    public function update(UpdateEnfAccionRequest $request, int $accion): JsonResponse
    {
        $record = EnfAccion::findOrFail($accion);
        $data = $request->validated();
        $data['modificado_por_usuario_id'] = $request->user()?->id;
        $record->update($data);

        return response()->json($record->fresh());
    }

    public function edit(int $accion): JsonResponse
    {
        return response()->json(EnfAccion::findOrFail($accion));
    }

    public function destroy(int $accion): JsonResponse
    {
        EnfAccion::findOrFail($accion)->delete();

        return response()->json(status: 204);
    }
}
