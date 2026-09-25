<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Asignatura;
use App\Models\Demografia\Pais;
use App\Models\JornadaLaboral;
use App\Models\Pasantia;
use App\Models\Personal\CategoriaEmpleado;
use App\Models\Personal\Empleado;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\Integraciones\IntegracionApiService;
use App\Services\Pasantias\PasantiaWorkflowService;
use App\Support\Notification;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreatePasantia extends Component
{
    use WithFileUploads;

    public ?int $registroId = null;

    public int $pasoActual = 1;

    public bool $modoEdicion = false;

    public bool $autoguardadoActivo = true;

    public bool $bloquearNavegacionPasos = true;

    public array $form = [];

    public bool $buscandoEstudiante = false;

    public string $busquedaAsignatura = '';

    public $cartaFormalizacionArchivo = null;

    public $convenioMarcoArchivo = null;

    // Envío al flujo, con el mismo patrón de confirmación utilizado por
    // FORM-DVUS-014.
    public bool $showEnviarModal = false;

    public int $modalStep = 1;

    public array $modalEtapas = [];

    public array $modalDestinatarios = [];

    public const OPCIONES_FORMULARIO = [
        'tipo_pasantia' => ['Nacional', 'Internacional'],
        'modalidad_ejecucion' => ['Presencial', '100% virtual (teletrabajo)', 'Híbrida (presencial + teletrabajo)'],
        'tipo_institucion' => ['Gobierno Nacional', 'Gobierno Municipal', 'ONG', 'Sociedad civil organizada', 'Sector Privado', 'Internacional'],
        'sector_institucion' => ['Agricultura, alimentación y silvicultura', 'Energía y minería', 'Producción', 'Sectores de servicios privados', 'Infraestructura, construcción y sectores relacionados', 'Educación e investigación', 'Servicios y función públicos', 'Transporte, transporte marítimo y aéreo'],
    ];

    public const PASOS = [
        1 => 'Estudiante', 2 => 'Información de la pasantía', 3 => 'Experiencia',
        4 => 'Institución', 5 => 'Contacto directo', 6 => 'Supervisor',
        7 => 'Firmas', 8 => 'Adjuntos',
    ];

    public function mount(?int $id = null): void
    {
        $this->inicializarFormulario();

        if ($id) {
            $registro = Pasantia::findOrFail($id);
            abort_unless($this->puedeVer($registro), 403);
            $this->registroId = $registro->id;
            $this->modoEdicion = true;
            $this->hidratarFormularioDesdeRegistro($registro);
        }
    }

    public function agregarAsignatura(int $id): void
    {
        $asignatura = Asignatura::query()->where('activa', true)->findOrFail($id);
        $asignaturas = $this->asignaturasSeleccionadas();
        if (! collect($asignaturas)->contains('codigo', $asignatura->codigo)) {
            $asignaturas[] = ['codigo' => $asignatura->codigo, 'nombre' => $asignatura->nombre];
        }
        $this->guardarAsignaturas($asignaturas);
    }

    public function quitarAsignatura(int $indice): void
    {
        $asignaturas = $this->asignaturasSeleccionadas();
        unset($asignaturas[$indice]);
        $this->guardarAsignaturas(array_values($asignaturas));
    }

    public function asignaturasSeleccionadas(): array
    {
        if (is_array($this->form['asignaturas'] ?? null)) {
            return $this->form['asignaturas'];
        }

        return filled($this->form['codigo_asignatura'] ?? null) || filled($this->form['nombre_asignatura'] ?? null)
            ? [['codigo' => $this->form['codigo_asignatura'], 'nombre' => $this->form['nombre_asignatura']]]
            : [];
    }

    protected function guardarAsignaturas(array $asignaturas): void
    {
        $this->form['asignaturas'] = $asignaturas;
        $this->form['codigo_asignatura'] = null;
        $this->form['nombre_asignatura'] = null;
        if ($this->autoguardadoActivo) {
            $this->guardarBorrador(false, 'asignaturas');
        }
    }

    public function updatedForm($value, $key): void
    {
        $campo = str_starts_with((string) $key, 'form.')
            ? substr((string) $key, 5)
            : (string) $key;

        if (! array_key_exists($campo, $this->form)) {
            return;
        }

        if (in_array($campo, ['pasantia_remunerada', 'monto_remuneracion'], true)
            && ! $this->campoEsSi($this->form['pasantia_remunerada'] ?? null)) {
            $this->form['monto_remuneracion'] = null;
            $this->resetErrorBag('form.monto_remuneracion');
        }

        $regla = $this->reglaParaCampo($campo);
        if ($regla !== null) {
            $atributo = 'form.'.$campo;
            $this->resetErrorBag($atributo);

            try {
                $this->validate([$atributo => $regla], [], $this->atributos());
            } catch (\Illuminate\Validation\ValidationException $exception) {
                foreach ($exception->errors() as $nombre => $mensajes) {
                    foreach ($mensajes as $mensaje) {
                        $this->addError($nombre, $mensaje);
                    }
                }
            }
        }

        if ($this->autoguardadoActivo) {
            $this->guardarBorrador(false, $campo);
        }
    }

    public function siguiente(): void
    {
        $this->resetErrorBag();
        $reglas = $this->reglasPasoCompleto($this->pasoActual);

        if ($reglas !== []) {
            $this->validate($reglas, [], $this->atributos());
        }

        if ($this->pasoActual === 8) {
            $this->abrirModalEnviar();

            return;
        }

        $this->guardarBorrador(false);

        $this->pasoActual = min(8, $this->pasoActual + 1);
    }

    public function abrirModalEnviar(): void
    {
        $this->resetErrorBag();

        // El último paso primero persiste el estado actual, igual que el
        // formulario 014, para que el modal siempre trabaje con el borrador
        // más reciente.
        $this->guardarBorrador(false);

        $registro = $this->registroId ? Pasantia::find($this->registroId) : null;
        if (! $registro) {
            $this->addError('flujo', 'Guarde el borrador antes de enviarlo a revisión.');

            return;
        }

        $faltantes = $registro->camposFaltantesParaEnvio();
        if ($faltantes !== []) {
            foreach ($faltantes as $faltante) {
                $this->addError('flujo', 'Complete '.$faltante.' antes de enviar a revisión.');
            }

            Notification::make()
                ->title('Formulario incompleto')
                ->body('Complete los campos obligatorios antes de enviar a revisión.')
                ->warning()
                ->send();

            return;
        }

        try {
            $this->modalEtapas = $this->obtenerEtapasParaDestinatario($registro);
        } catch (\RuntimeException $e) {
            $this->addError('flujo', $e->getMessage());
            Notification::make()
                ->title('Flujo de revisión no disponible')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('flujo', 'No se pudo cargar la configuración del flujo de revisión.');
            Notification::make()
                ->title('No se pudo preparar el envío')
                ->body('No se pudo cargar la configuración del flujo. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        $this->modalStep = 1;
        $this->modalDestinatarios = [];
        $this->showEnviarModal = true;
    }

    public function modalSiguiente(): void
    {
        $etapa = $this->modalEtapas[$this->modalStep - 1] ?? null;

        if ($etapa && empty($this->modalDestinatarios[$etapa['id']])) {
            $this->addError('modal_destinatario_'.$this->modalStep, 'Debe seleccionar un destinatario para esta etapa.');

            return;
        }

        $this->resetErrorBag('modal_destinatario_'.$this->modalStep);
        $this->modalStep++;
    }

    public function modalAnterior(): void
    {
        if ($this->modalStep > 1) {
            $this->modalStep--;
        }
    }

    public function cancelarModal(): void
    {
        $this->showEnviarModal = false;
        $this->modalStep = 1;
        $this->modalEtapas = [];
        $this->modalDestinatarios = [];
        $this->resetErrorBag();
    }

    public function confirmarEnvio(): void
    {
        $this->resetErrorBag();

        try {
            $registro = Pasantia::findOrFail($this->registroId);
            abort_unless($registro->perteneceAlUsuario(Auth::id()), 403);

            $registro->forceFill([
                'destinatarios_emisor' => $this->modalDestinatarios,
                'updated_by' => Auth::id(),
            ])->save();

            $registro = app(PasantiaWorkflowService::class)
                ->enviarARevision($registro, (int) Auth::id());
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Flujo de revisión no disponible')
                ->body($e->getMessage())
                ->warning()
                ->send();

            $this->showEnviarModal = false;

            return;
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('No se pudo enviar a revisión')
                ->body('No se pudo iniciar el flujo de revisión. Intente nuevamente.')
                ->danger()
                ->send();

            $this->showEnviarModal = false;

            return;
        }

        $this->showEnviarModal = false;
        $this->modalEtapas = [];
        $this->modalDestinatarios = [];

        Notification::make()
            ->title('Registro enviado')
            ->body('El FORM-DVUS-013 fue enviado a revisión correctamente.')
            ->success()
            ->send();

        $this->redirectRoute('pasantias.show', ['id' => $registro->id]);
    }

    public function buscarEstudiante(IntegracionApiService $integraciones): void
    {
        $this->resetErrorBag('form.numero_cuenta');
        $cuenta = preg_replace('/\s+/u', '', trim((string) ($this->form['numero_cuenta'] ?? '')));

        if ($cuenta === '' || ! ctype_digit($cuenta)) {
            $this->addError('form.numero_cuenta', 'Ingrese un número de cuenta válido.');

            return;
        }

        $this->buscandoEstudiante = true;

        try {
            $resultado = $integraciones->buscarEstudiantePorCuenta($cuenta);
            if (! ($resultado['ok'] ?? false)) {
                $this->addError('form.numero_cuenta', $resultado['mensaje'] ?? 'No se encontró el estudiante.');

                return;
            }

            $datos = $resultado['datos'] ?? [];
            $nombre = trim((string) ($datos['nombre_completo'] ?? ''));
            if ($nombre === '' || ctype_digit($nombre)) {
                $nombre = trim(implode(' ', array_filter([
                    $datos['nombres'] ?? null,
                    $datos['apellidos'] ?? null,
                ], fn ($valor) => filled($valor))));
            }

            $celular = $datos['celular'] ?? null;
            if (blank($celular) || str_contains((string) $celular, '@')) {
                $celular = $datos['telefono'] ?? null;
            }
            $correoInstitucional = $datos['correo_institucional'] ?? null;

            if ($this->valorLleno($nombre) && $this->nombreEstudianteInvalido($nombre)) {
                $this->addError('form.nombre_estudiante', 'La API devolvió un valor que no corresponde al nombre del estudiante.');
                $nombre = null;
            }

            if ($this->valorLleno($celular) && ! $this->telefonoHidratadoValido($celular)) {
                $this->addError('form.celular_estudiante', 'La API devolvió un número de celular inválido.');
                $celular = null;
            }

            if ($this->valorLleno($correoInstitucional)
                && ! filter_var($correoInstitucional, FILTER_VALIDATE_EMAIL)) {
                $this->addError('form.correo_institucional', 'La API devolvió un correo institucional inválido.');
                $correoInstitucional = null;
            }

            // Asignación deliberadamente explícita: una respuesta de API nunca
            // se copia completa al formulario ni se interpreta por posición.
            $cuentaRespuesta = preg_replace('/\s+/u', '', (string) ($datos['numero_cuenta'] ?? ''));
            if ($cuentaRespuesta !== '' && $cuentaRespuesta !== $cuenta) {
                $this->addError('form.numero_cuenta', 'La respuesta de la búsqueda no corresponde al número de cuenta consultado.');

                return;
            }

            $this->form['numero_cuenta'] = $cuenta;
            $this->form['nombre_estudiante'] = $nombre !== '' ? $nombre : null;
            $this->form['celular_estudiante'] = filled($celular) ? (string) $celular : null;
            $this->form['correo_institucional'] = filled($correoInstitucional) ? (string) $correoInstitucional : null;
            $this->form['carrera'] = $datos['carrera'] ?? $datos['carrera_nombre'] ?? $this->form['carrera'];
            $this->form['facultad_centro'] = $datos['centro_nombre'] ?? $this->form['facultad_centro'];
        } catch (\Throwable $e) {
            $this->addError('form.numero_cuenta', 'No fue posible consultar la integración de estudiantes.');
        } finally {
            $this->buscandoEstudiante = false;
        }
    }

    public function buscarDocente(): void
    {
        $this->resetErrorBag('form.numero_empleado_docente');
        $numero = preg_replace('/\s+/u', '', trim((string) ($this->form['numero_empleado_docente'] ?? '')));

        if ($numero === '' || ! ctype_digit($numero)) {
            $this->addError('form.numero_empleado_docente', 'Ingrese un número de empleado válido.');

            return;
        }

        $docente = Empleado::query()->with(['user', 'categoria', 'departamento_academico'])
            ->where('numero_empleado', $numero)->first();

        if (! $docente) {
            $this->addError('form.numero_empleado_docente', 'No se encontró un empleado con ese número.');

            return;
        }

        $this->form['numero_empleado_docente'] = $docente->numero_empleado;
        $this->form['nombre_docente_supervisor'] = $docente->nombre_completo;
        $this->form['celular_docente'] = $docente->celular;
        $this->form['correo_docente'] = $docente->user?->email;
        $this->form['categoria_docente'] = $docente->categoria?->nombre;
        $this->form['departamento_docente'] = $docente->departamento_academico?->nombre;
        $this->form['jornada_laboral_docente'] = $docente->jornada_laboral;
    }

    public function anterior(): void
    {
        $this->pasoActual = max(1, $this->pasoActual - 1);
    }

    public function irAPaso(int $paso): void
    {
        $paso = max(1, min(8, $paso));
        $this->resetErrorBag();

        if ($this->bloquearNavegacionPasos && $paso > $this->pasoActual) {
            $pasoIncompleto = $this->primerPasoIncompletoAntesDe($paso);

            if ($pasoIncompleto !== null) {
                $this->pasoActual = $pasoIncompleto;
                $this->validate($this->reglasPasoCompleto($pasoIncompleto), [], $this->atributos());
                $this->agregarErroresDeCompletitud($pasoIncompleto);

                return;
            }
        }

        $this->pasoActual = $paso;
    }

    public function isStepComplete(int $paso): bool
    {
        if (in_array($paso, [7, 8], true)) {
            return true;
        }

        foreach ($this->camposRequeridosDelPaso($paso) as $campo) {
            if (! $this->campoValido($campo)) {
                return false;
            }
        }

        if ($paso === 2 && $this->campoEsSi($this->form['otorga_creditos'] ?? null)
            && ! $this->campoValido('cantidad_creditos')) {
            return false;
        }

        if ($paso === 3 && $this->campoEsSi($this->form['pasantia_remunerada'] ?? null)
            && ! $this->campoValido('monto_remuneracion')) {
            return false;
        }

        return true;
    }

    public function canAccessStep(int $paso): bool
    {
        return ! $this->bloquearNavegacionPasos
            || $this->primerPasoIncompletoAntesDe($paso) === null;
    }

    protected function primerPasoIncompletoAntesDe(int $paso): ?int
    {
        $limite = min(max($paso, 1), count(self::PASOS));

        for ($indice = 1; $indice < $limite; $indice++) {
            if (! $this->isStepComplete($indice)) {
                return $indice;
            }
        }

        return null;
    }

    protected function agregarErroresDeCompletitud(int $paso): void
    {
        $mensajes = [
            'facultad_centro' => 'Seleccione la facultad o centro.',
            'escuela_departamento' => 'Ingrese la escuela o departamento académico.',
            'carrera' => 'Seleccione la carrera.',
            'numero_cuenta' => 'Ingrese el número de cuenta.',
            'nombre_estudiante' => 'Ingrese el nombre completo del estudiante.',
            'celular_estudiante' => 'Ingrese el número de celular del estudiante.',
            'correo_institucional' => 'Ingrese el correo institucional.',
            'tipo_pasantia' => 'Seleccione el tipo de pasantía.',
            'fecha_inicio' => 'Ingrese la fecha de inicio.',
            'fecha_finalizacion' => 'Ingrese la fecha de finalización.',
            'duracion_semanas' => 'Ingrese la duración en semanas.',
            'total_horas' => 'Ingrese el total de horas.',
            'horas_semanales' => 'Ingrese el promedio de horas semanales.',
            'pasantia_obligatoria' => 'Indique si la pasantía es obligatoria.',
            'otorga_creditos' => 'Indique si otorga créditos académicos.',
            'modalidad_ejecucion' => 'Seleccione la modalidad de ejecución.',
            'cantidad_creditos' => 'Ingrese la cantidad de créditos académicos.',
            'descripcion_experiencia' => 'Ingrese la descripción de la experiencia.',
            'descripcion_cargo' => 'Ingrese la descripción del cargo.',
            'resumen_responsabilidades' => 'Ingrese las responsabilidades y tareas.',
            'area_departamento' => 'Ingrese el área o departamento.',
            'area_conocimiento' => 'Ingrese el área de conocimiento.',
            'descripcion_conocimientos_teoricos' => 'Ingrese los conocimientos teóricos.',
            'habilidades_desarrollar' => 'Ingrese las habilidades por desarrollar.',
            'pasantia_remunerada' => 'Indique si la pasantía es remunerada.',
            'monto_remuneracion' => 'Ingrese el monto de la remuneración.',
            'nombre_institucion' => 'Ingrese el nombre de la institución.',
            'direccion_institucion' => 'Ingrese la dirección de la institución.',
            'ciudad_institucion' => 'Ingrese la ciudad de la institución.',
            'pais_institucion' => 'Ingrese el país de la institución.',
            'representante_legal' => 'Ingrese el representante legal.',
            'telefono_representante' => 'Ingrese el teléfono de la institución.',
            'correo_rrhh' => 'Ingrese el correo de recursos humanos.',
            'tipo_institucion' => 'Seleccione el tipo de institución.',
            'sector_institucion' => 'Seleccione el sector institucional.',
            'compromisos_institucion' => 'Ingrese los compromisos institucionales.',
            'nombre_contacto_directo' => 'Ingrese el nombre del contacto directo.',
            'celular_contacto_directo' => 'Ingrese el celular del contacto directo.',
            'correo_contacto_directo' => 'Ingrese el correo del contacto directo.',
            'cargo_contacto_directo' => 'Ingrese el cargo del contacto directo.',
            'grado_academico_contacto_directo' => 'Seleccione el grado académico.',
            'tipo_instrumento' => 'Seleccione el instrumento de formalización.',
            'nombre_docente_supervisor' => 'Ingrese el nombre del docente supervisor.',
            'numero_empleado_docente' => 'Ingrese el número de empleado del supervisor.',
            'celular_docente' => 'Ingrese el celular del supervisor.',
            'correo_docente' => 'Ingrese el correo del supervisor.',
            'categoria_docente' => 'Ingrese la categoría del supervisor.',
            'departamento_docente' => 'Ingrese el departamento del supervisor.',
            'jornada_laboral_docente' => 'Ingrese la jornada laboral del supervisor.',
            'ubicacion_cubiculo_docente' => 'Ingrese la ubicación del cubículo del supervisor.',
        ];

        $campos = $this->camposRequeridosDelPaso($paso);
        if ($paso === 2 && $this->campoEsSi($this->form['otorga_creditos'] ?? null)) {
            $campos[] = 'cantidad_creditos';
        }
        if ($paso === 3 && $this->campoEsSi($this->form['pasantia_remunerada'] ?? null)) {
            $campos[] = 'monto_remuneracion';
        }

        foreach ($campos as $campo) {
            if ($this->campoSinValor($this->form[$campo] ?? null)) {
                $this->addError('form.'.$campo, $mensajes[$campo] ?? 'Complete este campo.');
            }
        }
    }

    protected function camposRequeridosDelPaso(int $paso): array
    {
        return match ($paso) {
            1 => ['facultad_centro', 'escuela_departamento', 'carrera', 'numero_cuenta', 'nombre_estudiante', 'celular_estudiante', 'correo_institucional'],
            2 => ['tipo_pasantia', 'fecha_inicio', 'fecha_finalizacion', 'duracion_semanas', 'total_horas', 'horas_semanales', 'pasantia_obligatoria', 'otorga_creditos', 'modalidad_ejecucion'],
            3 => ['descripcion_experiencia', 'descripcion_cargo', 'resumen_responsabilidades', 'area_departamento', 'area_conocimiento', 'descripcion_conocimientos_teoricos', 'habilidades_desarrollar', 'pasantia_remunerada'],
            4 => ['nombre_institucion', 'direccion_institucion', 'ciudad_institucion', 'pais_institucion', 'representante_legal', 'telefono_representante', 'correo_rrhh', 'tipo_institucion', 'sector_institucion', 'compromisos_institucion'],
            5 => ['nombre_contacto_directo', 'celular_contacto_directo', 'correo_contacto_directo', 'cargo_contacto_directo', 'grado_academico_contacto_directo', 'tipo_instrumento'],
            6 => ['nombre_docente_supervisor', 'numero_empleado_docente', 'celular_docente', 'correo_docente', 'categoria_docente', 'departamento_docente', 'jornada_laboral_docente', 'ubicacion_cubiculo_docente'],
            default => [],
        };
    }

    protected function campoEsSi(mixed $valor): bool
    {
        return in_array(mb_strtolower(trim((string) $valor)), ['sí', 'si', '1', 'true'], true);
    }

    protected function campoSinValor(mixed $valor): bool
    {
        return $valor === null || (is_string($valor) && trim($valor) === '');
    }

    protected function campoValido(string $campo): bool
    {
        if ($this->campoSinValor($this->form[$campo] ?? null)) {
            return false;
        }

        $regla = $this->reglaParaCampo($campo);

        return $regla === null
            || validator(['form' => $this->form], ['form.'.$campo => $regla])->passes();
    }

    public function guardarBorrador(bool $notificar = true, ?string $campoModificado = null): void
    {
        // Un borrador puede estar incompleto, pero nunca debe persistir un
        // valor con formato inválido ni una respuesta cruzada entre campos.
        // El autoguardado de un campo inválido se maneja abajo excluyendo ese
        // campo; el guardado manual solo valida el paso visible.
        if ($campoModificado === null) {
            $reglas = $this->reglasPaso($this->pasoActual);
            if ($reglas !== []) {
                $this->validate($reglas, [], $this->atributos());
            }
        }
        $this->validarArchivosSeleccionados();

        $campos = $campoModificado !== null && array_key_exists($campoModificado, $this->form)
            ? [$campoModificado]
            : array_keys($this->form);

        if ($campoModificado !== null && $this->getErrorBag()->has('form.'.$campoModificado)) {
            $campos = [];
        }

        $payload = $this->normalizarPayload(array_intersect_key($this->form, array_flip($campos)));
        $payload['updated_by'] = Auth::id();

        if ($this->registroId) {
            $registro = Pasantia::findOrFail($this->registroId);
            abort_unless($this->puedeEditar($registro), 403);
            $registro->fill($payload)->save();
        } else {
            $payload['created_by'] = Auth::id();
            $payload['estado'] = 'borrador';
            $payload['proceso'] = Pasantia::PROCESO_FLUJO;
            $payload['tipo_accion_id'] = DB::table('vinculacion_tipos_accion')
                ->where('codigo', 'PASANTIAS')->where('activo', true)->value('id');
            $payload['flujo_aprobacion_id'] = DB::table('flujos_aprobacion')
                ->where('codigo', 'PASANTIAS_FORM_DVUS_013')
                ->where('proceso', Pasantia::PROCESO_FLUJO)
                ->where('codigo_formulario', Pasantia::FORMULARIO)
                ->where('activo', true)->value('id');
            $payload['codigo_registro'] = $this->generarCodigo();
            $registro = Pasantia::create($payload);
            $this->registroId = $registro->id;
            $this->modoEdicion = true;
        }

        $this->guardarArchivosSeleccionados($registro);

        if ($notificar) {
            Notification::make()->title('Borrador guardado')->body('La información de la pasantía se guardó correctamente.')->success()->send();
        }
    }

    protected function obtenerEtapasParaDestinatario(Pasantia $registro): array
    {
        return app(PasantiaWorkflowService::class)
            ->etapasQueRequierenDestinatario($registro)
            ->map(function (array $etapa): array {
                $usuarios = [];

                if ($etapa['rol_requerido']) {
                    $usuarios = User::query()
                        ->select(['id', 'name', 'email'])
                        ->whereHas('roles', fn ($query) => $query->where('roles.name', $etapa['rol_requerido']))
                        ->whereHas('empleado')
                        ->orderBy('name')
                        ->get()
                        ->filter(fn (User $usuario): bool => filled($usuario->email) && filter_var($usuario->email, FILTER_VALIDATE_EMAIL))
                        ->map(fn (User $usuario): array => [
                            'id' => $usuario->id,
                            'name' => $usuario->name,
                            'email' => $usuario->email,
                        ])
                        ->all();
                }

                return [
                    'id' => $etapa['id'],
                    'nombre' => $etapa['nombre'],
                    'rol_nombre' => $etapa['rol_requerido'] ?? 'Sin rol',
                    'usuarios' => $usuarios,
                ];
            })
            ->all();
    }

    public function render(): View
    {
        $facultadesCentros = FacultadCentro::query()->orderBy('nombre')->pluck('nombre', 'nombre');
        $facultadId = FacultadCentro::query()->where('nombre', $this->form['facultad_centro'] ?? '')->value('id');
        $carreras = $facultadId
            ? Carrera::query()
                ->where(function ($query) use ($facultadId) {
                    $query->where('facultad_centro_id', $facultadId)
                        ->orWhereHas('facultadCentros', fn ($q) => $q->where('centro_facultad.id', $facultadId));
                })->orderBy('nombre')->pluck('nombre', 'nombre')
            : collect();
        $categoriasDocente = CategoriaEmpleado::query()->orderBy('nombre')->pluck('nombre', 'nombre');
        $departamentosAcademicos = DepartamentoAcademico::query()->orderBy('nombre')->pluck('nombre', 'nombre');
        $jornadasLaborales = JornadaLaboral::query()->where('activo', true)->orderBy('orden')->orderBy('hora_inicio')->get()->pluck('etiqueta', 'etiqueta');

        return view('livewire.proyectos.vinculacion.create-pasantia', [
            'pasos' => self::PASOS,
            'facultadesCentros' => $facultadesCentros,
            'carreras' => $carreras,
            'categoriasDocente' => $categoriasDocente,
            'departamentosAcademicos' => $departamentosAcademicos,
            'jornadasLaborales' => $jornadasLaborales,
            'catalogoAsignaturas' => $this->pasoActual === 3
                ? Asignatura::query()->where('activa', true)
                    ->when(trim($this->busquedaAsignatura) !== '', fn ($query) => $query->where(fn ($q) => $q
                        ->where('codigo', 'like', '%'.trim($this->busquedaAsignatura).'%')
                        ->orWhere('nombre', 'like', '%'.trim($this->busquedaAsignatura).'%')))
                    ->orderBy('nombre')->limit(50)->get(['id', 'codigo', 'nombre'])
                : collect(),
            'paises' => Pais::query()->orderBy('nombre')->pluck('nombre', 'nombre'),
        ]);
    }

    protected function inicializarFormulario(): void
    {
        $this->form = array_fill_keys([
            'fecha_registro', 'facultad_centro', 'escuela_departamento', 'carrera', 'numero_cuenta', 'nombre_estudiante',
            'celular_estudiante', 'correo_institucional', 'correo_personal', 'tipo_pasantia', 'fecha_inicio',
            'fecha_finalizacion', 'duracion_semanas', 'total_horas', 'horas_semanales', 'pasantia_obligatoria',
            'otorga_creditos', 'cantidad_creditos', 'modalidad_ejecucion', 'descripcion_experiencia', 'descripcion_cargo',
            'resumen_responsabilidades', 'area_departamento', 'area_conocimiento', 'asignaturas', 'codigo_asignatura',
            'nombre_asignatura', 'descripcion_conocimientos_teoricos', 'habilidades_desarrollar', 'pasantia_remunerada',
            'monto_remuneracion', 'nombre_institucion', 'direccion_institucion', 'ciudad_institucion', 'pais_institucion',
            'representante_legal', 'telefono_representante', 'correo_rrhh', 'tipo_institucion', 'sector_institucion',
            'compromisos_institucion', 'nombre_contacto_directo', 'celular_contacto_directo', 'correo_contacto_directo',
            'cargo_contacto_directo', 'grado_academico_contacto_directo', 'tipo_instrumento', 'nombre_docente_supervisor',
            'numero_empleado_docente', 'celular_docente', 'correo_docente', 'categoria_docente', 'departamento_docente',
            'jornada_laboral_docente', 'ubicacion_cubiculo_docente', 'nombre_firma_coordinador', 'firma_coordinador',
            'nombre_firma_supervisor', 'firma_supervisor', 'nombre_firma_estudiante', 'firma_estudiante',
            'adjunta_carta_formalizacion', 'archivo_carta_formalizacion', 'adjunta_convenio_marco', 'archivo_convenio_marco',
        ], null);

        $this->form['fecha_registro'] = now()->format('Y-m-d');
    }

    protected function normalizarPayload(array $payload): array
    {
        if ((array_key_exists('pasantia_remunerada', $payload) || array_key_exists('monto_remuneracion', $payload))
            && ! $this->campoEsSi($this->form['pasantia_remunerada'] ?? null)) {
            $payload['monto_remuneracion'] = null;
        }

        if (array_key_exists('asignaturas', $payload) && is_array($payload['asignaturas'])) {
            $payload['codigo_asignatura'] = null;
            $payload['nombre_asignatura'] = null;
        }

        $camposNumericos = [
            'duracion_semanas',
            'total_horas',
            'horas_semanales',
            'cantidad_creditos',
            'monto_remuneracion',
        ];
        $camposFecha = ['fecha_registro', 'fecha_inicio', 'fecha_finalizacion'];

        $camposBooleanos = [
            'pasantia_obligatoria',
            'otorga_creditos',
            'pasantia_remunerada',
            'adjunta_carta_formalizacion',
            'adjunta_convenio_marco',
        ];

        foreach ($payload as $key => $value) {
            $regla = $this->reglaParaCampo($key);
            if ($regla !== null) {
                // Mantener el resto del formulario como contexto permite que
                // reglas relacionadas, como after_or_equal:form.fecha_inicio,
                // se evalúen con ambas fechas y no eliminen la fecha final al
                // guardar desde otro paso.
                $datosValidacion = ['form' => array_replace($this->form, $payload)];
                $validador = validator($datosValidacion, ['form.'.$key => $regla]);
                if ($validador->fails()) {
                    foreach ($validador->errors()->messages() as $nombre => $mensajes) {
                        foreach ($mensajes as $mensaje) {
                            if (! $this->getErrorBag()->has($nombre)) {
                                $this->addError($nombre, $mensaje);
                            }
                        }
                    }
                    unset($payload[$key]);

                    continue;
                }
            }

            if ($value === null || $value === '') {
                $payload[$key] = null;
            } elseif (in_array($key, $camposNumericos, true) && (! is_numeric($value) || (float) $value < 0)) {
                unset($payload[$key]);
                $this->addError('form.'.$key, 'Ingrese un valor numérico válido mayor o igual a cero.');
            } elseif (in_array($key, $camposFecha, true) && ! $this->esFechaFormularioValida($value)) {
                unset($payload[$key]);
                $this->addError('form.'.$key, 'Ingrese una fecha válida.');
            } elseif (in_array($key, $camposBooleanos, true)) {
                $payload[$key] = match (mb_strtolower(trim((string) $value))) {
                    'sí', 'si', '1', 'true' => true,
                    'no', '0', 'false' => false,
                    default => $value,
                };
            }
        }

        return $payload;
    }

    protected function validarArchivosSeleccionados(): void
    {
        $archivos = [];

        if ($this->cartaFormalizacionArchivo) {
            $archivos['cartaFormalizacionArchivo'] = ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'];
        }

        if ($this->convenioMarcoArchivo) {
            $archivos['convenioMarcoArchivo'] = ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'];
        }

        if ($archivos !== []) {
            $this->validate($archivos, [], [
                'cartaFormalizacionArchivo' => 'archivo de carta de formalización',
                'convenioMarcoArchivo' => 'archivo de convenio marco',
            ]);
        }
    }

    protected function guardarArchivosSeleccionados(Pasantia $registro): void
    {
        $payload = [];

        if ($this->cartaFormalizacionArchivo) {
            $payload['archivo_carta_formalizacion'] = $this->cartaFormalizacionArchivo
                ->store('pasantias/'.$registro->id.'/documentos', 'public');
            $payload['adjunta_carta_formalizacion'] = true;
            $this->form['archivo_carta_formalizacion'] = $payload['archivo_carta_formalizacion'];
            $this->form['adjunta_carta_formalizacion'] = 'Sí';
            $this->cartaFormalizacionArchivo = null;
        }

        if ($this->convenioMarcoArchivo) {
            $payload['archivo_convenio_marco'] = $this->convenioMarcoArchivo
                ->store('pasantias/'.$registro->id.'/documentos', 'public');
            $payload['adjunta_convenio_marco'] = true;
            $this->form['archivo_convenio_marco'] = $payload['archivo_convenio_marco'];
            $this->form['adjunta_convenio_marco'] = 'Sí';
            $this->convenioMarcoArchivo = null;
        }

        if ($payload !== []) {
            $registro->forceFill($payload)->save();
        }
    }

    protected function esFechaFormularioValida(mixed $valor): bool
    {
        if ($valor instanceof \DateTimeInterface) {
            return true;
        }

        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) $valor));

        return $fecha !== false && $fecha->format('Y-m-d') === trim((string) $valor);
    }

    protected function hidratarFormularioDesdeRegistro(Pasantia $registro): void
    {
        $datos = [
            'fecha_registro' => $registro->fecha_registro,
            'facultad_centro' => $registro->facultad_centro,
            'escuela_departamento' => $registro->escuela_departamento,
            'carrera' => $registro->carrera,
            'numero_cuenta' => $registro->numero_cuenta,
            'nombre_estudiante' => $registro->nombre_estudiante,
            'celular_estudiante' => $registro->celular_estudiante,
            'correo_institucional' => $registro->correo_institucional,
            'correo_personal' => $registro->correo_personal,
            'tipo_pasantia' => $registro->tipo_pasantia,
            'fecha_inicio' => $registro->fecha_inicio,
            'fecha_finalizacion' => $registro->fecha_finalizacion,
            'duracion_semanas' => $registro->duracion_semanas,
            'total_horas' => $registro->total_horas,
            'horas_semanales' => $registro->horas_semanales,
            'pasantia_obligatoria' => $registro->pasantia_obligatoria,
            'otorga_creditos' => $registro->otorga_creditos,
            'cantidad_creditos' => $registro->cantidad_creditos,
            'modalidad_ejecucion' => $registro->modalidad_ejecucion,
            'descripcion_experiencia' => $registro->descripcion_experiencia,
            'descripcion_cargo' => $registro->descripcion_cargo,
            'resumen_responsabilidades' => $registro->resumen_responsabilidades,
            'area_departamento' => $registro->area_departamento,
            'area_conocimiento' => $registro->area_conocimiento,
            'asignaturas' => $registro->asignaturas,
            'codigo_asignatura' => $registro->codigo_asignatura,
            'nombre_asignatura' => $registro->nombre_asignatura,
            'descripcion_conocimientos_teoricos' => $registro->descripcion_conocimientos_teoricos,
            'habilidades_desarrollar' => $registro->habilidades_desarrollar,
            'pasantia_remunerada' => $registro->pasantia_remunerada,
            'monto_remuneracion' => $registro->monto_remuneracion,
            'nombre_institucion' => $registro->nombre_institucion,
            'direccion_institucion' => $registro->direccion_institucion,
            'ciudad_institucion' => $registro->ciudad_institucion,
            'pais_institucion' => $registro->pais_institucion,
            'representante_legal' => $registro->representante_legal,
            'telefono_representante' => $registro->telefono_representante,
            'correo_rrhh' => $registro->correo_rrhh,
            'tipo_institucion' => $registro->tipo_institucion,
            'sector_institucion' => $registro->sector_institucion,
            'compromisos_institucion' => $registro->compromisos_institucion,
            'nombre_contacto_directo' => $registro->nombre_contacto_directo,
            'celular_contacto_directo' => $registro->celular_contacto_directo,
            'correo_contacto_directo' => $registro->correo_contacto_directo,
            'cargo_contacto_directo' => $registro->cargo_contacto_directo,
            'grado_academico_contacto_directo' => $registro->grado_academico_contacto_directo,
            'tipo_instrumento' => $registro->tipo_instrumento,
            'nombre_docente_supervisor' => $registro->nombre_docente_supervisor,
            'numero_empleado_docente' => $registro->numero_empleado_docente,
            'celular_docente' => $registro->celular_docente,
            'correo_docente' => $registro->correo_docente,
            'categoria_docente' => $registro->categoria_docente,
            'departamento_docente' => $registro->departamento_docente,
            'jornada_laboral_docente' => $registro->jornada_laboral_docente,
            'ubicacion_cubiculo_docente' => $registro->ubicacion_cubiculo_docente,
            'nombre_firma_coordinador' => $registro->nombre_firma_coordinador,
            'firma_coordinador' => $registro->firma_coordinador,
            'nombre_firma_supervisor' => $registro->nombre_firma_supervisor,
            'firma_supervisor' => $registro->firma_supervisor,
            'nombre_firma_estudiante' => $registro->nombre_firma_estudiante,
            'firma_estudiante' => $registro->firma_estudiante,
            'adjunta_carta_formalizacion' => $registro->adjunta_carta_formalizacion,
            'archivo_carta_formalizacion' => $registro->archivo_carta_formalizacion,
            'adjunta_convenio_marco' => $registro->adjunta_convenio_marco,
            'archivo_convenio_marco' => $registro->archivo_convenio_marco,
        ];

        $this->limpiarValoresIncompatiblesAlHidratar($registro, $datos);

        foreach (['fecha_registro', 'fecha_inicio', 'fecha_finalizacion'] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $datos[$campo] = $this->normalizarFechaFormulario($datos[$campo]);
            }
        }

        foreach (['pasantia_obligatoria', 'otorga_creditos', 'pasantia_remunerada', 'adjunta_carta_formalizacion', 'adjunta_convenio_marco'] as $campo) {
            if (array_key_exists($campo, $datos) && $datos[$campo] !== null) {
                $datos[$campo] = (bool) $datos[$campo] ? 'Sí' : 'No';
            }
        }

        $this->form = array_replace($this->form, $datos);
    }

    /**
     * Los borradores creados antes de corregir los bindings pueden contener
     * valores de otro campo. Se limpian únicamente los casos inequívocos; no
     * se intenta adivinar ni redistribuir datos por posición.
     */
    protected function limpiarValoresIncompatiblesAlHidratar(Pasantia $registro, array &$datos): void
    {
        $correcciones = [];

        if ($this->esFechaISO($datos['escuela_departamento'] ?? null)) {
            $datos['escuela_departamento'] = null;
            $correcciones['escuela_departamento'] = null;
            $this->addError('form.escuela_departamento', 'El valor cargado no corresponde a una escuela o departamento. Ingréselo nuevamente.');
        }

        if ($this->nombreEstudianteInvalido($datos['nombre_estudiante'] ?? null)) {
            $datos['nombre_estudiante'] = null;
            $correcciones['nombre_estudiante'] = null;
            $this->addError('form.nombre_estudiante', 'El valor cargado no corresponde al nombre del estudiante. Use Buscar o ingréselo nuevamente.');
        }

        if (! $this->telefonoHidratadoValido($datos['celular_estudiante'] ?? null)) {
            $datos['celular_estudiante'] = null;
            $correcciones['celular_estudiante'] = null;
            $this->addError('form.celular_estudiante', 'El valor cargado no corresponde a un número de celular válido. Ingréselo nuevamente.');
        }

        if ($this->valorLleno($datos['numero_cuenta'] ?? null)
            && ! ctype_digit(preg_replace('/\s+/u', '', (string) $datos['numero_cuenta']))) {
            $datos['numero_cuenta'] = null;
            $correcciones['numero_cuenta'] = null;
            $this->addError('form.numero_cuenta', 'El valor cargado no corresponde a un número de cuenta. Ingréselo nuevamente.');
        }

        if ($this->valorLleno($datos['correo_institucional'] ?? null)
            && ! filter_var($datos['correo_institucional'], FILTER_VALIDATE_EMAIL)) {
            $datos['correo_institucional'] = null;
            $correcciones['correo_institucional'] = null;
            $this->addError('form.correo_institucional', 'El valor cargado no corresponde a un correo institucional válido.');
        }

        if ($this->valorLleno($datos['correo_personal'] ?? null)
            && ! filter_var($datos['correo_personal'], FILTER_VALIDATE_EMAIL)) {
            $datos['correo_personal'] = null;
            $correcciones['correo_personal'] = null;
            $this->addError('form.correo_personal', 'El valor cargado no corresponde a un correo personal válido.');
        }

        if ($this->valorLleno($datos['nombre_asignatura'] ?? null)
            && ($this->esEtiquetaDeCampo((string) $datos['nombre_asignatura'])
                || filter_var($datos['nombre_asignatura'], FILTER_VALIDATE_EMAIL) !== false)) {
            $datos['nombre_asignatura'] = null;
            $correcciones['nombre_asignatura'] = null;
            $this->addError('form.nombre_asignatura', 'El valor cargado no corresponde al nombre de la asignatura. Ingréselo nuevamente.');
        }

        if ($this->valorLleno($datos['descripcion_experiencia'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['descripcion_experiencia'])) {
            $datos['descripcion_experiencia'] = null;
            $correcciones['descripcion_experiencia'] = null;
            $this->addError('form.descripcion_experiencia', 'El valor cargado es una etiqueta del formulario. Ingrese la descripción de la experiencia.');
        }

        if ($this->valorLleno($datos['descripcion_cargo'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['descripcion_cargo'])) {
            $datos['descripcion_cargo'] = null;
            $correcciones['descripcion_cargo'] = null;
            $this->addError('form.descripcion_cargo', 'El valor cargado es una etiqueta del formulario. Ingrese la descripción del cargo.');
        }

        if ($this->valorLleno($datos['resumen_responsabilidades'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['resumen_responsabilidades'])) {
            $datos['resumen_responsabilidades'] = null;
            $correcciones['resumen_responsabilidades'] = null;
            $this->addError('form.resumen_responsabilidades', 'El valor cargado es una etiqueta del formulario. Ingrese las responsabilidades y tareas.');
        }

        if ($this->valorLleno($datos['descripcion_conocimientos_teoricos'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['descripcion_conocimientos_teoricos'])) {
            $datos['descripcion_conocimientos_teoricos'] = null;
            $correcciones['descripcion_conocimientos_teoricos'] = null;
            $this->addError('form.descripcion_conocimientos_teoricos', 'El valor cargado es una etiqueta del formulario. Ingrese los conocimientos teóricos.');
        }

        if ($this->valorLleno($datos['habilidades_desarrollar'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['habilidades_desarrollar'])) {
            $datos['habilidades_desarrollar'] = null;
            $correcciones['habilidades_desarrollar'] = null;
            $this->addError('form.habilidades_desarrollar', 'El valor cargado es una etiqueta del formulario. Ingrese las habilidades por desarrollar.');
        }

        if ($this->valorLleno($datos['area_departamento'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['area_departamento'])) {
            $datos['area_departamento'] = null;
            $correcciones['area_departamento'] = null;
            $this->addError('form.area_departamento', 'El valor cargado es una etiqueta del formulario. Ingrese el área o departamento.');
        }

        if ($this->valorLleno($datos['area_conocimiento'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['area_conocimiento'])) {
            $datos['area_conocimiento'] = null;
            $correcciones['area_conocimiento'] = null;
            $this->addError('form.area_conocimiento', 'El valor cargado es una etiqueta del formulario. Ingrese el área de conocimiento.');
        }

        if (! $this->enteroHidratadoValido($datos['duracion_semanas'] ?? null, 0, 520)) {
            $datos['duracion_semanas'] = null;
            $correcciones['duracion_semanas'] = null;
            $this->addError('form.duracion_semanas', 'El valor cargado no corresponde a una duración válida. Ingrésela nuevamente.');
        }

        if (! $this->enteroHidratadoValido($datos['total_horas'] ?? null, 0, 10000)) {
            $datos['total_horas'] = null;
            $correcciones['total_horas'] = null;
            $this->addError('form.total_horas', 'El valor cargado no corresponde a un total de horas válido. Ingréselo nuevamente.');
        }

        if (! $this->enteroHidratadoValido($datos['horas_semanales'] ?? null, 0, 168)) {
            $datos['horas_semanales'] = null;
            $correcciones['horas_semanales'] = null;
            $this->addError('form.horas_semanales', 'El valor cargado no corresponde a horas semanales válidas. Ingréselas nuevamente.');
        }

        if ($this->valorLleno($datos['nombre_institucion'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['nombre_institucion'])) {
            $datos['nombre_institucion'] = null;
            $correcciones['nombre_institucion'] = null;
            $this->addError('form.nombre_institucion', 'El valor cargado es una etiqueta del formulario. Ingrese el nombre de la institución.');
        }

        if ($this->valorLleno($datos['direccion_institucion'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['direccion_institucion'])) {
            $datos['direccion_institucion'] = null;
            $correcciones['direccion_institucion'] = null;
            $this->addError('form.direccion_institucion', 'El valor cargado es una etiqueta del formulario. Ingrese la dirección de la institución.');
        }

        if ($this->valorLleno($datos['ciudad_institucion'] ?? null)
            && filter_var($datos['ciudad_institucion'], FILTER_VALIDATE_EMAIL) !== false) {
            $datos['ciudad_institucion'] = null;
            $correcciones['ciudad_institucion'] = null;
            $this->addError('form.ciudad_institucion', 'El valor cargado no corresponde a una ciudad. Ingrésela nuevamente.');
        }

        if ($this->valorLleno($datos['representante_legal'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['representante_legal'])) {
            $datos['representante_legal'] = null;
            $correcciones['representante_legal'] = null;
            $this->addError('form.representante_legal', 'El valor cargado es una etiqueta del formulario. Ingrese el representante legal.');
        }

        if ($this->valorLleno($datos['compromisos_institucion'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['compromisos_institucion'])) {
            $datos['compromisos_institucion'] = null;
            $correcciones['compromisos_institucion'] = null;
            $this->addError('form.compromisos_institucion', 'El valor cargado es una etiqueta del formulario. Ingrese los compromisos institucionales.');
        }

        if ($this->valorLleno($datos['nombre_contacto_directo'] ?? null)
            && $this->esEtiquetaDeCampo((string) $datos['nombre_contacto_directo'])) {
            $datos['nombre_contacto_directo'] = null;
            $correcciones['nombre_contacto_directo'] = null;
            $this->addError('form.nombre_contacto_directo', 'El valor cargado es una etiqueta del formulario. Ingrese el nombre del contacto.');
        }

        if ($this->valorLleno($datos['telefono_representante'] ?? null)
            && ! $this->telefonoHidratadoValido($datos['telefono_representante'])) {
            $datos['telefono_representante'] = null;
            $correcciones['telefono_representante'] = null;
            $this->addError('form.telefono_representante', 'El valor cargado no corresponde a un teléfono válido. Ingréselo nuevamente.');
        }

        if ($this->valorLleno($datos['celular_contacto_directo'] ?? null)
            && ! $this->telefonoHidratadoValido($datos['celular_contacto_directo'])) {
            $datos['celular_contacto_directo'] = null;
            $correcciones['celular_contacto_directo'] = null;
            $this->addError('form.celular_contacto_directo', 'El valor cargado no corresponde a un celular válido. Ingréselo nuevamente.');
        }

        if ($this->valorLleno($datos['celular_docente'] ?? null)
            && ! $this->telefonoHidratadoValido($datos['celular_docente'])) {
            $datos['celular_docente'] = null;
            $correcciones['celular_docente'] = null;
            $this->addError('form.celular_docente', 'El valor cargado no corresponde a un celular válido. Ingréselo nuevamente.');
        }

        if ($correcciones !== [] && in_array($registro->estado, ['borrador', 'subsanacion'], true)) {
            $registro->forceFill($correcciones)->saveQuietly();
        }
    }

    protected function esFechaISO(mixed $valor): bool
    {
        return is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($valor)) === 1;
    }

    protected function valorLleno(mixed $valor): bool
    {
        return $valor !== null && (! is_string($valor) || trim($valor) !== '');
    }

    protected function esEtiquetaDeCampo(string $valor): bool
    {
        $normalizado = mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($valor)));

        return in_array($normalizado, [
            'codigo de asignatura',
            'código de asignatura',
            'nombre de asignatura',
            'nombre completo del estudiante',
            'numero de cuenta',
            'número de cuenta',
            'descripción de la experiencia y resultados',
            'dirección de la sede principal',
            'responsabilidades y tareas',
            'conocimientos teóricos',
            'habilidades a desarrollar',
            'nombre de la institución / organización',
            'nombre completo',
            'ciudad',
            'país',
            'representante legal',
            'número de teléfono',
            'correo de recursos humanos',
            'nombre del contacto directo',
            'cargo',
        ], true);
    }

    protected function nombreEstudianteInvalido(mixed $valor): bool
    {
        if (! $this->valorLleno($valor)) {
            return false;
        }

        $texto = trim((string) $valor);

        return $this->esEtiquetaDeCampo($texto)
            || filter_var($texto, FILTER_VALIDATE_EMAIL) !== false
            || preg_match('/^[\pL\s.\'-]+$/u', $texto) !== 1;
    }

    protected function telefonoHidratadoValido(mixed $valor): bool
    {
        if (! $this->valorLleno($valor)) {
            return true;
        }

        $texto = trim((string) $valor);
        $digitos = preg_replace('/\D+/u', '', $texto);

        return preg_match('/^[0-9+()\s.-]+$/', $texto) === 1
            && strlen((string) $digitos) >= 8;
    }

    protected function enteroHidratadoValido(mixed $valor, int $minimo, int $maximo): bool
    {
        if (! $this->valorLleno($valor)) {
            return true;
        }

        return filter_var($valor, FILTER_VALIDATE_INT) !== false
            && (int) $valor >= $minimo
            && (int) $valor <= $maximo;
    }

    protected function reglaTextoNoEsFecha(): Closure
    {
        return static function ($attribute, $value, $fail): void {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value)) === 1) {
                $fail('Este campo no puede contener una fecha.');
            }
        };
    }

    protected function reglaNombreEstudiante(): Closure
    {
        return function ($attribute, $value, $fail): void {
            if (is_string($value) && $this->esEtiquetaDeCampo($value)) {
                $fail('Ingrese el nombre del estudiante, no la etiqueta de otro campo.');
            }
        };
    }

    protected function reglaTextoNoEtiquetaNiCorreo(): Closure
    {
        return function ($attribute, $value, $fail): void {
            if (! is_string($value)) {
                return;
            }

            if ($this->esEtiquetaDeCampo($value)) {
                $fail('Ingrese el contenido del campo, no una etiqueta del formulario.');
            } elseif (filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false) {
                $fail('Este campo no puede contener un correo electrónico.');
            }
        };
    }

    protected function normalizarFechaFormulario(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        return $this->esFechaFormularioValida($valor) ? trim((string) $valor) : null;
    }

    protected function reglasPasoCompleto(int $paso): array
    {
        $reglas = $this->reglasPaso($paso);
        foreach ($reglas as $atributo => &$regla) {
            $campo = str_replace('form.', '', $atributo);
            if ($this->campoObligatorio($campo, $paso)) {
                $regla = array_values(array_filter($regla, fn ($item) => $item !== 'nullable'));
                array_unshift($regla, 'required');
            }
        }

        return $reglas;
    }

    public function campoObligatorio(string $campo, int $paso): bool
    {
        return in_array($campo, $this->camposRequeridosDelPaso($paso), true)
            || ($campo === 'cantidad_creditos' && $this->campoEsSi($this->form['otorga_creditos'] ?? null))
            || ($campo === 'monto_remuneracion' && $this->campoEsSi($this->form['pasantia_remunerada'] ?? null));
    }

    protected function reglasPaso(int $paso): array
    {
        return match ($paso) {
            1 => [
                'form.fecha_registro' => ['nullable', 'date'],
                'form.facultad_centro' => ['nullable', 'string', 'max:255'],
                'form.escuela_departamento' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEsFecha()],
                'form.carrera' => ['nullable', 'string', 'max:255'],
                'form.numero_cuenta' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
                'form.nombre_estudiante' => ['nullable', 'string', 'max:255', 'regex:/^[\pL\s.\'-]+$/u', $this->reglaNombreEstudiante()],
                'form.celular_estudiante' => ['nullable', 'string', 'min:8', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
                'form.correo_institucional' => ['nullable', 'email', 'max:255'],
                'form.correo_personal' => ['nullable', 'email', 'max:255'],
            ],
            2 => [
                'form.tipo_pasantia' => ['nullable', 'string', Rule::in(array_unique(array_merge(self::OPCIONES_FORMULARIO['tipo_pasantia'], ['Nacional', 'Internacional', 'Pasantía profesional', 'Pasantía académica'])))],
                'form.fecha_inicio' => ['nullable', 'date'],
                'form.fecha_finalizacion' => ['nullable', 'date', 'after_or_equal:form.fecha_inicio'],
                'form.duracion_semanas' => ['nullable', 'integer', 'min:0', 'max:520'],
                'form.total_horas' => ['nullable', 'integer', 'min:0', 'max:10000'],
                'form.horas_semanales' => ['nullable', 'integer', 'min:0', 'max:168'],
                'form.cantidad_creditos' => ['nullable', 'numeric', 'min:0'],
                'form.modalidad_ejecucion' => ['nullable', 'string', Rule::in(array_unique(array_merge(self::OPCIONES_FORMULARIO['modalidad_ejecucion'], ['Presencial', '100% virtual (teletrabajo)', 'Híbrida (presencial + teletrabajo)', '100% presencial', 'Híbrida', 'Teletrabajo'])))],
                'form.pasantia_obligatoria' => ['nullable', Rule::in(['Sí', 'No'])],
                'form.otorga_creditos' => ['nullable', Rule::in(['Sí', 'No'])],
            ],
            3 => [
                'form.descripcion_experiencia' => ['nullable', 'string', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.descripcion_cargo' => ['nullable', 'string', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.resumen_responsabilidades' => ['nullable', 'string', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.area_departamento' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.area_conocimiento' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.asignaturas' => ['nullable', 'array'],
                'form.asignaturas.*.codigo' => ['nullable', 'string', 'max:100'],
                'form.asignaturas.*.nombre' => ['required', 'string', 'max:255'],
                'form.codigo_asignatura' => ['nullable', 'string', 'max:100'],
                'form.nombre_asignatura' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.descripcion_conocimientos_teoricos' => ['nullable', 'string', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.habilidades_desarrollar' => ['nullable', 'string', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.pasantia_remunerada' => ['nullable', Rule::in(['Sí', 'No'])],
                'form.monto_remuneracion' => ['nullable', 'numeric', 'min:0'],
            ],
            4 => [
                'form.nombre_institucion' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.direccion_institucion' => ['nullable', 'string', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.ciudad_institucion' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.pais_institucion' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.representante_legal' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.telefono_representante' => ['nullable', 'string', 'min:8', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
                'form.correo_rrhh' => ['nullable', 'email', 'max:255'],
                'form.tipo_institucion' => ['nullable', Rule::in(array_unique(array_merge(self::OPCIONES_FORMULARIO['tipo_institucion'], ['Pública', 'Privada', 'ONG', 'Organismo internacional'])))],
                'form.sector_institucion' => ['nullable', Rule::in(array_unique(array_merge(self::OPCIONES_FORMULARIO['sector_institucion'], ['Educación', 'Gobierno', 'Empresa privada', 'Sociedad civil'])))],
                'form.compromisos_institucion' => ['nullable', 'string', $this->reglaTextoNoEtiquetaNiCorreo()],
            ],
            5 => [
                'form.nombre_contacto_directo' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.celular_contacto_directo' => ['nullable', 'string', 'min:8', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
                'form.correo_contacto_directo' => ['nullable', 'email', 'max:255'],
                'form.cargo_contacto_directo' => ['nullable', 'string', 'max:255', $this->reglaTextoNoEtiquetaNiCorreo()],
                'form.grado_academico_contacto_directo' => ['nullable', Rule::in(['Secundaria completa', 'Licenciatura', 'Maestría', 'Doctorado', 'Postdoctorado'])],
                'form.tipo_instrumento' => ['nullable', Rule::in(['carta_formal_solicitud', 'carta_intenciones', 'convenio_marco'])],
            ],
            6 => [
                'form.nombre_docente_supervisor' => ['nullable', 'string', 'max:255'],
                'form.numero_empleado_docente' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
                'form.celular_docente' => ['nullable', 'string', 'min:8', 'max:30', 'regex:/^[0-9+()\s.-]+$/'],
                'form.correo_docente' => ['nullable', 'email', 'max:255'],
                'form.categoria_docente' => ['nullable', 'string', 'max:255'],
                'form.departamento_docente' => ['nullable', 'string', 'max:255'],
                'form.jornada_laboral_docente' => ['nullable', 'string', 'max:255'],
                'form.ubicacion_cubiculo_docente' => ['nullable', 'string', 'max:255'],
            ],
            7 => [],
            8 => [
                'form.adjunta_carta_formalizacion' => ['nullable', Rule::in(['Sí', 'No'])],
                'form.adjunta_convenio_marco' => ['nullable', Rule::in(['Sí', 'No'])],
                'cartaFormalizacionArchivo' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
                'convenioMarcoArchivo' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            ],
            default => [],
        };
    }

    protected function reglaParaCampo(string $campo): ?array
    {
        $clave = 'form.'.$campo;

        foreach (array_keys(self::PASOS) as $paso) {
            $reglas = $this->reglasPaso($paso);
            if (array_key_exists($clave, $reglas)) {
                return $reglas[$clave];
            }
        }

        return null;
    }

    protected function atributos(): array
    {
        return [
            'form.fecha_registro' => 'fecha de registro', 'form.fecha_inicio' => 'fecha de inicio',
            'form.fecha_finalizacion' => 'fecha de finalización', 'form.total_horas' => 'total de horas',
            'form.correo_institucional' => 'correo institucional', 'form.correo_personal' => 'correo personal',
            'form.correo_rrhh' => 'correo de recursos humanos', 'form.correo_contacto_directo' => 'correo del contacto directo',
            'form.correo_docente' => 'correo del docente supervisor',
        ];
    }

    protected function puedeVer(Pasantia $registro): bool
    {
        return $registro->created_by === Auth::id()
            || Auth::user()?->can('proyectos.historial')
            || Auth::user()?->can('docente.proyectos')
            || $registro->usuarioPuedeRevisar(Auth::user());
    }

    protected function puedeEditar(Pasantia $registro): bool
    {
        if (in_array($registro->estado, ['borrador', 'subsanacion'], true)) {
            return $registro->created_by === Auth::id();
        }

        return $registro->estado === 'en_revision' && $registro->usuarioPuedeRevisar(Auth::user());
    }

    protected function generarCodigo(): string
    {
        do {
            $codigo = 'PAS-'.now()->format('Y').'-'.str()->upper(str()->random(6));
        } while (Pasantia::where('codigo_registro', $codigo)->exists());

        return $codigo;
    }
}
