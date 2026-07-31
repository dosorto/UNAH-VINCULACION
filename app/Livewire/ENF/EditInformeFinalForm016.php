<?php

namespace App\Livewire\ENF;

use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfInformeFinal;
use App\Models\Proyecto\Od;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditInformeFinalForm016 extends Component
{
    use WithFileUploads;

    private const TOTAL_STEPS = 8;

    public EnfAccion $accion;
    public EnfInformeFinal $informe;
    public int $currentStep = 1;
    public array $general = [];
    public array $participantes = [];
    public array $accionesEjecutadas = [];
    public array $accionesNoEjecutadas = [];
    public array $anexos = [];
    public array $anexoArchivos = [];
    public array $fotografiasTemporales = [];
    public string $mensaje = '';
    public string $estadoGuardado = 'guardado';

    public function mount(EnfAccion $accion): void
    {
        abort_unless($accion->codigo_formulario === 'FORM-DVUS-016', 404);

        $this->accion = $this->loadAccion($accion);
        $this->informe = EnfInformeFinal::firstOrCreate(
            ['enf_accion_id' => $accion->id],
            $this->initialInformeData()
        );
        $this->cargarFormulario();
    }

    public function goToStep(int $step): void
    {
        $step = max(1, min(self::TOTAL_STEPS, $step));
        if ($step === $this->currentStep) {
            return;
        }

        if ($step > $this->currentStep) {
            $this->validateCurrentStep();
            $this->persistir();
        }

        $this->currentStep = $step;
    }

    public function siguiente(): void
    {
        $this->validateCurrentStep();
        $this->persistir();
        $this->currentStep = min(self::TOTAL_STEPS, $this->currentStep + 1);
    }

    public function anterior(): void
    {
        $this->currentStep = max(1, $this->currentStep - 1);
    }

    public function guardarBorrador(): void
    {
        $this->persistir();
        $this->mensaje = 'Informe final ENF guardado como borrador.';
    }

    public function validarInforme(): void
    {
        $this->validateAll();
        $this->persistir();
        $this->informe->update(['estado' => 'completo']);
        $this->general['estado'] = 'completo';
        $this->mensaje = 'El informe final FORM-DVUS-016 quedo marcado como completo.';
    }

    public function agregarFila(string $grupo): void
    {
        $defaults = [
            'participantes' => [
                'nombre_completo' => '',
                'documento_identidad' => '',
                'correo' => '',
                'sexo' => '',
                'edad' => 0,
                'certificado_emitido' => true,
                'codigo_certificado' => '',
            ],
            'accionesEjecutadas' => [
                'actividad' => '',
                'fecha_inicio' => null,
                'fecha_finalizacion' => null,
                'resultados' => '',
                'observaciones' => '',
            ],
            'accionesNoEjecutadas' => [
                'actividad' => '',
                'motivo' => '',
                'acciones_correctivas' => '',
                'fecha_reprogramacion' => null,
            ],
            'anexos' => [
                'tipo_documento' => 'otros',
                'nombre' => '',
                'descripcion' => '',
                'ruta' => 'pendiente',
                'mime_type' => null,
                'tamano_bytes' => 0,
            ],
        ];

        abort_unless(array_key_exists($grupo, $defaults), 404);
        $this->{$grupo}[] = $defaults[$grupo];
    }

    public function quitarFila(string $grupo, int $index): void
    {
        abort_unless(in_array($grupo, ['participantes', 'accionesEjecutadas', 'accionesNoEjecutadas', 'anexos'], true), 404);

        if ($grupo === 'anexos') {
            if (! empty($this->anexos[$index]['id'])) {
                $documento = $this->accion->documentos()->whereKey($this->anexos[$index]['id'])->first();
                if ($documento) {
                    if ($documento->ruta && $documento->ruta !== 'pendiente') {
                        Storage::disk('public')->delete($documento->ruta);
                    }
                    $documento->delete();
                }
            }

            unset($this->anexos[$index], $this->anexoArchivos[$index]);
            $this->anexos = array_values($this->anexos);
            $this->anexoArchivos = array_values($this->anexoArchivos);
            $this->accion = $this->loadAccion($this->accion->refresh());

            return;
        }

        if (! empty($this->{$grupo}[$index]['id'])) {
            $relation = [
                'participantes' => 'participantesFinales',
                'accionesEjecutadas' => 'accionesEjecutadas',
                'accionesNoEjecutadas' => 'accionesNoEjecutadas',
            ][$grupo];
            $this->informe->{$relation}()->whereKey($this->{$grupo}[$index]['id'])->delete();
        }

        unset($this->{$grupo}[$index]);
        $this->{$grupo} = array_values($this->{$grupo});
    }

    public function updated(string $propertyName): void
    {
        if (! $this->debeAutoguardar($propertyName)) {
            return;
        }

        try {
            $this->estadoGuardado = 'guardando';
            $this->validateOnly($propertyName, $this->draftRules(), $this->validationMessages(), $this->validationAttributes());
            $this->persistir(false);
        } catch (ValidationException $e) {
            $this->estadoGuardado = 'error';
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->estadoGuardado = 'error';
        }
    }

    public function updatedFotografiasTemporales(): void
    {
        $existentes = collect($this->anexos)->filter(fn ($row) => ($row['tipo_documento'] ?? null) === 'fotografia')->count();
        $disponibles = max(0, 20 - $existentes);
        $guardadas = 0;
        $huboErrores = false;

        foreach ($this->fotografiasTemporales as $index => $foto) {
            if ($index >= $disponibles) {
                $this->addError('fotografiasTemporales', 'Solo se permiten hasta 20 fotografias por informe.');
                $huboErrores = true;
                continue;
            }

            $validator = Validator::make(
                ['fotografia' => $foto],
                ['fotografia' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240']],
                [
                    'fotografia.image' => 'El formato de la fotografia no esta permitido.',
                    'fotografia.mimes' => 'El formato de la fotografia no esta permitido.',
                    'fotografia.max' => 'La fotografia supera el tamano maximo de 10 MB.',
                ]
            );

            if ($validator->fails()) {
                $this->addError("fotografiasTemporales.$index", $foto->getClientOriginalName().': '.$validator->errors()->first('fotografia'));
                $huboErrores = true;
                continue;
            }

            $ruta = $foto->store('enf/informes-finales/'.$this->accion->id.'/fotografias', 'public');
            $this->accion->documentos()->create([
                'tipo_documento' => 'informe_final_fotografia',
                'nombre' => $foto->getClientOriginalName(),
                'ruta' => $ruta,
                'mime_type' => $foto->getClientMimeType(),
                'tamano_bytes' => $foto->getSize(),
                'subido_por_usuario_id' => auth()->id(),
                'descripcion' => 'Fotografia del informe final FORM-DVUS-016.',
            ]);
            $guardadas++;
            $existentes++;
        }

        $this->fotografiasTemporales = [];
        if ($guardadas > 0) {
            $this->cargarFormulario();
            $this->dispatch('fotografias-guardadas');
        }
        $this->estadoGuardado = $huboErrores ? 'error' : 'guardado';
    }

    public function quitarFotografia(int $id): void
    {
        $foto = $this->accion->documentos()->whereKey($id)->where('tipo_documento', 'informe_final_fotografia')->firstOrFail();
        if ($foto->ruta && $foto->ruta !== 'pendiente') {
            Storage::disk('public')->delete($foto->ruta);
        }
        $foto->delete();
        $this->cargarFormulario();
        $this->estadoGuardado = 'guardado';
    }

    public function anexoUrl(?string $ruta): ?string
    {
        if (blank($ruta) || $ruta === 'pendiente') {
            return null;
        }

        return filter_var($ruta, FILTER_VALIDATE_URL) ? $ruta : Storage::disk('public')->url($ruta);
    }

    public function getBeneficiariosProgramadosProperty(): array
    {
        $beneficiarios = $this->accion->beneficiarios;

        return [
            'hombres' => (int) ($beneficiarios?->hombres ?? 0),
            'mujeres' => (int) ($beneficiarios?->mujeres ?? 0),
            'total' => (int) ($beneficiarios?->total ?? (($beneficiarios?->hombres ?? 0) + ($beneficiarios?->mujeres ?? 0))),
        ];
    }

    public function getResumenParticipantesProperty(): array
    {
        $pair = fn (string $prefix) => [
            'hombres' => (int) ($this->general[$prefix.'_hombres'] ?? 0),
            'mujeres' => (int) ($this->general[$prefix.'_mujeres'] ?? 0),
            'total' => (int) ($this->general[$prefix.'_hombres'] ?? 0) + (int) ($this->general[$prefix.'_mujeres'] ?? 0),
        ];

        return [
            'inscritos' => $pair('inscritos'),
            'no_presentaron' => $pair('no_presentaron'),
            'abandonaron' => $pair('abandonaron'),
            'reprobaron' => $pair('reprobaron'),
            'aprobaron' => $pair('aprobaron'),
            'graduados_unah' => $pair('graduados_unah'),
        ];
    }

    public function getTotalesPresupuestoProperty(): array
    {
        $sum = fn (?string $tipo) => (float) $this->accion->presupuestos
            ->firstWhere('tipo', $tipo)
            ?->detalles
            ?->sum(fn ($row) => (float) $row->total) ?? 0;

        $ingresos = $sum('ingresos');
        $egresos = $sum('egresos');

        return [
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'excedente' => $ingresos - $egresos,
            'aporte_unah' => $sum('aporte_unah'),
        ];
    }

    public function getPorcentajesValoracionProperty(): array
    {
        $muestra = (int) ($this->general['valoracion_muestra'] ?? 0);

        return collect(['excelente', 'muy_buena', 'regular', 'mala'])
            ->mapWithKeys(fn ($key) => [$key => $muestra > 0 ? round((int) ($this->general['valoracion_'.$key] ?? 0) / $muestra * 100, 2) : 0])
            ->all();
    }

    public function getResumenRevisionProperty(): array
    {
        return [
            'Participantes acreditados' => collect($this->participantes)->filter(fn ($row) => filled($row['nombre_completo'] ?? null))->count(),
            'Acciones ejecutadas' => collect($this->accionesEjecutadas)->filter(fn ($row) => filled($row['actividad'] ?? null))->count(),
            'Anexos' => collect($this->anexos)->filter(fn ($row) => filled($row['nombre'] ?? null))->count(),
            'Estado' => strtoupper($this->general['estado'] ?? 'BORRADOR'),
        ];
    }

    public function getFotografiasProperty(): array
    {
        return collect($this->anexos)
            ->map(fn ($row, $index) => $row + ['indice_formulario' => $index])
            ->where('tipo_documento', 'fotografia')
            ->values()
            ->all();
    }

    public function getDocumentosAnexosProperty(): array
    {
        return collect($this->anexos)
            ->map(fn ($row, $index) => $row + ['indice_formulario' => $index])
            ->reject(fn ($row) => ($row['tipo_documento'] ?? null) === 'fotografia')
            ->values()
            ->all();
    }

    public function getCamposPendientesProperty(): array
    {
        $pendientes = [];

        if (blank($this->general['resumen_ejecutivo'] ?? null)) {
            $pendientes[] = 'Resumen ejecutivo';
        }
        if (blank($this->general['modalidad_acreditacion'] ?? null)) {
            $pendientes[] = 'Figura de acreditacion';
        }
        if (! collect($this->participantes)->contains(fn ($row) => filled($row['nombre_completo'] ?? null))) {
            $pendientes[] = 'Participantes acreditados';
        }
        if (blank($this->general['resultados_obtenidos'] ?? null)) {
            $pendientes[] = 'Resultados obtenidos';
        }
        if (blank($this->general['observaciones_finales'] ?? null)) {
            $pendientes[] = 'Observaciones finales';
        }
        if (blank($this->general['fecha_aprobacion'] ?? null)) {
            $pendientes[] = 'Fecha de cierre';
        }
        if (! (bool) ($this->general['confirmacion_veracidad'] ?? false)) {
            $pendientes[] = 'Confirmacion de veracidad';
        }

        return $pendientes;
    }

    public function getInconsistenciasRevisionProperty(): array
    {
        $issues = [];
        $inscritos = (int) ($this->general['inscritos_hombres'] ?? 0) + (int) ($this->general['inscritos_mujeres'] ?? 0);
        $aprobaron = (int) ($this->general['aprobaron_hombres'] ?? 0) + (int) ($this->general['aprobaron_mujeres'] ?? 0);
        $muestra = (int) ($this->general['valoracion_muestra'] ?? 0);
        $totalValoracion = (int) ($this->general['valoracion_total_beneficiarios'] ?? 0);

        if ($inscritos > 0 && $aprobaron > $inscritos) {
            $issues[] = 'El total de aprobados supera el total de inscritos.';
        }
        if ($totalValoracion > 0 && $muestra > $totalValoracion) {
            $issues[] = 'La muestra de evaluacion supera el total de beneficiarios valorados.';
        }
        if (collect($this->anexos)->contains(fn ($doc) => blank($doc['ruta'] ?? null) || ($doc['ruta'] ?? null) === 'pendiente')) {
            $issues[] = 'Hay documentos registrados como pendientes.';
        }
        if (! $this->accion->equipo->contains(fn ($row) => $row->es_coordinador || $row->rol === 'Coordinador de la accion')) {
            $issues[] = 'No se encontro coordinador definido en el equipo.';
        }

        return $issues;
    }

    public function isStepComplete(int $step): bool
    {
        return match ($step) {
            1 => filled($this->general['fecha_presentacion'] ?? null)
                && filled($this->general['resumen_ejecutivo'] ?? null),
            2 => $this->accion->equipo->isNotEmpty(),
            3 => collect($this->participantes)->contains(fn ($row) => filled($row['nombre_completo'] ?? null)),
            4 => (int) ($this->general['inscritos_hombres'] ?? 0) + (int) ($this->general['inscritos_mujeres'] ?? 0) > 0,
            5 => filled($this->general['modalidad_acreditacion'] ?? null)
                || $this->accion->espaciosAprendizaje->isNotEmpty(),
            6 => filled($this->general['resultados_obtenidos'] ?? null)
                && filled($this->general['transformacion_lograda'] ?? null),
            7 => (int) ($this->general['valoracion_muestra'] ?? 0) > 0
                || filled($this->general['observaciones_finales'] ?? null),
            8 => (bool) ($this->general['confirmacion_veracidad'] ?? false),
            default => false,
        };
    }

    public function render(): View
    {
        return view('livewire.enf.edit-informe-final-form016', [
            'odsCatalogo' => Od::orderBy('id')->get(),
        ]);
    }

    private function loadAccion(EnfAccion $accion): EnfAccion
    {
        return $accion->load([
            'modalidad',
            'centroFacultad',
            'departamentoAcademico',
            'carrera',
            'lugaresEjecucion.departamento',
            'lugaresEjecucion.municipio',
            'beneficiarios',
            'equipo',
            'contrapartes.tipoContraparte',
            'objetivosEspecificos',
            'resultados',
            'presupuestos.detalles',
            'cronograma',
            'certificado.tipoCertificado',
            'certificado.figuraAcreditacion',
            'certificado.carreras.carrera',
            'espaciosAprendizaje',
            'documentos',
            'ods',
            'metasContribuye',
        ]);
    }

    private function initialInformeData(): array
    {
        $beneficiarios = $this->accion->beneficiarios;

        return [
            'fecha_presentacion' => now()->toDateString(),
            'resumen_ejecutivo' => $this->accion->resumen,
            'resultados_obtenidos' => null,
            'inscritos_hombres' => (int) ($beneficiarios?->hombres ?? 0),
            'inscritos_mujeres' => (int) ($beneficiarios?->mujeres ?? 0),
            'modalidad_acreditacion' => $this->accion->certificado?->figuraAcreditacion?->nombre,
            'aprobado_por_empleado_id' => null,
            'estado' => 'borrador',
        ];
    }

    private function cargarFormulario(): void
    {
        $this->accion = $this->loadAccion($this->accion->refresh());
        $this->informe->load(['participantesFinales', 'accionesEjecutadas', 'accionesNoEjecutadas']);
        $date = fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null;
        $this->general = Arr::only($this->informe->toArray(), $this->generalFields());
        $this->general['fecha_presentacion'] = $date($this->informe->fecha_presentacion);
        $this->general['fecha_aprobacion'] = $date($this->informe->fecha_aprobacion);
        if (blank($this->general['modalidad_acreditacion'] ?? null)) {
            $this->general['modalidad_acreditacion'] = $this->accion->certificado?->figuraAcreditacion?->nombre;
        }
        $this->participantes = $this->informe->participantesFinales->toArray();
        $this->accionesEjecutadas = $this->informe->accionesEjecutadas->map(function ($row) use ($date) {
            $data = $row->toArray();
            $data['fecha_inicio'] = $date($row->fecha_inicio);
            $data['fecha_finalizacion'] = $date($row->fecha_finalizacion);
            return $data;
        })->all();
        $this->accionesNoEjecutadas = $this->informe->accionesNoEjecutadas->map(function ($row) use ($date) {
            $data = $row->toArray();
            $data['fecha_reprogramacion'] = $date($row->fecha_reprogramacion);
            return $data;
        })->all();
        $this->anexos = $this->accion->documentos->map(fn ($doc) => [
            'id' => $doc->id,
            'tipo_documento' => str($doc->tipo_documento ?: 'otros')->after('informe_final_')->toString(),
            'tipo_documento_original' => $doc->tipo_documento ?: 'otros',
            'nombre' => $doc->nombre,
            'descripcion' => $doc->descripcion,
            'ruta' => $doc->ruta,
            'mime_type' => $doc->mime_type,
            'tamano_bytes' => $doc->tamano_bytes,
            'origen_informe' => str_starts_with((string) $doc->tipo_documento, 'informe_final_'),
            'created_at' => $doc->created_at?->format('Y-m-d'),
        ])->values()->all();
        $this->anexoArchivos = [];

        if (empty($this->accionesEjecutadas)) {
            $this->accionesEjecutadas = $this->accion->cronograma->map(fn ($row) => [
                'actividad' => $row->actividad,
                'fecha_inicio' => $date($row->fecha_inicio),
                'fecha_finalizacion' => $date($row->fecha_finalizacion),
                'resultados' => $row->producto,
                'observaciones' => '',
            ])->values()->all();
        }
    }

    private function persistir(bool $reload = true): void
    {
        $this->estadoGuardado = 'guardando';

        DB::transaction(function () {
            $this->informe->update(Arr::only($this->general, $this->generalFields()));
            $this->syncRows('participantesFinales', $this->participantes, [
                'estudiante_id',
                'user_id',
                'nombre_completo',
                'documento_identidad',
                'correo',
                'sexo',
                'edad',
                'certificado_emitido',
                'codigo_certificado',
            ]);
            $this->syncRows('accionesEjecutadas', $this->accionesEjecutadas, [
                'actividad',
                'fecha_inicio',
                'fecha_finalizacion',
                'resultados',
                'observaciones',
            ]);
            $this->syncRows('accionesNoEjecutadas', $this->accionesNoEjecutadas, [
                'actividad',
                'motivo',
                'acciones_correctivas',
                'fecha_reprogramacion',
            ]);
            $this->syncAnexos();
        }, 3);

        $this->informe->refresh();
        if ($reload) {
            $this->cargarFormulario();
        }
        $this->estadoGuardado = 'guardado';
    }

    private function syncRows(string $relation, array $rows, array $fields): void
    {
        $ids = [];
        foreach ($rows as $index => $row) {
            if (! $this->rowHasContent($row, $fields)) {
                continue;
            }
            $data = Arr::only($row, $fields);
            $id = isset($row['id']) ? (int) $row['id'] : null;
            $record = $id ? $this->informe->{$relation}()->whereKey($id)->first() : null;
            $record ? $record->update($data) : $record = $this->informe->{$relation}()->create($data);
            $ids[] = $record->id;
            $this->setRowId($relation, $index, $record->id);
        }

        $query = $this->informe->{$relation}();
        $ids ? $query->whereNotIn('id', $ids)->delete() : $query->delete();
    }

    private function syncAnexos(): void
    {
        foreach ($this->anexos as $index => $row) {
            if (blank($row['nombre'] ?? null) && empty($this->anexoArchivos[$index])) {
                continue;
            }

            $file = $this->anexoArchivos[$index] ?? null;
            $tipo = ! empty($row['id']) && empty($row['origen_informe'])
                ? ($row['tipo_documento_original'] ?? $row['tipo_documento'] ?? 'otros')
                : ($row['tipo_documento'] ?? 'otros');
            $tipo = str_starts_with((string) $tipo, 'informe_final_') || (! empty($row['id']) && empty($row['origen_informe']))
                ? $tipo
                : 'informe_final_'.$tipo;
            $data = [
                'tipo_documento' => $tipo,
                'nombre' => $row['nombre'] ?: 'Anexo informe final',
                'descripcion' => $row['descripcion'] ?? null,
                'subido_por_usuario_id' => auth()->id(),
            ];

            if ($file) {
                if (! empty($row['ruta']) && $row['ruta'] !== 'pendiente') {
                    Storage::disk('public')->delete($row['ruta']);
                }

                $data['ruta'] = $file->store('enf/informes-finales/'.$this->accion->id.'/anexos', 'public');
                $data['mime_type'] = $file->getClientMimeType();
                $data['tamano_bytes'] = $file->getSize();
            } else {
                $data['ruta'] = $row['ruta'] ?? 'pendiente';
                $data['mime_type'] = $row['mime_type'] ?? null;
                $data['tamano_bytes'] = (int) ($row['tamano_bytes'] ?? 0);
            }

            $documento = ! empty($row['id'])
                ? $this->accion->documentos()->whereKey($row['id'])->first()
                : null;

            $documento ? $documento->update($data) : $this->accion->documentos()->create($data);
        }
    }

    private function rowHasContent(array $row, array $fields): bool
    {
        return collect(Arr::only($row, $fields))
            ->filter(fn ($value) => filled($value) && $value !== false)
            ->isNotEmpty();
    }

    private function setRowId(string $relation, int $index, int $id): void
    {
        $property = [
            'participantesFinales' => 'participantes',
            'accionesEjecutadas' => 'accionesEjecutadas',
            'accionesNoEjecutadas' => 'accionesNoEjecutadas',
        ][$relation] ?? null;

        if ($property && isset($this->{$property}[$index])) {
            $this->{$property}[$index]['id'] = $id;
        }
    }

    private function validateCurrentStep(): void
    {
        $rules = match ($this->currentStep) {
            1 => [
                'general.fecha_presentacion' => ['required', 'date'],
                'general.resumen_ejecutivo' => ['nullable', 'string'],
            ],
            2 => [
            ],
            3 => [
                'participantes.*.nombre_completo' => ['required', 'string', 'max:220'],
                'participantes.*.correo' => ['nullable', 'email', 'max:180'],
                'participantes.*.sexo' => ['nullable', Rule::in(['Masculino', 'Femenino', ''])],
                'participantes.*.edad' => ['nullable', 'integer', 'min:0'],
            ],
            4 => [
                'general.inscritos_hombres' => ['integer', 'min:0'],
                'general.inscritos_mujeres' => ['integer', 'min:0'],
                'general.aprobaron_hombres' => ['integer', 'min:0'],
                'general.aprobaron_mujeres' => ['integer', 'min:0'],
            ],
            5 => [
                'general.modalidad_acreditacion' => ['nullable', 'string', 'max:255'],
                'general.contenido_curricular_cambios' => ['nullable', 'string'],
                'general.cronograma_cambios' => ['nullable', 'string'],
                'general.seguimiento_sistematizacion' => ['nullable', 'string'],
            ],
            6 => [
                'general.resultados_obtenidos' => ['nullable', 'string'],
                'general.transformacion_lograda' => ['nullable', 'string'],
                'accionesEjecutadas.*.actividad' => ['required', 'string', 'max:250'],
                'accionesNoEjecutadas.*.actividad' => ['required', 'string', 'max:250'],
            ],
            7 => [
                'general.valoracion_muestra' => ['integer', 'min:0', 'lte:general.valoracion_total_beneficiarios'],
            ],
            8 => [
                'general.fecha_aprobacion' => ['nullable', 'date'],
                'general.confirmacion_veracidad' => ['accepted'],
                'anexos.*.nombre' => ['nullable', 'string', 'max:220'],
                'anexos.*.tipo_documento' => ['nullable', 'string', 'max:120'],
                'anexoArchivos.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
                'fotografiasTemporales.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            ],
            default => [],
        };

        if ($rules) {
            $this->validate($rules, $this->validationMessages(), $this->validationAttributes());
        }
    }

    private function validateAll(): void
    {
        foreach (range(1, self::TOTAL_STEPS) as $step) {
            $this->currentStep = $step;
            $this->validateCurrentStep();
        }
    }

    private function validationAttributes(): array
    {
        return [
            'general.fecha_presentacion' => 'fecha de elaboracion',
            'general.modalidad_acreditacion' => 'figura de acreditacion',
            'general.resumen_ejecutivo' => 'resumen ejecutivo',
            'general.resultados_obtenidos' => 'resultados obtenidos',
            'general.transformacion_lograda' => 'transformacion lograda',
            'general.valoracion_muestra' => 'muestra',
            'general.valoracion_total_beneficiarios' => 'total de beneficiarios',
            'general.confirmacion_veracidad' => 'confirmacion de veracidad',
            'participantes.*.nombre_completo' => 'nombre del participante',
            'participantes.*.correo' => 'correo del participante',
            'participantes.*.sexo' => 'sexo del participante',
            'participantes.*.edad' => 'edad del participante',
            'accionesEjecutadas.*.actividad' => 'actividad ejecutada',
            'accionesNoEjecutadas.*.actividad' => 'actividad no ejecutada',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'accepted' => 'Debe aceptar el campo :attribute.',
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'email' => 'El campo :attribute debe ser un correo electronico valido.',
            'integer' => 'El campo :attribute debe ser un numero entero.',
            'lte.numeric' => 'El campo :attribute debe ser menor o igual que :value.',
            'max.string' => 'El campo :attribute no debe tener mas de :max caracteres.',
            'min.numeric' => 'El campo :attribute debe ser al menos :min.',
            'min.string' => 'El campo :attribute debe tener al menos :min caracteres.',
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
        ];
    }

    private function draftRules(): array
    {
        return [
            'general.fecha_presentacion' => ['nullable', 'date'],
            'general.fecha_aprobacion' => ['nullable', 'date'],
            'general.*' => ['nullable'],
            'participantes.*.correo' => ['nullable', 'email', 'max:180'],
            'participantes.*.edad' => ['nullable', 'integer', 'min:0'],
            'accionesEjecutadas.*.fecha_inicio' => ['nullable', 'date'],
            'accionesEjecutadas.*.fecha_finalizacion' => ['nullable', 'date'],
            'accionesNoEjecutadas.*.fecha_reprogramacion' => ['nullable', 'date'],
            'anexos.*.nombre' => ['nullable', 'string', 'max:220'],
            'anexos.*.tipo_documento' => ['nullable', 'string', 'max:120'],
            'anexoArchivos.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
            'fotografiasTemporales.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    private function debeAutoguardar(string $propertyName): bool
    {
        foreach (['general.', 'participantes.', 'accionesEjecutadas.', 'accionesNoEjecutadas.', 'anexos.', 'anexoArchivos.', 'fotografiasTemporales.'] as $root) {
            if (str_starts_with($propertyName, $root)) {
                return true;
            }
        }

        return false;
    }

    private function generalFields(): array
    {
        return [
            'fecha_presentacion',
            'resumen_ejecutivo',
            'resultados_obtenidos',
            'limitaciones',
            'conclusiones',
            'recomendaciones',
            'inscritos_hombres',
            'inscritos_mujeres',
            'no_presentaron_hombres',
            'no_presentaron_mujeres',
            'abandonaron_hombres',
            'abandonaron_mujeres',
            'reprobaron_hombres',
            'reprobaron_mujeres',
            'aprobaron_hombres',
            'aprobaron_mujeres',
            'graduados_unah_hombres',
            'graduados_unah_mujeres',
            'contenido_curricular_cambios',
            'cronograma_cambios',
            'modalidad_acreditacion',
            'seguimiento_sistematizacion',
            'dificultades',
            'lecciones_aprendidas',
            'buenas_practicas',
            'transformacion_lograda',
            'desafios',
            'respuesta_reforma_universitaria',
            'valoracion_total_beneficiarios',
            'valoracion_muestra',
            'valoracion_excelente',
            'valoracion_muy_buena',
            'valoracion_regular',
            'valoracion_mala',
            'observaciones_finales',
            'confirmacion_veracidad',
            'estado',
        ];
    }
}
