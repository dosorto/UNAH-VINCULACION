<div class="text-gray-900 dark:text-gray-100">
    <div class="mb-6 flex flex-wrap items-center gap-2 border-b border-gray-200 pb-3 text-sm font-semibold text-slate-600">
        <a href="{{ route('selectorTipoAccion') }}" class="rounded-md px-4 py-2 transition hover:bg-gray-100 hover:text-blue-700">Registrar Acción</a>
        <a href="{{ route('proyectosDocente') }}" class="rounded-md px-4 py-2 transition hover:bg-gray-100 hover:text-blue-700">Mi Historial Vinculación</a>
        <a href="{{ route('fichasActualizacionVinculacion') }}" class="rounded-md px-4 py-2 transition hover:bg-gray-100 hover:text-blue-700">Mis Fichas de Actualización</a>
        <a href="{{ route('proyectosAntesDelSistema') }}" class="rounded-md px-4 py-2 transition hover:bg-gray-100 hover:text-blue-700">Vinculaciones Antes del Sistema</a>
    </div>
    <div class="mb-5"><p class="text-xs font-semibold uppercase tracking-wide text-blue-700">FORM-DVUS-013</p><h1 class="mt-1 text-2xl font-bold text-gray-950">Registro de Pasantías</h1><p class="mt-2 text-sm text-gray-500">Complete la información del formulario y guárdela como borrador.</p></div>
    @php
        $inputClass = 'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
        $selectOptions = [
            'tipo_pasantia' => ['Pasantía profesional' => 'Pasantía profesional', 'Pasantía académica' => 'Pasantía académica'],
            'modalidad_ejecucion' => ['100% presencial' => '100% presencial', 'Híbrida' => 'Híbrida', 'Teletrabajo' => 'Teletrabajo'],
            'pasantia_obligatoria' => ['Sí' => 'Sí', 'No' => 'No'],
            'otorga_creditos' => ['Sí' => 'Sí', 'No' => 'No'],
            'pasantia_remunerada' => ['Sí' => 'Sí', 'No' => 'No'],
            'tipo_institucion' => ['Pública' => 'Pública', 'Privada' => 'Privada', 'ONG' => 'ONG', 'Organismo internacional' => 'Organismo internacional'],
            'sector_institucion' => ['Educación' => 'Educación', 'Gobierno' => 'Gobierno', 'Empresa privada' => 'Empresa privada', 'Sociedad civil' => 'Sociedad civil'],
            'tipo_instrumento' => ['carta_formal_solicitud' => 'Carta formal de solicitud', 'carta_intenciones' => 'Carta de intenciones', 'convenio_marco' => 'Convenio marco'],
            'grado_academico_contacto_directo' => ['Secundaria completa' => 'Secundaria completa', 'Licenciatura' => 'Licenciatura', 'Maestría' => 'Maestría', 'Doctorado' => 'Doctorado', 'Postdoctorado' => 'Postdoctorado'],
            'adjunta_carta_formalizacion' => ['Sí' => 'Sí', 'No' => 'No'],
            'adjunta_convenio_marco' => ['Sí' => 'Sí', 'No' => 'No'],
            'categoria_docente' => $categoriasDocente,
            'departamento_docente' => $departamentosAcademicos,
            'jornada_laboral_docente' => $jornadasLaborales,
        ];
    @endphp

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-center gap-0.5 overflow-x-auto">

        @foreach($pasos as $numero => $nombre)
            @php $completado = $this->isStepComplete($numero); $activo = $pasoActual === $numero; $accesible = $this->canAccessStep($numero); @endphp
            <button type="button" wire:click="irAPaso({{ $numero }})" aria-disabled="{{ $accesible ? 'false' : 'true' }}" class="group flex min-w-[82px] flex-1 shrink-0 flex-col items-center rounded-md p-1 transition hover:bg-gray-50 dark:hover:bg-white/5 {{ $accesible ? '' : 'cursor-not-allowed opacity-60' }}">
                <span class="mb-1 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold transition-colors {{ $activo ? 'bg-blue-600 text-white ring-2 ring-blue-300' : ($completado ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">{{ $completado ? '✓' : $numero }}</span>
                <span class="text-center text-[10px] leading-tight {{ $activo ? 'font-semibold text-blue-600' : ($completado ? 'text-green-600 dark:text-green-400' : 'text-gray-500') }}">{{ $nombre }}</span>
            </button>
            @if($numero < count($pasos))<div class="h-0.5 w-3 shrink-0 {{ $completado ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>@endif
        @endforeach
        </div>
    </div>

    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
        @php
            $secciones = [
                1 => [['fecha_registro','Fecha de registro','date'],['facultad_centro','Facultad / centro','text'],['escuela_departamento','Escuela / departamento académico','text'],['carrera','Carrera','text'],['numero_cuenta','Número de cuenta','text'],['nombre_estudiante','Nombre completo del estudiante','text'],['celular_estudiante','Número de celular','text'],['correo_institucional','Correo institucional','email'],['correo_personal','Correo personal','email']],
                2 => [['tipo_pasantia','Tipo de pasantía','text'],['fecha_inicio','Fecha de inicio','date'],['fecha_finalizacion','Fecha de finalización','date'],['duracion_semanas','Duración en semanas','number'],['total_horas','Total de horas programadas','number'],['horas_semanales','Promedio de horas semanales','number'],['cantidad_creditos','Cantidad de créditos académicos','number'],['modalidad_ejecucion','Modalidad de ejecución','text'],['pasantia_obligatoria','Pasantía obligatoria (Sí/No)','text'],['otorga_creditos','Otorgamiento de créditos (Sí/No)','text']],
                3 => [['descripcion_experiencia','Descripción de la experiencia y resultados','textarea'],['descripcion_cargo','Descripción del cargo','textarea'],['resumen_responsabilidades','Responsabilidades y tareas','textarea'],['area_departamento','Departamento o área','text'],['area_conocimiento','Área de conocimiento','text'],['codigo_asignatura','Código de asignatura','text'],['nombre_asignatura','Nombre de asignatura','text'],['descripcion_conocimientos_teoricos','Conocimientos teóricos','textarea'],['habilidades_desarrollar','Habilidades a desarrollar','textarea'],['pasantia_remunerada','Pasantía remunerada (Sí/No)','text'],['monto_remuneracion','Monto de remuneración','number']],
                4 => [['nombre_institucion','Nombre de la institución / organización','text'],['direccion_institucion','Dirección de la sede principal','textarea'],['ciudad_institucion','Ciudad','text'],['pais_institucion','País','text'],['representante_legal','Representante legal','text'],['telefono_representante','Teléfono','text'],['correo_rrhh','Correo de recursos humanos','email'],['tipo_institucion','Tipo de institución','text'],['sector_institucion','Sector','text'],['compromisos_institucion','Compromisos institucionales','textarea']],
                5 => [['nombre_contacto_directo','Nombre del contacto directo','text'],['celular_contacto_directo','Celular del contacto','text'],['correo_contacto_directo','Correo del contacto','email'],['cargo_contacto_directo','Cargo','text'],['grado_academico_contacto_directo','Grado académico','text'],['tipo_instrumento','Instrumento de formalización','text']],
                6 => [['nombre_docente_supervisor','Nombre del docente supervisor','text'],['numero_empleado_docente','Número de empleado','text'],['celular_docente','Celular','text'],['correo_docente','Correo electrónico','email'],['categoria_docente','Categoría','text'],['departamento_docente','Departamento','text'],['jornada_laboral_docente','Jornada laboral','text'],['ubicacion_cubiculo_docente','Ubicación del cubículo','text']],
                7 => [['nombre_firma_coordinador','Nombre del coordinador','text'],['firma_coordinador','Ruta de firma del coordinador','text'],['nombre_firma_supervisor','Nombre del supervisor','text'],['firma_supervisor','Ruta de firma del supervisor','text'],['nombre_firma_estudiante','Nombre del estudiante firmante','text'],['firma_estudiante','Ruta de firma del estudiante','text']],
                8 => [['adjunta_carta_formalizacion','Adjunta carta de formalización (Sí/No)','text'],['archivo_carta_formalizacion','Archivo de carta','text'],['adjunta_convenio_marco','Adjunta convenio marco (Sí/No)','text'],['archivo_convenio_marco','Archivo de convenio','text']],
            ];
        @endphp
        <h2 class="mb-5 text-lg font-semibold text-gray-900">Paso {{ $pasoActual }}: {{ $pasos[$pasoActual] }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @if($pasoActual === 7)
            <div class="md:col-span-2 rounded-lg border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-100">
                Las firmas se asignan y registran mediante el flujo de revisión. No deben editarse manualmente en este formulario.
            </div>
        @else
        @foreach($secciones[$pasoActual] as $indice => [$campo, $etiqueta, $tipo])
                <label class="block {{ $tipo === 'textarea' ? 'md:col-span-2' : '' }}">
                    <span class="mb-1 block text-sm font-medium text-gray-700">{{ $etiqueta }}</span>
                    <span class="block">
                    @if(isset($selectOptions[$campo]))
                        <select wire:model="form.{{ $campo }}" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($selectOptions[$campo] as $valor => $opcion)
                                <option value="{{ $valor }}">{{ $opcion }}</option>
                            @endforeach
                        </select>
                    @elseif($campo === 'facultad_centro')
                        <select wire:model.live="form.{{ $campo }}" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($facultadesCentros as $nombre)
                                <option value="{{ $nombre }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    @elseif($campo === 'carrera')
                        <select wire:model="form.{{ $campo }}" class="{{ $inputClass }}" @disabled(blank($form['facultad_centro'] ?? '') || $carreras->isEmpty())>
                            <option value="">Seleccione...</option>
                            @foreach($carreras as $nombre)
                                <option value="{{ $nombre }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    @elseif(in_array($campo, ['numero_cuenta', 'numero_empleado_docente'], true))
                        <div x-data="{ cuenta: @js($form[$campo] ?? '') }" class="flex gap-2">
                            <input type="text" wire:model.blur="form.{{ $campo }}" x-model="cuenta" class="{{ $inputClass }}">
                            <button type="button" x-cloak x-show="cuenta.trim().length > 0" wire:click="{{ $campo === 'numero_cuenta' ? 'buscarEstudiante' : 'buscarDocente' }}" wire:loading.attr="disabled" wire:target="{{ $campo === 'numero_cuenta' ? 'buscarEstudiante' : 'buscarDocente' }}" class="inline-flex shrink-0 items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                                <span wire:loading.remove wire:target="{{ $campo === 'numero_cuenta' ? 'buscarEstudiante' : 'buscarDocente' }}">Buscar</span>
                                <span wire:loading wire:target="{{ $campo === 'numero_cuenta' ? 'buscarEstudiante' : 'buscarDocente' }}">Buscando…</span>
                            </button>
                        </div>
                    @elseif($campo === 'archivo_carta_formalizacion')
                        <input type="file" wire:model="cartaFormalizacionArchivo" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700">
                        <span wire:loading wire:target="cartaFormalizacionArchivo" class="mt-1 block text-xs text-blue-600">Cargando archivo...</span>
                        @if(filled($form['archivo_carta_formalizacion'] ?? null))
                            <span class="mt-1 block text-xs text-gray-500">Archivo actual: {{ basename($form['archivo_carta_formalizacion']) }}</span>
                        @endif
                        @error('cartaFormalizacionArchivo')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                    @elseif($campo === 'archivo_convenio_marco')
                        <input type="file" wire:model="convenioMarcoArchivo" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700">
                        <span wire:loading wire:target="convenioMarcoArchivo" class="mt-1 block text-xs text-blue-600">Cargando archivo...</span>
                        @if(filled($form['archivo_convenio_marco'] ?? null))
                            <span class="mt-1 block text-xs text-gray-500">Archivo actual: {{ basename($form['archivo_convenio_marco']) }}</span>
                        @endif
                        @error('convenioMarcoArchivo')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                    @elseif($tipo === 'textarea')
                        <textarea wire:model.blur="form.{{ $campo }}" rows="3" class="{{ $inputClass }}"></textarea>
                    @else
                        <input type="{{ $tipo }}" wire:model.blur="form.{{ $campo }}" class="{{ $inputClass }}">
                    @endif
                    </span>
                    @error('form.'.$campo)<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                </label>
        @endforeach
        @endif
        </div>
        <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
            <button type="button" wire:click="anterior" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" @disabled($pasoActual === 1)>&larr; Anterior</button>
            <div class="flex items-center gap-3"><button type="button" wire:click="guardarBorrador" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Guardar borrador</button><button type="button" wire:click="siguiente" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ $pasoActual === 8 ? 'Finalizar' : 'Siguiente' }} &rarr;</button></div>
        </div>
    </div>
</div>
