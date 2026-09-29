<div>
    @php
        $stepLabels = $this::PASOS;

        $inputClass = 'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
        $readonlyClass = 'w-full rounded-md border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/60 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 cursor-not-allowed';
        $labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
        $hintClass = 'mt-1 text-xs text-gray-500 dark:text-gray-400';
        $errorClass = 'text-red-500 text-xs mt-1';
        $sectionTitle = 'text-sm font-semibold text-gray-900 dark:text-white';
        $asterisco = '<span class="text-red-500">*</span>';
        $modoEdicion = $modoEdicion ?? false;
        $registroEdicion = $registroEdicion ?? null;
        $esRevisor = method_exists($this, 'esEdicionRevisor') && $this->esEdicionRevisor();
        $nacional = $territorio_ejecucion !== 'Internacional';
        $aplicaPresencial = $this->aplicaPresencial();
        $aplicaTeletrabajo = $this->aplicaTeletrabajo();
        $modalidadElegida = $this->modalidadClave();
        $usaCatalogoDepartamentos = $this->usaCatalogoDepartamentos();
        $usaCatalogoDepartamentosSede = $this->usaCatalogoDepartamentosSede();
    @endphp

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">FORM-DVUS-014</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
            {{ $modoEdicion ? 'Editar Registro de Práctica Profesional Supervisada o Servicio Social' : 'Registro de Práctica Profesional Supervisada o Servicio Social' }}
        </h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ $modoEdicion ? 'Actualice la información del borrador antes de enviarlo a revisión.' : 'Complete la información del FORM-DVUS-014 para guardarla como borrador.' }}
        </p>
    </div>

    @if($modoEdicion && $registroEdicion && filled($registroEdicion->motivo_rechazo))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800 shadow-sm dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-200">
            <p class="font-semibold">Este registro fue rechazado anteriormente.</p>
            <p class="mt-1">Corrija las observaciones indicadas antes de reenviarlo a revisión.</p>

            @if($registroEdicion->fecha_revision)
                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">
                    Fecha de revisión: {{ $registroEdicion->fecha_revision->format('d/m/Y H:i') }}
                </p>
            @endif

            <div class="mt-3 rounded-lg border border-red-200 bg-white/70 p-3 dark:border-red-900/60 dark:bg-gray-900/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">Motivo de rechazo</p>
                <p class="mt-1 whitespace-pre-line text-sm">{{ $registroEdicion->motivo_rechazo }}</p>
            </div>
        </div>
    @endif

    @if(method_exists($this, 'esEdicionRevisor') && $this->esEdicionRevisor())
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900 shadow-sm dark:border-blue-900/70 dark:bg-blue-950/30 dark:text-blue-100">
            <p class="mb-3 font-semibold">Edición en etapa: {{ $registroEdicion?->etapaActual?->nombre ?? 'Etapa actual del flujo' }}</p>
            <label for="comentarioRevisor" class="block font-semibold">Comentario de revisión <span class="text-red-600">*</span></label>
            <textarea id="comentarioRevisor" wire:model="comentarioRevisor" rows="3" required
                      class="mt-2 w-full rounded-md border border-blue-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-blue-800 dark:bg-gray-900 dark:text-white"
                      placeholder="Explique los cambios realizados durante la revisión"></textarea>
            @error('comentarioRevisor') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif


    <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-900">
        <div class="flex items-center overflow-x-auto gap-0.5">
            @foreach($stepLabels as $step => $label)
                @php
                    $accessible = $this->canAccessStep($step);
                    $showComplete = $this->shouldShowStepComplete($step);
                @endphp
                <button wire:click="goToStep({{ $step }})" type="button"
                    aria-disabled="{{ $accessible ? 'false' : 'true' }}"
                    class="group flex min-w-[56px] flex-1 flex-col items-center rounded-md p-1 transition hover:bg-gray-50 dark:hover:bg-white/5 {{ $accessible ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                    <span class="mb-1 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold transition-colors
                        {{ $currentStep === $step
                            ? 'bg-blue-600 text-white ring-2 ring-blue-300'
                            : ($showComplete
                                ? 'bg-green-500 text-white'
                                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">
                        @if($showComplete)
                            &#10003;
                        @else
                            {{ $step }}
                        @endif
                    </span>
                    <span class="hidden text-center text-[11px] leading-tight sm:block
                        {{ $currentStep === $step ? 'font-semibold text-blue-600' : ($showComplete ? 'text-green-600 dark:text-green-400' : 'text-gray-500') }}">
                        {{ $label }}
                    </span>
                </button>

                @if($step < count($stepLabels))
                    <div class="h-0.5 w-4 shrink-0 {{ $showComplete ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endif
            @endforeach
        </div>

        <div class="mt-3 flex min-h-5 justify-end text-xs font-medium">
            @if($estadoAutoGuardado === 'guardando')
                <span class="text-gray-500 dark:text-gray-400">Guardando...</span>
            @elseif($estadoAutoGuardado === 'guardado')
                <span class="text-green-600 dark:text-green-400">Guardado</span>
            @elseif($estadoAutoGuardado === 'error')
                <span class="text-red-600 dark:text-red-400">Error al guardar</span>
            @endif
        </div>
    </div>

    <form wire:submit.prevent="guardar" wire:input.debounce.1500ms="autoGuardarBorrador" wire:change="autoGuardarBorrador" class="rounded-lg bg-white p-6 shadow dark:bg-gray-900">

        {{-- ══════════════ PASO 1: Estudiante y práctica (I, II, ítems 9 y 10) ══════════════ --}}
        @if($currentStep === 1)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 1: Estudiante y práctica</h2>
            <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">Unidad académica, tipo de práctica y datos del estudiante.</p>

            <div class="space-y-8">
                <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label class="{{ $labelClass }}">Facultad / Centro Universitario Regional / Instituto Tecnológico {!! $asterisco !!}</label>
                        <select wire:model.live="facultad_centro_id" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($facultadesCentros as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('facultad_centro_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $labelClass }}">Carrera {!! $asterisco !!}</label>
                        <select wire:model="carrera_id" class="{{ $inputClass }}" @disabled(!$facultad_centro_id || $carreras->isEmpty())>
                            <option value="">{{ !$facultad_centro_id ? 'Seleccione primero una facultad o centro' : 'Seleccione...' }}</option>
                            @foreach($carreras as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @if($facultad_centro_id && $carreras->isEmpty())
                            <p class="mt-1 text-xs text-amber-600">No hay carreras disponibles para la facultad o centro elegido.</p>
                        @endif
                        @error('carrera_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </section>

                <section>
                    <h3 class="{{ $sectionTitle }}">Tipo de práctica {!! $asterisco !!}</h3>
                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($tipoPpsOpciones as $value => $label)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm transition {{ $tipo_pps_ss === $value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-blue-300 dark:border-gray-700' }}">
                                <input type="radio" wire:model.live="tipo_pps_ss" value="{{ $value }}" class="text-blue-600 focus:ring-blue-500">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('tipo_pps_ss') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </section>

                <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="{{ $labelClass }}">Total de horas {!! $asterisco !!}</label>
                        <input type="number" min="1" step="1" wire:model="total_horas" class="{{ $inputClass }}">
                        <p class="{{ $hintClass }}">Las que exige el plan de estudios; se indican en la solicitud de práctica.</p>
                        @error('total_horas') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </section>

                <section>
                    <h3 class="{{ $sectionTitle }}">Estudiante</h3>
                    <p class="{{ $hintClass }} mb-3">Búsquelo por su número de cuenta y complete sus datos de contacto.</p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}">Número de cuenta {!! $asterisco !!}</label>
                            <div x-data="{ cuenta: @js($numero_cuenta) }" class="flex max-w-md gap-2">
                                <input type="text" inputmode="numeric" wire:model.blur="numero_cuenta" wire:blur="limpiarErrorBusquedaEstudiante" x-model="cuenta" class="{{ $inputClass }}">
                                <button type="button" x-cloak x-show="cuenta.trim().length > 0"
                                        wire:click="buscarEstudiante" wire:loading.attr="disabled" wire:target="buscarEstudiante"
                                        class="shrink-0 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <span wire:loading.remove wire:target="buscarEstudiante">Buscar</span>
                                    <span wire:loading wire:target="buscarEstudiante">Buscando…</span>
                                </button>
                            </div>
                            @if($estudianteConsultado)
                                <p class="mt-1 text-xs text-green-700 dark:text-green-400">✓ Nombre y correo institucional obtenidos del registro estudiantil.</p>
                            @else
                                <p class="{{ $hintClass }}">Con «Buscar» se completan el nombre y el correo institucional. Si el servicio no responde, escríbalos.</p>
                            @endif
                            @error('numero_cuenta') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}">Nombre completo {!! $asterisco !!}</label>
                            <input type="text" wire:model="estudiante_nombre_completo" class="{{ $inputClass }}">
                            <p class="{{ $hintClass }}">Exactamente como aparece en la tarjeta de identidad.</p>
                            @error('estudiante_nombre_completo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Número de celular {!! $asterisco !!}</label>
                            <input type="tel" wire:model="estudiante_celular" class="{{ $inputClass }}">
                            @error('estudiante_celular') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Correo electrónico institucional {!! $asterisco !!}</label>
                            <input type="email" wire:model="estudiante_correo_institucional" class="{{ $inputClass }}">
                            @error('estudiante_correo_institucional') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Correo electrónico personal {!! $asterisco !!}</label>
                            <input type="email" wire:model="estudiante_correo_personal" class="{{ $inputClass }}">
                            @error('estudiante_correo_personal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>
            </div>
        @endif

        {{-- ══════════════ PASO 2: Institución y solicitud de práctica (VI e ítem 12) ══════════════ --}}
        @if($currentStep === 2)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 2: Institución y solicitud de práctica</h2>
            <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">Elija la institución, a quién va dirigida la carta y la modalidad; luego genere la solicitud de práctica.</p>

            <div class="space-y-8">
                <section>
                    <h3 class="{{ $sectionTitle }}">Institución / empresa {!! $asterisco !!}</h3>

                    @if($modoInstitucion === null)
                        <div class="mt-2 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto] sm:items-start">
                                <div wire:key="buscador-institucion" x-data="{
                                    open: false,
                                    search: '',
                                    selected: @js(filled($institucionBuscadaId) ? (string) $institucionBuscadaId : ''),
                                    options: @js($institucionesCatalogo),
                                    normalizar(texto) { return String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
                                    filtradas() {
                                        const term = this.normalizar(this.search);
                                        return this.options.filter(option => !term || this.normalizar(option.nombre).includes(term));
                                    },
                                    seleccionada() { return this.options.find(option => option.id === this.selected); },
                                    elegir(option) {
                                        this.selected = option.id;
                                        this.search = '';
                                        this.open = false;
                                        this.$wire.set('institucionBuscadaId', option.id, false);
                                    }
                                }" @click.outside="open = false">
                                    <input type="text" autocomplete="off" x-model="search"
                                        @focus="open = true" @click="open = true" @input="open = true" @keydown.escape="open = false"
                                        @keydown.enter.prevent="filtradas().length && elegir(filtradas()[0])"
                                        :placeholder="seleccionada() ? seleccionada().nombre : 'Buscar institución por nombre...'"
                                        :class="seleccionada() ? 'placeholder:text-gray-900 dark:placeholder:text-white' : 'placeholder:text-gray-400'"
                                        class="{{ $inputClass }}">
                                    <div x-show="open" x-cloak class="mt-1 max-h-60 overflow-y-auto rounded-md border border-blue-200 bg-white shadow-sm dark:border-blue-700 dark:bg-gray-800">
                                        <template x-if="filtradas().length === 0">
                                            <div class="px-3 py-2 text-sm text-gray-500">Sin resultados. Si no existe, use «Crear institución».</div>
                                        </template>
                                        <template x-for="option in filtradas()" :key="option.id">
                                            <div @click="elegir(option)" class="cursor-pointer px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-700"
                                                :class="option.id === selected ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                                <span x-text="option.nombre"></span>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="option.detalle"></span>
                                            </div>
                                        </template>
                                    </div>
                                    @error('institucionBuscadaId') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <button type="button" wire:click="usarInstitucionSeleccionada" class="inline-flex items-center justify-center rounded-md bg-orange-600 px-3 py-2 text-xs font-medium text-white hover:bg-orange-700">Usar seleccionada</button>
                            </div>
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Busque la institución por nombre y presione «Usar seleccionada». Si no existe, créela.</p>
                                <button type="button" wire:click="crearInstitucionNueva" class="inline-flex shrink-0 items-center justify-center rounded-md border border-blue-600 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-300 dark:hover:bg-blue-900/30">+ Crear institución</button>
                            </div>
                        </div>
                    @elseif($modoInstitucion === 'existente')
                        <div class="mt-2 rounded-lg border border-green-200 p-4 dark:border-green-900">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $institucion_nombre }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $tipoInstitucionOpciones[$institucion_tipo] ?? $institucion_tipo }} · {{ $sectorOpciones[$institucion_sector] ?? $institucion_sector }}</p>
                                </div>
                                <button type="button" wire:click="cambiarInstitucion" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">Cambiar institución</button>
                            </div>
                            <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                                <div><dt class="text-xs text-gray-500">Nacionalidad</dt><dd class="text-gray-900 dark:text-gray-100">{{ $institucion_nacionalidad === 'Nacional' ? 'Nacional (Honduras)' : 'Internacional ('.$institucion_pais.')' }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Representante legal</dt><dd class="text-gray-900 dark:text-gray-100">{{ $institucion_representante }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Dirección exacta de la sede principal</dt><dd class="text-gray-900 dark:text-gray-100">{{ $institucion_direccion }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Teléfono</dt><dd class="text-gray-900 dark:text-gray-100">{{ $institucion_telefono }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Correo de recursos humanos</dt><dd class="text-gray-900 dark:text-gray-100">{{ $institucion_correo_rrhh }}</dd></div>
                            </dl>
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Los datos vienen del catálogo y no se modifican desde el registro. Si hay un error, el administrador puede corregirlo.</p>
                        </div>
                    @else
                        <div class="mt-2 rounded-lg border border-blue-200 p-4 dark:border-blue-800">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="{{ $labelClass }}">Nombre completo de la institución / organización {!! $asterisco !!}</label>
                                    <input type="text" wire:model="institucion_nombre" class="{{ $inputClass }}">
                                    @error('institucion_nombre') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Nacionalidad {!! $asterisco !!}</label>
                                    <select wire:model.live="institucion_nacionalidad" class="{{ $inputClass }}">
                                        <option value="">Seleccione...</option>
                                        @foreach($institucionNacionalidadOpciones as $opcion)
                                            <option value="{{ $opcion }}">{{ $opcion }}</option>
                                        @endforeach
                                    </select>
                                    @error('institucion_nacionalidad') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">País {!! $institucion_nacionalidad === 'Internacional' ? $asterisco : '' !!}</label>
                                    @if($institucion_nacionalidad === 'Internacional')
                                        <select wire:model="institucion_pais" class="{{ $inputClass }}">
                                            <option value="">Seleccione el país...</option>
                                            @foreach($paises as $nombrePais)
                                                <option value="{{ $nombrePais }}">{{ $nombrePais }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" value="{{ $institucion_nacionalidad === 'Nacional' ? 'Honduras' : '' }}" readonly class="{{ $readonlyClass }}">
                                    @endif
                                    @error('institucion_pais') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Tipo de institución / organización {!! $asterisco !!}</label>
                                    <select wire:model="institucion_tipo" class="{{ $inputClass }}">
                                        <option value="">Seleccione...</option>
                                        @foreach($tipoInstitucionOpciones as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('institucion_tipo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Sector al que pertenece {!! $asterisco !!}</label>
                                    <select wire:model="institucion_sector" class="{{ $inputClass }}">
                                        <option value="">Seleccione...</option>
                                        @foreach($sectorOpciones as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('institucion_sector') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="{{ $labelClass }}">Dirección exacta de la sede principal {!! $asterisco !!}</label>
                                    <textarea wire:model="institucion_direccion" rows="2" class="{{ $inputClass }}"></textarea>
                                    @error('institucion_direccion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="{{ $labelClass }}">Nombre completo del representante legal {!! $asterisco !!}</label>
                                    <input type="text" wire:model="institucion_representante" class="{{ $inputClass }}">
                                    @error('institucion_representante') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Número de teléfono {!! $asterisco !!}</label>
                                    <input type="tel" wire:model="institucion_telefono" class="{{ $inputClass }}">
                                    @error('institucion_telefono') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Correo del departamento de recursos humanos {!! $asterisco !!}</label>
                                    <input type="email" wire:model="institucion_correo_rrhh" class="{{ $inputClass }}">
                                    @error('institucion_correo_rrhh') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap justify-end gap-2">
                                <button type="button" wire:click="cambiarInstitucion" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>
                                <button type="button" wire:click="guardarInstitucionNueva" wire:loading.attr="disabled" wire:target="guardarInstitucionNueva" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-60">Guardar institución</button>
                            </div>
                        </div>
                    @endif
                    @error('pps_institucion_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </section>

                <section>
                    <h3 class="{{ $sectionTitle }}">Destinatario de la solicitud de práctica</h3>
                    <p class="{{ $hintClass }} mb-3">Persona de la institución a quien va dirigida la carta (por ejemplo, reclutamiento o recursos humanos). El jefe directo se registra después, cuando la institución lo asigne.</p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $labelClass }}">Tratamiento {!! $asterisco !!}</label>
                            <select wire:model.live="destinatario_tratamiento" class="{{ $inputClass }}">
                                <option value="">Seleccione...</option>
                                @foreach($tratamientos as $tratamiento)
                                    <option value="{{ $tratamiento }}">{{ $tratamiento }}</option>
                                @endforeach
                            </select>
                            @error('destinatario_tratamiento') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}">Nombre completo {!! $asterisco !!}</label>
                            <input type="text" wire:model="destinatario_nombre" maxlength="255" class="{{ $inputClass }}" placeholder="Ej.: María Helena Mejía">
                            @error('destinatario_nombre') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-3">
                            <label class="{{ $labelClass }}">Cargo {!! $asterisco !!}</label>
                            <input type="text" wire:model="destinatario_cargo" maxlength="255" class="{{ $inputClass }}" placeholder="Ej.: Coordinadora de Reclutamiento">
                            @error('destinatario_cargo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="{{ $sectionTitle }}">Modalidad {!! $asterisco !!}</h3>
                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @foreach($modalidadOpciones as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm transition {{ $modalidadElegida === $value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-blue-300 dark:border-gray-700' }}">
                                <input type="radio" wire:model.live="modalidad_ejecucion" value="{{ $value }}" class="text-blue-600 focus:ring-blue-500">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('modalidad_ejecucion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </section>

                <section class="rounded-lg border border-blue-200 p-4 dark:border-blue-900">
                    <h3 class="{{ $sectionTitle }}">Solicitud de práctica</h3>
                    <p class="{{ $hintClass }} mb-4">Se genera con los datos del paso 1 y de esta página. Envíela a la institución y, con su respuesta, continúe con el paso 3. Si cambia algún dato, genere una nueva versión.</p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">Lugar de emisión {!! $asterisco !!}</label>
                            <input type="text" wire:model="solicitud_lugar" maxlength="255" class="{{ $inputClass }}" placeholder="Ej.: Choluteca">
                            <p class="{{ $hintClass }}">Aparece junto a la fecha de la carta.</p>
                            @error('solicitud_lugar') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <p class="{{ $labelClass }}">Firma</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ auth()->user()?->empleado?->nombre_completo ?? 'Quien llena el formulario' }}</p>
                            <p class="{{ $hintClass }}">{{ \App\Services\PpsServicioSocial\PpsDocumentoGenerator::cargoFirmante(auth()->user()?->empleado?->sexo) }}, como coordinador que registra la práctica.</p>
                            <p class="{{ $hintClass }}">{{ $firmaRegistrada ? 'La carta lleva su firma registrada.' : 'No tiene firma registrada: la carta queda con el espacio para firmar a mano.' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="generarSolicitud" wire:loading.attr="disabled" wire:target="generarSolicitud"
                            class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="generarSolicitud">{{ $solicitudesGeneradas->isEmpty() ? 'Generar solicitud' : 'Generar nueva versión' }}</span>
                            <span wire:loading wire:target="generarSolicitud">Generando…</span>
                        </button>
                        @if($solicitudesGeneradas->isEmpty())
                            <p class="text-xs text-gray-500 dark:text-gray-400">Genere la solicitud para continuar con el registro.</p>
                        @endif
                    </div>
                    @error('solicitud') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror

                    @if($solicitudesGeneradas->isNotEmpty())
                        @php $solicitudVisible = $solicitudesGeneradas->firstWhere('id', $solicitudVisibleId) ?? $solicitudesGeneradas->first(); @endphp
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Versiones:</span>
                            @foreach($solicitudesGeneradas as $documento)
                                <button type="button" wire:key="version-solicitud-{{ $documento->id }}" wire:click="$set('solicitudVisibleId', {{ $documento->id }})"
                                    class="rounded-full px-3 py-1 font-medium {{ $documento->id === $solicitudVisible->id ? 'bg-blue-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                                    v{{ $documento->version }} · {{ $documento->generado_en?->format('d/m/Y H:i') }}
                                </button>
                            @endforeach
                        </div>
                        {{-- Visor del navegador, como el del FORM-DVUS-018: permite ver, imprimir y descargar. --}}
                        <iframe wire:key="visor-solicitud-{{ $solicitudVisible->id }}"
                            src="{{ route('pps-servicio-social.documento-generado', ['documento' => $solicitudVisible, 'ver' => 1]) }}"
                            title="Solicitud de práctica v{{ $solicitudVisible->version }}"
                            class="mt-3 block min-h-[85vh] w-full rounded-lg border border-gray-200 bg-white dark:border-gray-700"></iframe>
                    @endif
                </section>
            </div>
        @endif

        {{-- ══════════════ PASO 3: Respuesta de la institución (ítems 10 y 11, V) ══════════════ --}}
        @if($currentStep === 3)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 3: Respuesta de la institución</h2>
            <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">Complete con lo que respondió la institución a la solicitud: fechas, funciones del puesto y jefe inmediato.</p>

            <div class="space-y-8">
                <section>
                    <h3 class="{{ $sectionTitle }}">Fechas de ejecución</h3>
                    <div class="mt-2 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">Fecha de inicio {!! $asterisco !!}</label>
                            <input type="date" wire:model.live="fecha_inicio" class="{{ $inputClass }}">
                            @error('fecha_inicio') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Fecha de finalización {!! $asterisco !!}</label>
                            <input type="date" wire:model.live="fecha_finalizacion" class="{{ $inputClass }}">
                            <p class="{{ $hintClass }}">Al elegir el inicio se sugieren cinco meses; puede cambiarla.</p>
                            @error('fecha_finalizacion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @php $diasHabiles = $this->diasHabilesPlanificados(); @endphp
                    @if($diasHabiles !== null)
                        <p class="mt-3 rounded-md bg-blue-50 px-3 py-2 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                            Horas planificadas: <strong>{{ number_format($this->horasPlanificadas()) }}</strong>
                            <span class="text-xs">({{ $diasHabiles }} {{ $diasHabiles === 1 ? 'día hábil' : 'días hábiles' }} de lunes a viernes × {{ $this::HORAS_POR_DIA_PLANIFICADO }} horas)</span>
                        </p>
                    @endif
                </section>

                <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label class="{{ $labelClass }}">Descripción del tipo de PPS {!! $asterisco !!}</label>
                        <textarea wire:model="descripcion_tipo_pps" rows="4" class="{{ $inputClass }}"
                            placeholder="Ej.: Práctica profesionalizante"></textarea>
                        <p class="{{ $hintClass }}">De acuerdo al plan de estudios de la carrera, indique si la práctica es intermedia, profesionalizante u otra (en Arquitectura, si es de diseño, construcción u otro tipo). Si se realizan dos modalidades, describa ambas. En servicio social escriba SERVICIO SOCIAL.</p>
                        @error('descripcion_tipo_pps') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $labelClass }}">Desglose de horas <span class="font-normal text-gray-400">(opcional)</span></label>
                        <textarea wire:model="descripcion_horas_tipo_pps_ss" rows="3" maxlength="500" class="{{ $inputClass }}" placeholder="Ej.: 240 h diseño + 240 h construcción"></textarea>
                        <p class="{{ $hintClass }}">Solo si la práctica tiene dos modalidades.</p>
                        @error('descripcion_horas_tipo_pps_ss') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="{{ $labelClass }}">Departamento o área donde se realizará {!! $asterisco !!}</label>
                        <input type="text" wire:model="area_realizacion" class="{{ $inputClass }}" placeholder="Ej.: Departamento de Contabilidad, Gerencia de Recursos Humanos">
                        @error('area_realizacion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="{{ $labelClass }}">Resumen de las responsabilidades y tareas que realizará {!! $asterisco !!}</label>
                        <textarea wire:model="resumen_responsabilidades" rows="4" class="{{ $inputClass }}"></textarea>
                        @error('resumen_responsabilidades') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </section>

                <section>
                    <h3 class="{{ $sectionTitle }}">Jefe inmediato (jefe directo de la PPS / SS)</h3>
                    <p class="{{ $hintClass }} mb-3">Persona de la institución que supervisa al estudiante.</p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}">Nombre completo {!! $asterisco !!}</label>
                            <input type="text" wire:model="jefe_directo_nombre" class="{{ $inputClass }}">
                            @error('jefe_directo_nombre') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Número de celular {!! $asterisco !!}</label>
                            <input type="tel" wire:model="jefe_directo_celular" class="{{ $inputClass }}">
                            @error('jefe_directo_celular') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Correo electrónico {!! $asterisco !!}</label>
                            <input type="email" wire:model="jefe_directo_correo" class="{{ $inputClass }}">
                            @error('jefe_directo_correo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Cargo {!! $asterisco !!}</label>
                            <input type="text" wire:model="jefe_directo_cargo" class="{{ $inputClass }}">
                            @error('jefe_directo_cargo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Grado académico {!! $asterisco !!}</label>
                            <select wire:model="jefe_directo_grado" class="{{ $inputClass }}">
                                <option value="">Seleccione...</option>
                                @foreach($this::GRADO_ACADEMICO_JEFE_DIRECTO_OPCIONES as $grado)
                                    <option value="{{ $grado }}">{{ $grado }}</option>
                                @endforeach
                            </select>
                            @error('jefe_directo_grado') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>
            </div>
        @endif

        {{-- ══════════════ PASO 4: Ubicación y jornada (ítem 12 y IV) ══════════════ --}}
        @if($currentStep === 4)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 4: Ubicación y jornada</h2>
            <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">Dónde y cómo se realiza la práctica. Se piden solo los datos de la modalidad elegida.</p>

            <div class="space-y-8">
                <section class="space-y-6">
                    <div>
                        <h3 class="{{ $sectionTitle }}">Territorio de ejecución {!! $asterisco !!}</h3>
                        <div class="mt-2 grid max-w-xl grid-cols-2 gap-3">
                            @foreach(['Nacional', 'Internacional'] as $territorio)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm transition {{ $territorio_ejecucion === $territorio ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-blue-300 dark:border-gray-700' }}">
                                    <input type="radio" wire:model.live="territorio_ejecucion" value="{{ $territorio }}" class="text-blue-600 focus:ring-blue-500">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $territorio }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('territorio_ejecucion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Modalidad: <strong>{{ $modalidadOpciones[$modalidadElegida] ?? 'sin elegir' }}</strong>
                        <button type="button" wire:click="goToStep(2)" class="ml-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">Cambiar en el paso 2</button>
                    </p>
                </section>

                @if($modalidadElegida === '')
                    <p class="rounded-md border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-gray-600">Elija la modalidad en el paso 2 para completar la ubicación de la práctica.</p>
                @endif

                @if($aplicaPresencial)
                    <section>
                        <h3 class="{{ $sectionTitle }}">Práctica presencial</h3>
                        <p class="{{ $hintClass }} mb-3">Lugar donde el estudiante realiza la práctica. Región: <strong>{{ $nacional ? 'Nacional' : 'Extranjero' }}</strong> (según el territorio de ejecución).</p>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="{{ $labelClass }}">País {!! $asterisco !!}</label>
                                @if($nacional)
                                    <input type="text" value="Honduras" readonly class="{{ $readonlyClass }}">
                                @else
                                    <x-forms.searchable-select wire:model.live="pais_id" :options="$paises" placeholder="Buscar o seleccionar país..." />
                                    @error('pais_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                @endif
                            </div>

                            @if($usaCatalogoDepartamentos)
                                <div>
                                    <label class="{{ $labelClass }}">{{ $nacional ? 'Departamento' : 'Departamento / provincia' }} {!! $asterisco !!}</label>
                                    <x-forms.searchable-select wire:model.live="departamento_id" :options="$departamentos" placeholder="Buscar o seleccionar departamento..." />
                                    @error('departamento_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Municipio {!! $asterisco !!}</label>
                                    <x-forms.searchable-select wire:model.live="municipio_id" :options="$municipios"
                                        :disabled="!$departamento_id || $municipios->isEmpty()"
                                        :placeholder="$departamento_id ? 'Buscar o seleccionar municipio...' : 'Seleccione primero el departamento'" />
                                    @error('municipio_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            @elseif($pais_id)
                                <div>
                                    <label class="{{ $labelClass }}">Departamento / provincia {!! $asterisco !!}</label>
                                    <input type="text" wire:model="departamento_provincia" maxlength="255" class="{{ $inputClass }}">
                                    @error('departamento_provincia') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Municipio {!! $asterisco !!}</label>
                                    <input type="text" wire:model="municipio_texto" maxlength="255" class="{{ $inputClass }}">
                                    @error('municipio_texto') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <p class="md:col-span-2 -mt-2 text-xs text-amber-700 dark:text-amber-400">{{ $paises[$pais_id] ?? 'Este país' }} aún no tiene departamentos en el catálogo: escríbalos. El administrador puede agregarlos en Demografía.</p>
                            @else
                                <p class="self-end pb-2 text-xs text-gray-500 dark:text-gray-400">Elija el país para ver sus departamentos y municipios.</p>
                            @endif

                            <div>
                                <label class="{{ $labelClass }}">Aldea (incluye ciudad) {!! $asterisco !!}</label>
                                <input type="text" wire:model.live.debounce.500ms="aldea_ciudad" maxlength="255" class="{{ $inputClass }}">
                                @error('aldea_ciudad') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Caserío {!! $asterisco !!}</label>
                                <input type="text" wire:model="caserio" maxlength="255" class="{{ $inputClass }}">
                                @error('caserio') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>
                @endif

                @if($aplicaTeletrabajo)
                    <section>
                        <h3 class="{{ $sectionTitle }}">Práctica en teletrabajo: sede principal</h3>
                        <p class="{{ $hintClass }} mb-3">Ubicación de la sede principal de la institución para la que se teletrabaja.</p>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="{{ $labelClass }}">País de la sede principal {!! $asterisco !!}</label>
                                @if($nacional)
                                    <input type="text" value="Honduras" readonly class="{{ $readonlyClass }}">
                                @else
                                    <x-forms.searchable-select wire:model.live="pais_sede_id" :options="$paises" placeholder="Buscar o seleccionar país..." />
                                    @error('pais_sede_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                @endif
                            </div>

                            @if($usaCatalogoDepartamentosSede)
                                <div>
                                    <label class="{{ $labelClass }}">{{ $nacional ? 'Departamento' : 'Departamento / provincia' }} {!! $asterisco !!}</label>
                                    <x-forms.searchable-select wire:model.live="departamento_sede_id" :options="$departamentosSede" placeholder="Buscar o seleccionar departamento..." />
                                    @error('departamento_sede_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Municipio {!! $asterisco !!}</label>
                                    <x-forms.searchable-select wire:model.live="municipio_sede_id" :options="$municipiosSede"
                                        :disabled="!$departamento_sede_id || $municipiosSede->isEmpty()"
                                        :placeholder="$departamento_sede_id ? 'Buscar o seleccionar municipio...' : 'Seleccione primero el departamento'" />
                                    @error('municipio_sede_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            @elseif($pais_sede_id)
                                <div>
                                    <label class="{{ $labelClass }}">Departamento / provincia {!! $asterisco !!}</label>
                                    <input type="text" wire:model="departamento_provincia_sede_principal" maxlength="255" class="{{ $inputClass }}">
                                    @error('departamento_provincia_sede_principal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Municipio {!! $asterisco !!}</label>
                                    <input type="text" wire:model="municipio_sede_principal" maxlength="255" class="{{ $inputClass }}">
                                    @error('municipio_sede_principal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <p class="md:col-span-2 -mt-2 text-xs text-amber-700 dark:text-amber-400">{{ $paises[$pais_sede_id] ?? 'Este país' }} aún no tiene departamentos en el catálogo: escríbalos. El administrador puede agregarlos en Demografía.</p>
                            @else
                                <p class="self-end pb-2 text-xs text-gray-500 dark:text-gray-400">Elija el país para ver sus departamentos y municipios.</p>
                            @endif

                            <div>
                                <label class="{{ $labelClass }}">Aldea / ciudad {!! $asterisco !!}</label>
                                <input type="text" wire:model="aldea_ciudad_sede_principal" maxlength="255" class="{{ $inputClass }}">
                                @error('aldea_ciudad_sede_principal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>
                @endif

                @if($modalidadElegida !== '')
                    <section>
                        <h3 class="{{ $sectionTitle }}">Distribución de la jornada</h3>
                        <div class="mt-2 grid grid-cols-1 gap-4 md:grid-cols-2">
                            @if($aplicaPresencial)
                                <div>
                                    <label class="{{ $labelClass }}">Horas presenciales {!! $asterisco !!}</label>
                                    <input type="number" min="1" step="1" wire:model="horas_presenciales" class="{{ $inputClass }}">
                                    @error('horas_presenciales') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            @endif
                            @if($aplicaTeletrabajo)
                                <div>
                                    <label class="{{ $labelClass }}">Horas de teletrabajo {!! $asterisco !!}</label>
                                    <input type="number" min="1" step="1" wire:model="horas_teletrabajo" class="{{ $inputClass }}">
                                    @error('horas_teletrabajo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>
                    </section>
                @endif
            </div>
        @endif

        {{-- ══════════════ PASO 5: Formalización y docente supervisor (VI, IX y VII) ══════════════ --}}
        @if($currentStep === 5)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 5: Formalización y supervisor</h2>
            <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">Cómo se formaliza la práctica con {{ $institucion_nombre ?: 'la institución' }} y qué docente de la UNAH la supervisa.</p>

            <div class="space-y-8">
                <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <h3 class="{{ $sectionTitle }}">Formalización de la práctica</h3>
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Tipo de instrumento que formaliza la PPS / SS {!! $asterisco !!}</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            @foreach($instrumentoOpciones as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm transition {{ $tipo_instrumento === $value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 hover:border-blue-300 dark:border-gray-700' }}">
                                    <input type="radio" wire:model.live="tipo_instrumento" value="{{ $value }}" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-gray-800 dark:text-gray-200">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('tipo_instrumento') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Documentos adjuntos</label>
                        <p class="{{ $hintClass }} mb-2">Documentos que formalizan la práctica (sección IX de la ficha).</p>
                    @php
                        $documentos = [
                            [
                                'numero' => 1,
                                'titulo' => 'Carta de formalización de la PPS firmada por la contraparte',
                                'ayuda' => 'Obligatoria.',
                                'obligatorio' => true,
                                'modelo' => 'carta_formalizacion_archivo',
                                'ruta' => 'carta-formalizacion',
                                'nuevo' => $carta_formalizacion_archivo,
                                'actual' => $archivo_carta_formalizacion_actual,
                                'quitar' => null,
                            ],
                            [
                                'numero' => 2,
                                'titulo' => 'Convenio marco entre la UNAH y la entidad',
                                'ayuda' => $tipo_instrumento === 'convenio_marco'
                                    ? 'Obligatorio: el instrumento que formaliza la práctica es un convenio marco.'
                                    : 'Solo si se tiene.',
                                'obligatorio' => $tipo_instrumento === 'convenio_marco',
                                'modelo' => 'convenio_marco_archivo',
                                'ruta' => 'convenio-marco',
                                'nuevo' => $convenio_marco_archivo,
                                'actual' => $convenio_marco_aplica === 'No' ? null : $archivo_convenio_marco_actual,
                                'quitar' => 'quitarConvenioMarco',
                            ],
                        ];
                    @endphp

                    <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-700">
                        @foreach($documentos as $documento)
                            @php $adjunto = $documento['nuevo'] || filled($documento['actual']); @endphp
                            <div wire:key="documento-{{ $documento['numero'] }}" class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $documento['numero'] }}. {{ $documento['titulo'] }} {!! $documento['obligatorio'] ? $asterisco : '' !!}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $documento['ayuda'] }}</p>
                                    @if($documento['nuevo'])
                                        <p class="mt-1 text-xs text-green-700 dark:text-green-400">✓ Seleccionado: {{ method_exists($documento['nuevo'], 'getClientOriginalName') ? $documento['nuevo']->getClientOriginalName() : 'archivo' }} (se guarda con «Guardar cambios»)</p>
                                    @elseif(filled($documento['actual']))
                                        <p class="mt-1 text-xs text-green-700 dark:text-green-400">✓ Adjunto:
                                            {{-- Se abre por la ruta del registro (valida permisos), no por la URL pública de storage. --}}
                                            <a href="{{ route('pps-servicio-social.anexo', ['id' => $registroId, 'tipo' => $documento['ruta']]) }}" target="_blank" class="font-semibold hover:underline">{{ basename($documento['actual']) }}</a>
                                        </p>
                                    @endif
                                    @error($documento['modelo']) <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $adjunto ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $adjunto ? 'Sí' : 'No' }}</span>
                                    <input id="{{ $documento['modelo'] }}" type="file" wire:model="{{ $documento['modelo'] }}" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden">
                                    <label for="{{ $documento['modelo'] }}" class="cursor-pointer rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300">{{ $adjunto ? 'Reemplazar' : 'Seleccionar archivo' }}</label>
                                    @if($documento['quitar'] && $adjunto)
                                        <button type="button" wire:click="{{ $documento['quitar'] }}" class="text-xs font-medium text-red-600 hover:text-red-800">Quitar</button>
                                    @endif
                                    <span wire:loading wire:target="{{ $documento['modelo'] }}" class="text-xs text-blue-600">Cargando…</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="{{ $hintClass }} mt-2">Formatos: PDF, Word o imagen (máx. 10 MB).</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Breve descripción de los compromisos asumidos por la institución {!! $asterisco !!}</label>
                        <textarea wire:model="institucion_compromisos" rows="3" class="{{ $inputClass }}"></textarea>
                        @error('institucion_compromisos') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </section>

                <section>
                    <h3 class="{{ $sectionTitle }}">Docente supervisor(a) de la PPS / SS</h3>
                    <p class="{{ $hintClass }} mb-3">Busque al docente de la UNAH que supervisa la práctica; sus datos se completan desde su expediente.</p>
                    @if(!$docente_supervisor_id)
                        <div class="max-w-2xl">
                            <label class="{{ $labelClass }}">Buscar docente {!! $asterisco !!}</label>
                            <input type="search" wire:model.live.debounce.300ms="docenteBusqueda" placeholder="Escriba el nombre o el número de empleado..." class="{{ $inputClass }}">
                            @if(filled($docente_supervisor_nombre))
                                <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">El supervisor guardado ({{ $docente_supervisor_nombre }}) no está vinculado a un empleado del sistema: búsquelo y selecciónelo.</p>
                            @endif
                            @error('docente_supervisor_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror

                            @if(mb_strlen(trim($docenteBusqueda)) >= 2)
                                <div class="mt-2 divide-y divide-gray-100 overflow-hidden rounded-md border border-gray-200 dark:divide-gray-800 dark:border-gray-700">
                                    @forelse($docentesEncontrados as $docente)
                                        <div wire:key="docente-{{ $docente->id }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-gray-900 dark:text-white">{{ $docente->nombre_completo }}</p>
                                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $docente->numero_empleado ? 'N.º '.$docente->numero_empleado : 'Sin número de empleado' }}
                                                    @if($docente->departamento_academico) · {{ $docente->departamento_academico->nombre }} @endif
                                                </p>
                                            </div>
                                            <button type="button" wire:click="seleccionarDocente({{ $docente->id }})" class="shrink-0 rounded bg-blue-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-700">Seleccionar</button>
                                        </div>
                                    @empty
                                        <p class="px-3 py-4 text-center text-sm text-gray-500">No se encontraron docentes con ese nombre o número.</p>
                                    @endforelse
                                </div>
                            @else
                                <p class="{{ $hintClass }}">Escriba al menos dos letras o números.</p>
                            @endif
                        </div>
                    @else
                        <div class="rounded-lg border border-green-200 p-4 dark:border-green-900">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $docente_supervisor_nombre }}</p>
                                <button type="button" wire:click="cambiarDocente" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">Cambiar docente</button>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach([
                                    'docente_numero_empleado' => 'Número de empleado',
                                    'docente_celular' => 'Número de celular',
                                    'docente_correo' => 'Correo electrónico',
                                    'docente_categoria' => 'Categoría',
                                    'docente_departamento' => 'Departamento al que pertenece',
                                ] as $campo => $etiqueta)
                                    @php
                                        $delSistema = in_array($campo, $docenteCamposDelSistema, true);
                                        $catalogo = ['docente_categoria' => $categoriasDocente, 'docente_departamento' => $departamentosDocente][$campo] ?? null;
                                    @endphp
                                    <div>
                                        <label class="{{ $labelClass }}">{{ $etiqueta }} {!! $delSistema ? '' : $asterisco !!}</label>
                                        @if($catalogo !== null && ! $delSistema)
                                            <x-forms.searchable-select wire:model.live="{{ $campo }}" :options="$catalogo" empty-value=""
                                                :placeholder="$campo === 'docente_categoria' ? 'Buscar o seleccionar categoría...' : 'Buscar o seleccionar departamento...'" />
                                            <p class="{{ $hintClass }}">El expediente del docente no tiene este dato; elíjalo.</p>
                                        @else
                                            <input type="{{ $campo === 'docente_correo' ? 'email' : 'text' }}" wire:model="{{ $campo }}" @readonly($delSistema) class="{{ $delSistema ? $readonlyClass : $inputClass }}">
                                            @unless($delSistema)<p class="{{ $hintClass }}">El expediente del docente no tiene este dato; escríbalo.</p>@endunless
                                        @endif
                                        @error($campo) <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                                <div>
                                    <label class="{{ $labelClass }}">Jornada laboral {!! $asterisco !!}</label>
                                    <select wire:model="docente_jornada" class="{{ $inputClass }}">
                                        <option value="">Seleccione...</option>
                                        @foreach($jornadasLaborales as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @error('docente_jornada') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="{{ $labelClass }}">Ubicación del cubículo en la UNAH {!! $asterisco !!}</label>
                                    <input type="text" wire:model="docente_cubiculo" class="{{ $inputClass }}" placeholder="Ej.: Edificio F1, segundo piso, cubículo 204">
                                    @error('docente_cubiculo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        @endif

        {{-- ══════════════ PASO 6: Revisión y envío ══════════════ --}}
        @if($currentStep === 6)
            @php
                $unir = fn (array $partes, string $separador = ', ') => collect($partes)->filter(fn ($parte) => filled($parte))->implode($separador);
                $fecha = fn (?string $valor) => filled($valor) ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y') : null;
                $departamentoLugar = $usaCatalogoDepartamentos ? ($departamentos[$departamento_id] ?? null) : $departamento_provincia;
                $municipioLugar = $usaCatalogoDepartamentos ? ($municipios[$municipio_id] ?? null) : $municipio_texto;
                $resumen = [
                    1 => ['titulo' => 'Estudiante y práctica', 'datos' => [
                        'Facultad / centro' => $facultadesCentros[$facultad_centro_id] ?? null,
                        'Carrera' => $carreras[$carrera_id] ?? null,
                        'Tipo de práctica' => $tipoPpsOpciones[$tipo_pps_ss] ?? $tipo_pps_ss,
                        'Total de horas' => $total_horas,
                        'Estudiante' => $unir([$estudiante_nombre_completo, $numero_cuenta], ' · '),
                        'Contacto' => $unir([$estudiante_celular, $estudiante_correo_institucional, $estudiante_correo_personal], ' · '),
                    ]],
                    2 => ['titulo' => 'Institución y solicitud', 'datos' => [
                        'Institución' => $pps_institucion_id ? $institucion_nombre : null,
                        'Dirigida a' => $unir([$unir([$destinatario_tratamiento, $destinatario_nombre], ' '), $destinatario_cargo], ' · '),
                        'Modalidad' => $modalidadOpciones[$modalidadElegida] ?? null,
                        'Solicitud' => $this->solicitudGenerada() ? $unir(['Generada', $solicitud_lugar], ' · ') : null,
                    ]],
                    3 => ['titulo' => 'Respuesta de la institución', 'datos' => [
                        'Ejecución' => $unir([$fecha($fecha_inicio), $fecha($fecha_finalizacion)], ' al '),
                        'Tipo de PPS' => \Illuminate\Support\Str::limit($descripcion_tipo_pps, 160),
                        'Departamento o área' => $area_realizacion,
                        'Responsabilidades' => \Illuminate\Support\Str::limit($resumen_responsabilidades, 200),
                        'Jefe inmediato' => $unir([$jefe_directo_nombre, $jefe_directo_cargo], ' · '),
                        'Contacto del jefe' => $unir([$jefe_directo_celular, $jefe_directo_correo], ' · '),
                    ]],
                    4 => ['titulo' => 'Ubicación y jornada', 'datos' => array_filter([
                        'Territorio' => $territorio_ejecucion,
                        'Lugar' => $aplicaPresencial ? $unir([$caserio, $aldea_ciudad, $municipioLugar, $departamentoLugar, $nacional ? 'Honduras' : $pais]) : false,
                        'Sede principal' => $aplicaTeletrabajo ? $unir([$aldea_ciudad_sede_principal, $municipio_sede_principal, $departamento_provincia_sede_principal, $nacional ? 'Honduras' : $pais_sede_principal]) : false,
                        'Horas' => $unir([
                            $aplicaPresencial && filled($horas_presenciales) ? $horas_presenciales.' presenciales' : null,
                            $aplicaTeletrabajo && filled($horas_teletrabajo) ? $horas_teletrabajo.' de teletrabajo' : null,
                        ], ' · '),
                    ], fn ($dato) => $dato !== false)],
                    5 => ['titulo' => 'Formalización y supervisor', 'datos' => [
                        'Instrumento' => $instrumentoOpciones[$tipo_instrumento] ?? null,
                        'Documentos' => $unir([
                            $this->tieneCartaFormalizacion() ? 'Carta de formalización ✓' : null,
                            $this->tieneConvenioMarco() ? 'Convenio marco ✓' : null,
                        ], ' · '),
                        'Compromisos' => \Illuminate\Support\Str::limit($institucion_compromisos, 160),
                        'Docente supervisor' => $docente_supervisor_id ? $unir([$docente_supervisor_nombre, $docente_numero_empleado], ' · ') : null,
                        'Contacto del docente' => $unir([$docente_celular, $docente_correo], ' · '),
                        'Jornada y cubículo' => $unir([$docente_jornada, $docente_cubiculo], ' · '),
                    ]],
                ];
            @endphp

            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 6: Revisión y envío</h2>
            <p class="mt-1 mb-5 text-sm text-gray-500 dark:text-gray-400">Revise la información antes de enviarla a firmar. Con «Editar» vuelve al paso correspondiente.</p>

            @if($this->isStepComplete($totalSteps))
                <div class="mb-5 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-900/20 dark:text-green-200">
                    Todo está completo. Puede enviar el registro a firmar.
                </div>
            @else
                <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-900/20 dark:text-amber-200">
                    Hay pasos incompletos. Complételos antes de enviar; puede guardar el borrador mientras tanto.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach($resumen as $paso => $bloque)
                    @php $pasoCompleto = $this->isStepComplete($paso); @endphp
                    <section wire:key="resumen-paso-{{ $paso }}" class="rounded-lg border p-4 {{ $pasoCompleto ? 'border-gray-200 dark:border-gray-700' : 'border-amber-300 dark:border-amber-800' }}">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $paso }}. {{ $bloque['titulo'] }}</h3>
                            <div class="flex shrink-0 items-center gap-3">
                                @if($pasoCompleto)
                                    <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">✓ Completo</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">⚠ Incompleto</span>
                                @endif
                                <button type="button" wire:click="goToStep({{ $paso }})" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">Editar</button>
                            </div>
                        </div>
                        <dl class="space-y-1.5 text-sm">
                            @foreach($bloque['datos'] as $etiqueta => $dato)
                                <div class="grid grid-cols-[9rem_1fr] gap-2">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $etiqueta }}</dt>
                                    <dd class="break-words {{ filled($dato) ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400' }}">{{ filled($dato) ? $dato : 'Sin completar' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endforeach
            </div>
        @endif

        <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
            <div>
                @if($currentStep > 1)
                    <button type="button" wire:click="prevStep" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        &larr; Anterior
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-3">
                @if($currentStep < $totalSteps)
                    <button type="button" wire:click="nextStep" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Siguiente &rarr;
                    </button>
                @else
                    <button type="button" wire:click="guardarBorrador"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        {{ $esRevisor ? 'Guardar cambios' : 'Guardar como borrador' }}
                    </button>
                    @unless($esRevisor)
                        <button type="button" wire:click="abrirModalEnviar" wire:loading.attr="disabled" wire:target="abrirModalEnviar"
                            class="inline-flex items-center rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 disabled:opacity-60">
                            Enviar a firmar
                        </button>
                    @endunless
                @endif
            </div>
        </div>
    </form>

    @if ($showEnviarModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">

                {{-- Header --}}
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Enviar a revisión</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            @if (count($modalEtapas) > 0)
                                Configura los destinatarios solo en las etapas donde el flujo indica que el emisor debe definirlos.
                            @else
                                El registro será enviado al flujo de revisión configurado.
                            @endif
                        </p>
                    </div>
                    <button wire:click="cancelarModal" class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400">
                        &times;
                    </button>
                </div>

                @if (count($modalEtapas) > 0)
                    {{-- Indicador de pasos del modal --}}
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        @foreach ($modalEtapas as $i => $etapa)
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $modalStep === $i + 1 ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'border border-slate-300 text-slate-400 dark:border-slate-600' }}">{{ $i + 1 }}</span>
                                <span class="text-sm {{ $modalStep === $i + 1 ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">{{ $etapa['nombre'] }}</span>
                                <span class="text-slate-300 dark:text-slate-600">&rarr;</span>
                            </div>
                        @endforeach
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $modalStep === count($modalEtapas) + 1 ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'border border-slate-300 text-slate-400 dark:border-slate-600' }}">{{ count($modalEtapas) + 1 }}</span>
                            <span class="text-sm {{ $modalStep === count($modalEtapas) + 1 ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">Confirmación</span>
                        </div>
                    </div>

                    {{-- Contenido por etapa --}}
                    @foreach ($modalEtapas as $i => $etapa)
                        @if ($modalStep === $i + 1)
                            <div class="mt-6 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                <h3 class="font-semibold text-slate-900 dark:text-white">Etapa {{ $i + 1 }} &middot; {{ $etapa['nombre'] }}</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Selecciona el usuario que recibirá el registro en esta etapa. Rol de la etapa: <strong>{{ $etapa['rol_nombre'] }}</strong>.
                                </p>
                                <div class="mt-4">
                                    <select wire:model="modalDestinatarios.{{ $etapa['id'] }}"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">Buscar por nombre o correo</option>
                                        @foreach ($etapa['usuarios'] as $usuario)
                                            <option value="{{ $usuario['id'] }}">{{ $usuario['name'] }}{{ filled($usuario['email']) ? ' — '.$usuario['email'] : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('modal_destinatario_'.($i + 1))
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    @if (empty($etapa['usuarios']))
                                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">No hay usuarios con el rol {{ $etapa['rol_nombre'] }} disponibles.</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Paso de confirmación --}}
                    @if ($modalStep === count($modalEtapas) + 1)
                        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                            <h3 class="font-semibold text-emerald-800 dark:text-emerald-200">Listo para enviar</h3>
                            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                                Se asignarán los destinatarios seleccionados y el registro pasará al flujo de revisión.
                            </p>
                        </div>
                    @endif

                @else
                    {{-- Sin etapas con emisor_define_destinatario — confirmación directa --}}
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">
                            El flujo de revisión asignará los responsables automáticamente según la configuración.
                        </p>
                    </div>
                @endif

                {{-- Footer --}}
                <div class="mt-6 flex items-center justify-between">
                    <button wire:click="cancelarModal" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancelar
                    </button>
                    <div class="flex items-center gap-2">
                        @if ($modalStep > 1)
                            <button wire:click="modalAnterior" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                &larr; Anterior
                            </button>
                        @endif
                        @if (count($modalEtapas) > 0 && $modalStep <= count($modalEtapas))
                            <button wire:click="modalSiguiente" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200">
                                Siguiente &rarr;
                            </button>
                        @else
                            <button wire:click="confirmarEnvio" class="inline-flex items-center rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600">
                                Confirmar envío
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>
