<?php

namespace App\Livewire\Configuracion;

use App\Exceptions\Integraciones\IntegracionApiException;
use App\Models\IntegracionApi;
use App\Services\Integraciones\EstudianteApiService;
use App\Services\Integraciones\IntegracionApiConfigValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class IntegracionesApi extends Component
{
    public ?int $editingId = null;
    public ?int $testingId = null;
    public bool $showForm = false;
    public bool $showTest = false;
    public bool $showHistory = false;
    public string $testValue = '';
    public array $testResult = [];
    public array $historyRecords = [];
    public array $form = [];

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorizeAccess();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeAccess();
        $integration = IntegracionApi::findOrFail($id);
        $this->resetForm();
        $this->editingId = $integration->id;

        foreach ([
            'nombre',
            'codigo',
            'tipo_perfil',
            'base_url',
            'ruta_busqueda',
            'metodo_http',
            'tipo_autenticacion',
            'api_key_header',
            'api_key_ubicacion',
            'parametro_busqueda',
            'timeout_segundos',
            'reintentos',
            'verificar_ssl',
            'ruta_respuesta',
            'activo',
        ] as $field) {
            $this->form[$field] = $integration->{$field};
        }

        $this->form['headers_json'] = json_encode(
            $integration->headers_json ?: (object) [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $this->form['mapeo_campos_json'] = json_encode(
            $integration->mapeo_campos_json ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $this->form['tiene_secreto'] = $this->hasRequiredSecret($integration);
        $this->showForm = true;
    }

    public function save(
        IntegracionApiConfigValidator $configValidator,
        EstudianteApiService $apiService
    ): void {
        $this->authorizeAccess();
        $validated = $this->validateForm();

        try {
            $headers = $configValidator->decodeHeaders(
                $this->form['headers_json'],
                $this->form['tipo_autenticacion'],
                $this->form['api_key_header'] ?: null
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('form.headers_json', $exception->getMessage());

            return;
        }

        try {
            $mapping = $configValidator->decodeMapping($this->form['mapeo_campos_json']);
        } catch (InvalidArgumentException $exception) {
            $this->addError('form.mapeo_campos_json', $exception->getMessage());

            return;
        }

        try {
            $configValidator->assertSafeDataPath($this->form['ruta_respuesta']);
        } catch (InvalidArgumentException $exception) {
            $this->addError('form.ruta_respuesta', $exception->getMessage());

            return;
        }

        try {
            $apiService->validarUrlSegura($this->form['base_url']);
        } catch (IntegracionApiException $exception) {
            $this->addError('form.base_url', $exception->getMessage());

            return;
        }

        $integration = $this->editingId
            ? IntegracionApi::findOrFail($this->editingId)
            : new IntegracionApi(['created_by' => auth()->id()]);

        if (! $this->secretsAreComplete($integration)) {
            $this->addError(
                'form.tipo_autenticacion',
                'Debe configurar las credenciales requeridas para el tipo de autenticación.'
            );

            return;
        }

        $data = collect($validated['form'])->except([
            'token',
            'usuario_api',
            'password_api',
            'tiene_secreto',
        ])->all();
        $data['headers_json'] = $headers;
        $data['mapeo_campos_json'] = $mapping;
        $data['updated_by'] = auth()->id();

        if (filled($this->form['token'])) {
            $data['token_encriptado'] = $this->form['token'];
        }
        if (filled($this->form['usuario_api'])) {
            $data['usuario_api_encriptado'] = $this->form['usuario_api'];
        }
        if (filled($this->form['password_api'])) {
            $data['password_api_encriptado'] = $this->form['password_api'];
        }

        DB::transaction(function () use ($integration, $data): void {
            if ($data['activo']) {
                IntegracionApi::query()
                    ->where('tipo_perfil', $data['tipo_perfil'])
                    ->when(
                        $integration->exists,
                        fn ($query) => $query->where('id', '!=', $integration->id)
                    )
                    ->where('activo', true)
                    ->get()
                    ->each->update(['activo' => false, 'updated_by' => auth()->id()]);
            }

            $integration->fill($data)->save();
        });

        session()->flash('status', 'La integración API se guardó correctamente.');
        $this->closeForm();
    }

    public function toggle(int $id): void
    {
        $this->authorizeAccess();
        $integration = IntegracionApi::findOrFail($id);

        if (! $integration->activo && ! $this->configurationIsComplete($integration)) {
            $this->addError(
                'toggle',
                'La integración debe tener endpoint, mapeo y credenciales válidas antes de activarse.'
            );

            return;
        }

        DB::transaction(function () use ($integration): void {
            if (! $integration->activo) {
                IntegracionApi::query()
                    ->where('tipo_perfil', $integration->tipo_perfil)
                    ->where('id', '!=', $integration->id)
                    ->where('activo', true)
                    ->get()
                    ->each->update(['activo' => false, 'updated_by' => auth()->id()]);
            }

            $integration->update([
                'activo' => ! $integration->activo,
                'updated_by' => auth()->id(),
            ]);
        });

        session()->flash(
            'status',
            $integration->activo ? 'Integración activada.' : 'Integración desactivada.'
        );
    }

    public function openTest(int $id): void
    {
        $this->authorizeAccess();
        IntegracionApi::findOrFail($id);
        $this->testingId = $id;
        $this->testValue = '';
        $this->testResult = [];
        $this->showTest = true;
        $this->resetErrorBag('testValue');
    }

    public function testConnection(EstudianteApiService $service): void
    {
        $this->authorizeAccess();
        $this->validate(
            ['testValue' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/']],
            [
                'testValue.required' => 'Ingrese un número de cuenta para realizar la prueba.',
                'testValue.regex' => 'El número de cuenta contiene caracteres no válidos.',
            ]
        );

        $integration = IntegracionApi::findOrFail($this->testingId);
        $this->testResult = $service->probarConexion($integration, $this->testValue);
        $this->testValue = '';
    }

    public function viewHistory(int $id): void
    {
        $this->authorizeAccess();
        $integration = IntegracionApi::findOrFail($id);
        $this->historyRecords = Activity::query()
            ->where('subject_type', IntegracionApi::class)
            ->where('subject_id', $integration->id)
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (Activity $activity): array => [
                'descripcion' => $activity->description,
                'evento' => $activity->event,
                'fecha' => $activity->created_at?->format('d/m/Y H:i:s'),
                'usuario' => $activity->causer?->name ?? 'Sistema',
                'resultado' => data_get($activity->properties, 'resultado'),
            ])
            ->all();
        $this->showHistory = true;
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        $integration = IntegracionApi::findOrFail($id);

        abort_if($integration->protegida, 403, 'La integración protegida no puede eliminarse.');

        $integration->delete();
        session()->flash('status', 'La integración fue eliminada.');
    }

    public function closeForm(): void
    {
        $this->authorizeAccess();
        $this->showForm = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function closeTest(): void
    {
        $this->authorizeAccess();
        $this->showTest = false;
        $this->testingId = null;
        $this->testValue = '';
        $this->testResult = [];
    }

    public function closeHistory(): void
    {
        $this->authorizeAccess();
        $this->showHistory = false;
        $this->historyRecords = [];
    }

    public function render(): View
    {
        $this->authorizeAccess();

        return view('livewire.configuracion.integraciones-api', [
            'integraciones' => IntegracionApi::query()->orderBy('nombre')->get(),
            'camposPermitidos' => EstudianteApiService::CAMPOS_ESTUDIANTE,
        ]);
    }

    private function authorizeAccess(): void
    {
        Gate::authorize('configuracion.integraciones-api');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'nombre' => '',
            'codigo' => '',
            'tipo_perfil' => IntegracionApi::PERFIL_ESTUDIANTE,
            'base_url' => '',
            'ruta_busqueda' => '',
            'metodo_http' => 'GET',
            'tipo_autenticacion' => IntegracionApi::AUTH_NINGUNA,
            'token' => '',
            'usuario_api' => '',
            'password_api' => '',
            'api_key_header' => '',
            'api_key_ubicacion' => 'HEADER',
            'parametro_busqueda' => 'numero_cuenta',
            'timeout_segundos' => 15,
            'reintentos' => 0,
            'verificar_ssl' => true,
            'headers_json' => '{}',
            'ruta_respuesta' => '',
            'mapeo_campos_json' => json_encode([
                'numero_cuenta' => 'numeroCuenta',
                'nombre_completo' => 'nombreCompleto',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'activo' => false,
            'tiene_secreto' => false,
        ];
    }

    private function validateForm(): array
    {
        $rules = [
            'form.nombre' => ['required', 'string', 'max:255'],
            'form.codigo' => [
                'required',
                'alpha_dash:ascii',
                'max:100',
                Rule::unique('integraciones_api', 'codigo')->ignore($this->editingId),
            ],
            'form.tipo_perfil' => [
                'required',
                Rule::in([
                    IntegracionApi::PERFIL_ESTUDIANTE,
                    IntegracionApi::PERFIL_EMPLEADO,
                    IntegracionApi::PERFIL_EXTERNO,
                ]),
                Rule::unique('integraciones_api', 'tipo_perfil')
                    ->where(fn ($query) => $query->where('nombre', $this->form['nombre']))
                    ->ignore($this->editingId),
            ],
            'form.base_url' => ['required', 'url:http,https', 'max:2048'],
            'form.ruta_busqueda' => ['required', 'string', 'max:500'],
            'form.metodo_http' => ['required', Rule::in(['GET', 'POST'])],
            'form.tipo_autenticacion' => [
                'required',
                Rule::in([
                    IntegracionApi::AUTH_NINGUNA,
                    IntegracionApi::AUTH_BEARER,
                    IntegracionApi::AUTH_BASIC,
                    IntegracionApi::AUTH_API_KEY,
                ]),
            ],
            'form.token' => ['nullable', 'string', 'max:4096'],
            'form.usuario_api' => ['nullable', 'string', 'max:1024'],
            'form.password_api' => ['nullable', 'string', 'max:4096'],
            'form.api_key_header' => [
                Rule::requiredIf($this->form['tipo_autenticacion'] === IntegracionApi::AUTH_API_KEY),
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'form.api_key_ubicacion' => ['required', Rule::in(['HEADER', 'QUERY'])],
            'form.parametro_busqueda' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'form.timeout_segundos' => ['required', 'integer', 'between:1,60'],
            'form.reintentos' => ['required', 'integer', 'between:0,3'],
            'form.verificar_ssl' => ['boolean'],
            'form.headers_json' => ['required', 'string'],
            'form.ruta_respuesta' => ['nullable', 'string', 'max:500'],
            'form.mapeo_campos_json' => ['required', 'string'],
            'form.activo' => ['boolean'],
            'form.tiene_secreto' => ['boolean'],
        ];

        return $this->validate($rules, [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'El valor de :attribute ya está registrado.',
            'url' => 'La URL base debe ser una URL HTTP o HTTPS válida.',
            'between' => 'El campo :attribute debe estar entre :min y :max.',
            'regex' => 'El formato de :attribute no es válido.',
            'in' => 'El valor seleccionado para :attribute no es válido.',
        ], [
            'form.nombre' => 'nombre',
            'form.codigo' => 'slug',
            'form.tipo_perfil' => 'tipo de perfil',
            'form.base_url' => 'URL base',
            'form.ruta_busqueda' => 'ruta de búsqueda',
            'form.parametro_busqueda' => 'parámetro de consulta',
            'form.timeout_segundos' => 'timeout',
            'form.reintentos' => 'reintentos',
            'form.api_key_header' => 'nombre de API Key',
        ]);
    }

    private function secretsAreComplete(IntegracionApi $integration): bool
    {
        return match ($this->form['tipo_autenticacion']) {
            IntegracionApi::AUTH_NINGUNA => true,
            IntegracionApi::AUTH_BEARER,
            IntegracionApi::AUTH_API_KEY => filled($this->form['token'])
                || filled($integration->token_encriptado),
            IntegracionApi::AUTH_BASIC => (
                filled($this->form['usuario_api']) || filled($integration->usuario_api_encriptado)
            ) && (
                filled($this->form['password_api']) || filled($integration->password_api_encriptado)
            ),
            default => false,
        };
    }

    private function configurationIsComplete(IntegracionApi $integration): bool
    {
        return filled($integration->base_url)
            && filled($integration->ruta_busqueda)
            && filled($integration->parametro_busqueda)
            && is_array($integration->mapeo_campos_json)
            && (
                array_key_exists('numero_cuenta', $integration->mapeo_campos_json)
                || array_key_exists('nombre_completo', $integration->mapeo_campos_json)
            )
            && $this->hasRequiredSecret($integration);
    }

    private function hasRequiredSecret(IntegracionApi $integration): bool
    {
        return match ($integration->tipo_autenticacion) {
            IntegracionApi::AUTH_NINGUNA => true,
            IntegracionApi::AUTH_BEARER,
            IntegracionApi::AUTH_API_KEY => filled($integration->token_encriptado),
            IntegracionApi::AUTH_BASIC => filled($integration->usuario_api_encriptado)
                && filled($integration->password_api_encriptado),
            default => false,
        };
    }
}
