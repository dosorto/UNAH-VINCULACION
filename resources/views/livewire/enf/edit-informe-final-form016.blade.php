@php
    $steps = [
        1 => 'Info general',
        2 => 'Equipo',
        3 => 'Participantes',
        4 => 'Estadisticas',
        5 => 'Acreditacion',
        6 => 'Ejecucion',
        7 => 'Evaluacion',
        8 => 'Anexos y cierre',
    ];
    $input = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white';
    $readonly = $input.' bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    $label = 'mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300';
    $button = 'inline-flex items-center rounded-md px-4 py-2 text-sm font-medium';
    $certificado = $accion->certificado;
    $lugar = $accion->lugaresEjecucion->first();
    $coordinador = $accion->equipo->firstWhere('rol', 'Coordinador de la accion') ?: $accion->equipo->firstWhere('es_coordinador', true);
    $docentes = $accion->equipo->whereIn('rol', ['Docente UNAH', 'Consultor nacional', 'Consultor internacional'])->values();
@endphp

<div class="text-gray-900 dark:text-gray-100">
    <header class="mb-4 px-1">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">FORM-DVUS-016</p>
                <h1 class="mt-1 text-xl font-bold">Informe final de certificado universitario / Educacion No Formal</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $accion->nombre_accion }} &middot; {{ $certificado?->codigo_certificado ?: ($accion->numero_registro ?: 'Pendiente de registro') }}</p>
                <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-400">Los datos base se precargan desde el FORM-DVUS-016. Complete la informacion real de cierre, acreditacion, ejecucion y evaluacion.</p>
            </div>
            <div class="flex flex-col items-start gap-2 sm:items-end">
                <a href="{{ route('proyectosDocente') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-200">Volver al selector</a>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ ($general['estado'] ?? '') === 'completo' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ strtoupper($general['estado'] ?? 'BORRADOR') }}</span>
            </div>
        </div>
        @if($mensaje)<div class="mt-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ $mensaje }}</div>@endif
        @if($errors->any())<div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><p class="font-semibold">Revise los datos senalados.</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    </header>

    <nav aria-label="Pasos del informe final ENF" class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-900">
        <div class="flex items-center overflow-x-auto gap-0.5">
            @foreach($steps as $step => $name)
                @php($complete = $this->isStepComplete($step))
                <button type="button" wire:click="goToStep({{ $step }})" @if($currentStep === $step) aria-current="step" @endif class="group flex min-w-[50px] flex-1 flex-col items-center rounded-md p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <span class="mb-1 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $currentStep === $step ? 'bg-blue-600 text-white ring-2 ring-blue-300' : ($complete ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300') }}">{!! $complete ? '&check;' : $step !!}</span>
                    <span class="hidden text-center text-[10px] leading-tight sm:block {{ $currentStep === $step ? 'font-semibold text-blue-600' : ($complete ? 'text-green-600' : 'text-gray-500') }}">{{ $name }}</span>
                </button>
                @if($step < count($steps))<div class="h-0.5 w-3 shrink-0 {{ $complete ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>@endif
            @endforeach
        </div>
    </nav>

    <main class="rounded-lg bg-white p-6 shadow dark:bg-gray-900">
        @if($currentStep === 1)
            <h2 class="mb-6 text-lg font-semibold">Paso 1: Informacion general del certificado</h2>
            <p class="-mt-4 mb-5 text-xs text-gray-500"><span class="font-medium text-blue-600">Datos precargados.</span> Estos campos vienen del FORM-DVUS-016 y sirven como base del cierre.</p>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="{{ $label }}">Fecha de elaboracion</label><input type="date" wire:model.live="general.fecha_presentacion" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Codigo del certificado</label><input value="{{ $certificado?->codigo_certificado ?: 'Pendiente' }}" class="{{ $readonly }}" readonly></div>
                <div class="md:col-span-2"><label class="{{ $label }}">Nombre de la accion</label><input value="{{ $accion->nombre_accion }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Tipo de certificado</label><input value="{{ $certificado?->tipoCertificado?->nombre }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Figura de acreditacion</label><input wire:model.live.debounce.600ms="general.modalidad_acreditacion" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Unidad academica responsable</label><input value="{{ $accion->unidad_academica_responsable_texto ?: $accion->centroFacultad?->nombre }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Escuela / Departamento</label><input value="{{ $accion->escuela_departamento_texto ?: $accion->departamentoAcademico?->nombre }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Fecha inicio</label><input value="{{ $accion->fecha_inicio?->format('d/m/Y') }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Fecha finalizacion</label><input value="{{ $accion->fecha_finalizacion?->format('d/m/Y') }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Total horas</label><input value="{{ $accion->total_horas ?: ($accion->horas_teoricas + $accion->horas_practicas) }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Modalidad / lugar</label><input value="{{ collect([$lugar?->modalidad_ejecucion ?: $accion->modalidad?->nombre, $lugar?->nombre_lugar])->filter()->implode(' / ') }}" class="{{ $readonly }}" readonly></div>
                <div class="md:col-span-2"><label class="{{ $label }}">Resumen ejecutivo</label><textarea rows="4" wire:model.live.debounce.1000ms="general.resumen_ejecutivo" class="{{ $input }}"></textarea></div>
            </div>
        @elseif($currentStep === 2)
            <h2 class="mb-6 text-lg font-semibold">Paso 2: Equipo docente y coordinacion</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="{{ $label }}">Coordinador de la accion</label><input value="{{ $coordinador?->nombre_completo ?: 'Sin coordinador definido' }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Correo del coordinador</label><input value="{{ $coordinador?->correo ?: 'Pendiente' }}" class="{{ $readonly }}" readonly></div>
            </div>
            <h3 class="mt-7 font-semibold">Equipo docente precargado</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-2 text-left">Nombre</th><th class="px-3 py-2 text-left">Correo</th><th class="px-3 py-2 text-left">Participacion</th><th class="px-3 py-2 text-left">Universidad / pais</th></tr></thead>
                    <tbody>@forelse($docentes as $row)<tr class="border-t dark:border-gray-700"><td class="px-3 py-2">{{ $row->nombre_completo }}</td><td class="px-3 py-2">{{ $row->correo }}</td><td class="px-3 py-2">{{ $row->perfil_docente ?: $row->rol }}</td><td class="px-3 py-2">{{ collect([$row->universidad_procedencia, $row->pais_procedencia])->filter()->implode(' / ') }}</td></tr>@empty<tr><td colspan="4" class="p-3 text-gray-500">Sin docentes registrados.</td></tr>@endforelse</tbody>
                </table>
            </div>
        @elseif($currentStep === 3)
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Paso 3: Participantes que finalizaron y obtuvieron acreditacion</h2>
                <button type="button" wire:click="agregarFila('participantes')" class="{{ $button }} bg-blue-50 text-blue-700">Agregar participante</button>
            </div>
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre','Documento/telefono','Correo','Sexo','Edad','Certificado','Codigo',''] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead>
                    <tbody>
                        @forelse($participantes as $i => $row)
                            <tr class="border-t dark:border-gray-700">
                                <td class="px-3 py-2"><input wire:model.live.debounce.800ms="participantes.{{ $i }}.nombre_completo" class="{{ $input }} min-w-48"></td>
                                <td class="px-3 py-2"><input wire:model.live.debounce.800ms="participantes.{{ $i }}.documento_identidad" class="{{ $input }} min-w-36"></td>
                                <td class="px-3 py-2"><input type="email" wire:model.live.debounce.800ms="participantes.{{ $i }}.correo" class="{{ $input }} min-w-44"></td>
                                <td class="px-3 py-2"><select wire:model.live="participantes.{{ $i }}.sexo" class="{{ $input }} min-w-32"><option value="">Sin definir</option><option>Masculino</option><option>Femenino</option></select></td>
                                <td class="px-3 py-2"><input type="number" min="0" wire:model.live="participantes.{{ $i }}.edad" class="{{ $input }} w-24"></td>
                                <td class="px-3 py-2"><input type="checkbox" wire:model.live="participantes.{{ $i }}.certificado_emitido" class="rounded border-gray-300 text-blue-600"></td>
                                <td class="px-3 py-2"><input wire:model.live.debounce.800ms="participantes.{{ $i }}.codigo_certificado" class="{{ $input }} min-w-36"></td>
                                <td class="px-3 py-2"><button type="button" wire:click="quitarFila('participantes', {{ $i }})" class="text-red-600">Quitar</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-3 text-gray-500">Agregue los participantes finales del certificado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($currentStep === 4)
            <h2 class="mb-6 text-lg font-semibold">Paso 4: Resumen estadistico de participantes</h2>
            <div class="mb-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>Programados FORM 16</strong><p>Hombres: {{ $this->beneficiariosProgramados['hombres'] }}</p><p>Mujeres: {{ $this->beneficiariosProgramados['mujeres'] }}</p><p>Total: {{ $this->beneficiariosProgramados['total'] }}</p></div>
                <div class="rounded bg-blue-50 p-3 text-sm"><strong>Inscritos reales</strong><p>Hombres: {{ $this->resumenParticipantes['inscritos']['hombres'] }}</p><p>Mujeres: {{ $this->resumenParticipantes['inscritos']['mujeres'] }}</p><p>Total: {{ $this->resumenParticipantes['inscritos']['total'] }}</p></div>
                <div class="rounded bg-green-50 p-3 text-sm"><strong>Aprobaron</strong><p>Hombres: {{ $this->resumenParticipantes['aprobaron']['hombres'] }}</p><p>Mujeres: {{ $this->resumenParticipantes['aprobaron']['mujeres'] }}</p><p>Total: {{ $this->resumenParticipantes['aprobaron']['total'] }}</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-2 text-left">Concepto</th><th class="px-3 py-2">Hombres</th><th class="px-3 py-2">Mujeres</th><th class="px-3 py-2">Total</th></tr></thead>
                    <tbody>
                        @foreach(['inscritos'=>'Personas matriculadas / inscritas','no_presentaron'=>'No se presentaron','abandonaron'=>'Abandonaron','reprobaron'=>'Reprobaron','aprobaron'=>'Aprobaron / participaron en toda la actividad','graduados_unah'=>'Graduados UNAH que aprobaron'] as $key => $name)
                            <tr class="border-t dark:border-gray-700"><td class="px-3 py-2 font-medium">{{ $name }}</td><td class="px-3 py-2"><input type="number" min="0" wire:model.live="general.{{ $key }}_hombres" class="{{ $input }} w-28"></td><td class="px-3 py-2"><input type="number" min="0" wire:model.live="general.{{ $key }}_mujeres" class="{{ $input }} w-28"></td><td class="px-3 py-2 text-center font-semibold">{{ $this->resumenParticipantes[$key]['total'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($currentStep === 5)
            <h2 class="mb-6 text-lg font-semibold">Paso 5: Desarrollo academico y acreditacion</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="{{ $label }}">Figura de acreditacion utilizada</label><input wire:model.live.debounce.600ms="general.modalidad_acreditacion" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Carreras relacionadas</label><input value="{{ $certificado?->carreras?->pluck('carrera.nombre')->filter()->implode(', ') ?: 'No registradas' }}" class="{{ $readonly }}" readonly></div>
                <div class="md:col-span-2"><label class="{{ $label }}">Cambios en contenido curricular o metodologia</label><textarea rows="3" wire:model.live.debounce.1000ms="general.contenido_curricular_cambios" class="{{ $input }}"></textarea></div>
                <div class="md:col-span-2"><label class="{{ $label }}">Cambios en cronograma de desarrollo</label><textarea rows="3" wire:model.live.debounce.1000ms="general.cronograma_cambios" class="{{ $input }}"></textarea></div>
                <div class="md:col-span-2"><label class="{{ $label }}">Seguimiento / sistematizacion</label><textarea rows="3" wire:model.live.debounce.1000ms="general.seguimiento_sistematizacion" class="{{ $input }}"></textarea></div>
            </div>
            <h3 class="mt-7 font-semibold">Espacios de aprendizaje</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-2 text-left">Espacio</th><th class="px-3 py-2 text-left">Codigo</th><th class="px-3 py-2 text-left">Creditos</th><th class="px-3 py-2 text-left">Horas</th></tr></thead><tbody>@forelse($accion->espaciosAprendizaje as $espacio)<tr class="border-t dark:border-gray-700"><td class="px-3 py-2">{{ $espacio->nombre }}</td><td class="px-3 py-2">{{ $espacio->codigo ?: '-' }}</td><td class="px-3 py-2">{{ $espacio->creditos }}</td><td class="px-3 py-2">{{ $espacio->horas }}</td></tr>@empty<tr><td colspan="4" class="p-3 text-gray-500">Sin espacios de aprendizaje registrados.</td></tr>@endforelse</tbody></table>
            </div>
        @elseif($currentStep === 6)
            <h2 class="mb-6 text-lg font-semibold">Paso 6: Ejecucion, resultados y reflexion</h2>
            <div class="grid gap-4">
                <div><label class="{{ $label }}">Resultados obtenidos</label><textarea rows="4" wire:model.live.debounce.1000ms="general.resultados_obtenidos" class="{{ $input }}"></textarea></div>
            </div>
            <div class="mt-7 flex items-center justify-between"><h3 class="font-semibold">Acciones ejecutadas</h3><button type="button" wire:click="agregarFila('accionesEjecutadas')" class="{{ $button }} bg-blue-50 text-blue-700">Agregar accion</button></div>
            <div class="mt-3 space-y-3">@foreach($accionesEjecutadas as $i => $row)<div class="grid gap-3 rounded border p-3 dark:border-gray-700 md:grid-cols-5"><div class="md:col-span-2"><label class="{{ $label }}">Actividad</label><input wire:model.live.debounce.800ms="accionesEjecutadas.{{ $i }}.actividad" class="{{ $input }}"></div><div><label class="{{ $label }}">Inicio</label><input type="date" wire:model.live="accionesEjecutadas.{{ $i }}.fecha_inicio" class="{{ $input }}"></div><div><label class="{{ $label }}">Finalizacion</label><input type="date" wire:model.live="accionesEjecutadas.{{ $i }}.fecha_finalizacion" class="{{ $input }}"></div><button type="button" wire:click="quitarFila('accionesEjecutadas', {{ $i }})" class="self-end text-sm text-red-600">Quitar</button><div class="md:col-span-5"><label class="{{ $label }}">Resultados / observaciones</label><textarea rows="2" wire:model.live.debounce.1000ms="accionesEjecutadas.{{ $i }}.resultados" class="{{ $input }}"></textarea></div></div>@endforeach</div>
            <div class="mt-7 flex items-center justify-between"><h3 class="font-semibold">Acciones no ejecutadas</h3><button type="button" wire:click="agregarFila('accionesNoEjecutadas')" class="{{ $button }} bg-blue-50 text-blue-700">Agregar accion</button></div>
            <div class="mt-3 space-y-3">@foreach($accionesNoEjecutadas as $i => $row)<div class="grid gap-3 rounded border p-3 dark:border-gray-700 md:grid-cols-4"><div><label class="{{ $label }}">Actividad</label><input wire:model.live.debounce.800ms="accionesNoEjecutadas.{{ $i }}.actividad" class="{{ $input }}"></div><div><label class="{{ $label }}">Motivo</label><input wire:model.live.debounce.800ms="accionesNoEjecutadas.{{ $i }}.motivo" class="{{ $input }}"></div><div><label class="{{ $label }}">Fecha reprogramacion</label><input type="date" wire:model.live="accionesNoEjecutadas.{{ $i }}.fecha_reprogramacion" class="{{ $input }}"></div><button type="button" wire:click="quitarFila('accionesNoEjecutadas', {{ $i }})" class="self-end text-sm text-red-600">Quitar</button><div class="md:col-span-4"><label class="{{ $label }}">Acciones correctivas / afectacion</label><textarea rows="2" wire:model.live.debounce.1000ms="accionesNoEjecutadas.{{ $i }}.acciones_correctivas" class="{{ $input }}"></textarea></div></div>@endforeach</div>
            <h3 class="mt-7 font-semibold">Reflexion</h3>
            <div class="mt-3 grid gap-4 md:grid-cols-2">
                @foreach(['dificultades'=>'Dificultades','lecciones_aprendidas'=>'Lecciones aprendidas','buenas_practicas'=>'Buenas practicas','transformacion_lograda'=>'Transformacion lograda','desafios'=>'Desafios','respuesta_reforma_universitaria'=>'Respuesta a lo esencial de la reforma','conclusiones'=>'Conclusiones','recomendaciones'=>'Recomendaciones'] as $field => $name)
                    <div><label class="{{ $label }}">{{ $name }}</label><textarea rows="3" wire:model.live.debounce.1000ms="general.{{ $field }}" class="{{ $input }}"></textarea></div>
                @endforeach
            </div>
        @elseif($currentStep === 7)
            <h2 class="mb-6 text-lg font-semibold">Paso 7: Evaluacion y presupuesto</h2>
            <h3 class="font-semibold">Valoracion por beneficiarios</h3>
            <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div><label class="{{ $label }}">Total beneficiarios</label><input type="number" min="0" wire:model.live="general.valoracion_total_beneficiarios" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Muestra</label><input type="number" min="0" wire:model.live="general.valoracion_muestra" class="{{ $input }}"></div>
                @foreach(['excelente'=>'Excelente','muy_buena'=>'Muy buena','regular'=>'Regular','mala'=>'Mala'] as $field => $name)
                    <div><label class="{{ $label }}">{{ $name }}</label><input type="number" min="0" wire:model.live="general.valoracion_{{ $field }}" class="{{ $input }}"><p class="mt-1 text-xs text-gray-500">{{ $this->porcentajesValoracion[$field] }}%</p></div>
                @endforeach
            </div>
            <h3 class="mt-7 font-semibold">Presupuesto precargado desde FORM-DVUS-016</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach(['ingresos'=>'Ingresos','egresos'=>'Egresos','excedente'=>'Excedente','aporte_unah'=>'Aporte UNAH'] as $field => $name)
                    <div class="rounded bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">{{ $name }}</p><p class="mt-1 font-semibold">L {{ number_format($this->totalesPresupuesto[$field], 2) }}</p></div>
                @endforeach
            </div>
            <div class="mt-7"><label class="{{ $label }}">Observaciones de evaluacion</label><textarea rows="4" wire:model.live.debounce.1000ms="general.observaciones_finales" class="{{ $input }}"></textarea></div>
        @else
            <h2 class="mb-6 text-lg font-semibold">Paso 8: Anexos y cierre</h2>
            <section class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="font-semibold">Documentos generales</h3>
                        <p class="mt-1 text-xs text-gray-500">Respaldos, listados, actas, evidencias, certificaciones y documentos cargados desde el FORM-DVUS-016.</p>
                    </div>
                    <button type="button" wire:click="agregarFila('anexos')" class="{{ $button }} bg-blue-50 text-blue-700">Agregar documento</button>
                </div>
                <div class="mt-3 space-y-3">
                    @forelse($this->documentosAnexos as $doc)
                        @php($i = $doc['indice_formulario'])
                        <div class="rounded border border-gray-200 p-3 dark:border-gray-700">
                            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-6">
                                <div>
                                    <label class="{{ $label }}">Tipo</label>
                                    <select wire:model.live="anexos.{{ $i }}.tipo_documento" class="{{ $input }}">
                                        @if(! in_array($doc['tipo_documento'] ?? '', ['materiales','encuestas','procesamiento','videos','difusion','asistencia','actas','certificacion','otros'], true))
                                            <option value="{{ $doc['tipo_documento'] }}">{{ str($doc['tipo_documento'])->replace('_', ' ')->title() }}</option>
                                        @endif
                                        <option value="materiales">Materiales generados</option>
                                        <option value="encuestas">Formularios de encuesta</option>
                                        <option value="procesamiento">Informes de procesamiento</option>
                                        <option value="videos">Videos</option>
                                        <option value="difusion">Evidencias de difusion</option>
                                        <option value="asistencia">Listas de asistencia</option>
                                        <option value="actas">Actas</option>
                                        <option value="certificacion">Certificaciones</option>
                                        <option value="otros">Otros</option>
                                    </select>
                                </div>
                                <div class="lg:col-span-2"><label class="{{ $label }}">Nombre</label><input wire:model.live.debounce.800ms="anexos.{{ $i }}.nombre" class="{{ $input }}"></div>
                                <div class="lg:col-span-2"><label class="{{ $label }}">Descripcion</label><input wire:model.live.debounce.800ms="anexos.{{ $i }}.descripcion" class="{{ $input }}"></div>
                                <div>
                                    <label class="{{ $label }}">Archivo</label>
                                    <input type="file" wire:model="anexoArchivos.{{ $i }}" class="{{ $input }}">
                                    @if($this->anexoUrl($doc['ruta'] ?? null))
                                        <a href="{{ $this->anexoUrl($doc['ruta']) }}" target="_blank" rel="noopener" class="mt-1 block text-xs text-blue-700">Ver documento</a>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                                <span class="rounded-full px-2 py-1 font-medium {{ ! empty($doc['ruta']) && $doc['ruta'] !== 'pendiente' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ ! empty($doc['ruta']) && $doc['ruta'] !== 'pendiente' ? 'Adjunto' : 'Pendiente' }}</span>
                                <span>{{ ! empty($doc['tamano_bytes']) ? number_format($doc['tamano_bytes'] / 1024, 1).' KB' : 'Sin tamano registrado' }}</span>
                                <button type="button" wire:click="quitarFila('anexos', {{ $i }})" class="text-sm text-red-600">Quitar</button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">No hay anexos registrados. Puede agregar documentos de respaldo para el informe final.</div>
                    @endforelse
                </div>
            </section>

            <section class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="mb-4 font-semibold">Fotografias del certificado</h3>
                <x-forms.image-dropzone model="fotografiasTemporales" id="form016-fotografias" />
                @if($this->fotografias)
                    <h4 class="mt-6 text-sm font-semibold">Fotografias guardadas</h4>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($this->fotografias as $foto)
                            <article class="overflow-hidden rounded-lg border dark:border-gray-700">
                                @if($this->anexoUrl($foto['ruta'] ?? null))
                                    <img src="{{ $this->anexoUrl($foto['ruta']) }}" alt="{{ $foto['descripcion'] ?: 'Fotografia '.$foto['nombre'] }}" class="h-36 w-full object-cover">
                                @endif
                                <div class="space-y-2 p-3 text-xs">
                                    <div class="flex items-center justify-between gap-2"><p class="truncate font-medium" title="{{ $foto['nombre'] }}">{{ $foto['nombre'] ?: 'Fotografia' }}</p><span class="rounded-full bg-green-100 px-2 py-1 text-[10px] font-medium text-green-800">Guardada</span></div>
                                    <p class="text-gray-500">{{ ! empty($foto['tamano_bytes']) ? number_format($foto['tamano_bytes'] / 1024, 1).' KB' : 'Tamano no disponible' }} · {{ $foto['created_at'] ?: 'Sin fecha' }}</p>
                                    <input wire:model.live.debounce.1000ms="anexos.{{ $foto['indice_formulario'] }}.descripcion" aria-label="Descripcion de {{ $foto['nombre'] ?: 'la fotografia' }}" placeholder="Descripcion opcional" class="{{ $input }}">
                                    <div class="flex gap-3">
                                        @if($this->anexoUrl($foto['ruta'] ?? null))
                                            <a href="{{ $this->anexoUrl($foto['ruta']) }}" target="_blank" rel="noopener" class="text-blue-700">Ver</a>
                                            <a href="{{ $this->anexoUrl($foto['ruta']) }}" download class="text-blue-700">Descargar</a>
                                        @endif
                                        <button type="button" wire:click="quitarFotografia({{ $foto['id'] }})" wire:confirm="Quitar esta fotografia del informe final?" class="text-red-600">Quitar</button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Evidencias del certificado</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Espacios de aprendizaje</p><p class="mt-1 font-semibold">{{ $accion->espaciosAprendizaje->count() }}</p></div>
                    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Docentes registrados</p><p class="mt-1 font-semibold">{{ $docentes->count() }}</p></div>
                    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Participantes finales</p><p class="mt-1 font-semibold">{{ collect($participantes)->filter(fn ($row) => filled($row['nombre_completo'] ?? null))->count() }}</p></div>
                    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Acciones ejecutadas</p><p class="mt-1 font-semibold">{{ collect($accionesEjecutadas)->filter(fn ($row) => filled($row['actividad'] ?? null))->count() }}</p></div>
                </div>
            </section>

            <section class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Cierre</h3>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div><label class="{{ $label }}">Fecha de cierre</label><input type="date" wire:model.live="general.fecha_aprobacion" class="{{ $input }}"></div>
                    <div class="sm:col-span-2"><label class="{{ $label }}">Observaciones finales</label><textarea rows="4" wire:model.live.debounce.1000ms="general.observaciones_finales" class="{{ $input }}"></textarea></div>
                    <label class="flex items-start gap-2"><input type="checkbox" wire:model.live="general.confirmacion_veracidad" class="mt-1 rounded border-gray-300 text-blue-600"><span class="text-sm">Confirmo que la informacion consignada en el informe final FORM-DVUS-016 es veraz.</span></label>
                </div>
            </section>

            <div class="mt-6 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">Al validar el informe final, el envio al flujo queda disponible en el detalle de la accion ENF.</div>

            <section class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Resumen final del informe</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach($this->resumenRevision as $name => $value)<div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">{{ $name }}</p><p class="mt-1 font-semibold">{{ $value }}</p></div>@endforeach</div>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"><strong>Campos pendientes</strong>@if($this->camposPendientes)<ul class="mt-2 list-disc pl-5">@foreach($this->camposPendientes as $pending)<li>{{ $pending }}</li>@endforeach</ul>@else<p class="mt-2">No hay campos esenciales pendientes.</p>@endif</div>
                    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-900"><strong>Inconsistencias detectadas</strong>@if($this->inconsistenciasRevision)<ul class="mt-2 list-disc pl-5">@foreach($this->inconsistenciasRevision as $issue)<li>{{ $issue }}</li>@endforeach</ul>@else<p class="mt-2">No se detectaron inconsistencias.</p>@endif</div>
                </div>
            </section>
        @endif

        <div class="mt-8 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div>@if($currentStep > 1)<button type="button" wire:click="anterior" class="{{ $button }} border border-gray-300 bg-white text-gray-700">Anterior</button>@else<a href="{{ route('enf.acciones.show', $accion) }}" class="{{ $button }} border border-gray-300 bg-white text-gray-700">Volver</a>@endif</div>
            <div class="flex flex-wrap items-center justify-end gap-3">
                <span class="min-w-[78px] text-right text-xs">@if($estadoGuardado === 'guardando')Guardando...@elseif($estadoGuardado === 'error')<span class="text-red-600">Error al guardar</span>@else<span class="text-green-600">Guardado</span>@endif</span>
                <button type="button" wire:click="guardarBorrador" class="{{ $button }} border border-gray-300 bg-white text-gray-700">Guardar borrador</button>
                @if($currentStep < count($steps))
                    <button type="button" wire:click="siguiente" class="{{ $button }} bg-blue-600 text-white">Siguiente</button>
                @else
                    <a target="_blank" href="{{ route('enf.acciones.informe-final.preview-pdf', $accion) }}" class="{{ $button }} border border-blue-300 text-blue-700">Vista previa</a>
                    <a href="{{ route('enf.acciones.informe-final.pdf', $accion) }}" class="{{ $button }} border border-blue-300 text-blue-700">Descargar PDF</a>
                    <button type="button" wire:click="validarInforme" wire:confirm="Confirma que desea marcar completo el informe final FORM-DVUS-016?" class="{{ $button }} bg-green-700 text-white">Validar informe</button>
                    @if(($general['estado'] ?? null) === 'completo' || ($general['estado'] ?? null) === 'SUBSANACION')
                        <a href="{{ route('enf.acciones.show', $accion) }}" class="{{ $button }} bg-emerald-700 text-white">Ir a envio</a>
                    @endif
                @endif
            </div>
        </div>
    </main>
</div>
