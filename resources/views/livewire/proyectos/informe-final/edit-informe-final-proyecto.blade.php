@php
    $steps = [1=>'Info General',2=>'Equipo',3=>'Participantes',4=>'Contrapartes',5=>'Resultados',6=>'Ejecución',7=>'Evaluación',8=>'Anexos'];
    $input = 'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
    $readonly = $input.' bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $button = 'inline-flex items-center px-4 py-2 rounded-md text-sm font-medium';
@endphp

<div class="text-gray-900 dark:text-gray-100">
    <span class="hidden" aria-hidden="true">route('informes-finales.anexos.mostrar') route('informes-finales.anexos.descargar')</span>
    <header class="mb-4 px-1">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">INF-001</p>
                <h1 class="mt-1 text-xl font-bold">Informe final de programas y proyectos de vinculación</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $general['nombre_proyecto'] ?: 'Proyecto sin nombre' }} · {{ $general['numero_registro'] }}</p>
                <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-400">Los datos generales se precargan desde el registro del proyecto. Complete la información correspondiente a la ejecución final.</p>
            </div>
            <span class="self-start rounded-full px-3 py-1 text-xs font-semibold {{ ($general['estado'] ?? '') === 'COMPLETO' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' }}">{{ $general['estado'] ?? 'BORRADOR' }}</span>
        </div>
        @if($mensaje)<div class="mt-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/30 dark:text-green-200">{{ $mensaje }}</div>@endif
        @if($errors->any())<div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200"><p class="font-semibold">Revise los datos señalados.</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    </header>

    <nav aria-label="Pasos del informe final" class="mb-6 bg-white dark:bg-gray-900 shadow rounded-lg p-4">
        <div class="flex items-center overflow-x-auto gap-0.5">
            @foreach($steps as $step=>$name)
                @php $complete = $this->isStepComplete($step); @endphp
                <button type="button" wire:click="goToStep({{ $step }})" @if($currentStep===$step) aria-current="step" @endif class="flex flex-col items-center flex-1 min-w-[44px] p-1 group rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mb-1 transition-colors {{ $currentStep===$step ? 'bg-blue-600 text-white ring-2 ring-blue-300' : ($complete ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">{{ $complete ? '✓' : $step }}</span>
                    <span class="text-[10px] text-center hidden sm:block leading-tight {{ $currentStep===$step ? 'text-blue-600 font-semibold' : ($complete ? 'text-green-600 dark:text-green-400' : 'text-gray-500') }}">{{ $name }}</span>
                </button>
                @if($step < 8)<div class="h-0.5 w-3 shrink-0 {{ $complete ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>@endif
            @endforeach
        </div>
    </nav>

    <main class="bg-white dark:bg-gray-900 shadow rounded-lg p-6">
        @if($currentStep === 1)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 1: Información general y beneficiarios</h2>
            <p class="-mt-4 mb-5 text-xs text-gray-500 dark:text-gray-400"><span class="font-medium text-blue-600 dark:text-blue-400">Datos precargados.</span> Las fechas permanecen editables para reflejar la ejecución final.</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2"><label class="{{ $label }}">Nombre del programa o proyecto</label><input wire:model="general.nombre_proyecto" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Número de registro</label><input value="{{ $general['numero_registro'] ?: 'Pendiente de asignación' }}" class="{{ $readonly }}" readonly></div>
                <div><label class="{{ $label }}">Fecha de registro</label><input type="date" wire:model="general.fecha_registro" class="{{ $readonly }}" readonly></div>
                @foreach(['facultad_centro'=>'Facultad/Centro','unidad_academica'=>'Unidad académica','departamento_academico'=>'Departamento académico','carrera'=>'Carrera','programa_vinculacion'=>'Programa de vinculación','linea_investigacion'=>'Línea de investigación','modalidad'=>'Modalidad','ejes_prioritarios'=>'Ejes prioritarios','categoria'=>'Categoría'] as $field=>$name)
                    <div class="{{ in_array($field,['nombre_proyecto','linea_investigacion','ejes_prioritarios']) ? 'md:col-span-2' : '' }}"><label class="{{ $label }}">{{ $name }}</label><input wire:model="general.{{ $field }}" class="{{ $readonly }}" readonly></div>
                @endforeach
                <div><label class="{{ $label }}">Fecha de inicio</label><input type="date" wire:model="general.fecha_inicio" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Fecha de finalización</label><input type="date" wire:model="general.fecha_finalizacion" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Duración estimada</label><input value="{{ $informe->duracion_semanas }} semanas" class="{{ $readonly }}" readonly></div>
            </div>
            <h3 class="mt-7 font-semibold">Territorio</h3>
            <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach(['pais'=>'País','region'=>'Región','departamento_territorial'=>'Departamento','municipio'=>'Municipio','aldea_ciudad'=>'Aldea o ciudad','caserio'=>'Caserío'] as $field=>$name)<div><label class="{{ $label }}">{{ $name }}</label><input wire:model="general.{{ $field }}" class="{{ $input }}"></div>@endforeach
            </div>
            <h3 class="mt-7 font-semibold">Beneficiarios directos</h3>
            <div class="mt-5 grid gap-6 lg:grid-cols-3">
                <section><h3 class="font-semibold">Por sexo</h3><div class="mt-3 space-y-3">@foreach(['hombres'=>'Hombres','mujeres'=>'Mujeres'] as $field=>$name)<div><label class="{{ $label }}">{{ $name }}</label><input type="number" min="0" step="1" inputmode="numeric" wire:model.live="beneficiarios.{{ $field }}" class="{{ $input }}">@error("beneficiarios.$field")<p class="mt-1 text-xs text-red-600">Debe ser un número entero (sin decimales ni ceros a la izquierda).</p>@enderror</div>@endforeach</div><p class="mt-3 rounded bg-gray-50 p-2 text-sm dark:bg-gray-800">Total: <strong>{{ $this->totalesBeneficiarios['sexo'] }}</strong></p></section>
                <section><h3 class="font-semibold">Por rango de edad</h3><div class="mt-3 grid grid-cols-2 gap-3">@foreach(['edad_0_10'=>'0–10','edad_11_18'=>'11–18','edad_19_25'=>'19–25','edad_26_35'=>'26–35','edad_36_50'=>'36–50','edad_51_65'=>'51–65','edad_66_80'=>'66–80','edad_81_mas'=>'Mayor de 81'] as $field=>$name)<div><label class="{{ $label }}">{{ $name }}</label><input type="number" min="0" step="1" inputmode="numeric" wire:model.live="beneficiarios.{{ $field }}" class="{{ $input }}">@error("beneficiarios.$field")<p class="mt-1 text-xs text-red-600">Debe ser un número entero (sin decimales ni ceros a la izquierda).</p>@enderror</div>@endforeach</div><p class="mt-3 rounded bg-gray-50 p-2 text-sm dark:bg-gray-800">Total: <strong>{{ $this->totalesBeneficiarios['edad'] }}</strong></p></section>
                <section><h3 class="font-semibold">Por etnia y sexo</h3><div class="mt-3 grid grid-cols-2 gap-3">@foreach(['indigena_hombres'=>'Indígena H','indigena_mujeres'=>'Indígena M','afrodescendiente_hombres'=>'Afrodesc. H','afrodescendiente_mujeres'=>'Afrodesc. M','mestizo_hombres'=>'Mestizo H','mestizo_mujeres'=>'Mestizo M'] as $field=>$name)<div><label class="{{ $label }}">{{ $name }}</label><input type="number" min="0" step="1" inputmode="numeric" wire:model.live="beneficiarios.{{ $field }}" class="{{ $input }}">@error("beneficiarios.$field")<p class="mt-1 text-xs text-red-600">Debe ser un número entero (sin decimales ni ceros a la izquierda).</p>@enderror</div>@endforeach</div><p class="mt-3 rounded bg-gray-50 p-2 text-sm dark:bg-gray-800">Total: <strong>{{ $this->totalesBeneficiarios['etnia'] }}</strong></p></section>
            </div>
            @if(count(array_unique($this->totalesBeneficiarios)) > 1)<p class="mt-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">Advertencia: las distribuciones por sexo, edad y etnia no representan el mismo total.</p>@endif
        @elseif($currentStep === 2)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 2: Equipo ejecutor</h2>
            <h3 class="mt-5 font-semibold">Equipo docente</h3>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre','N.º empleado','Correo','Categoría','Departamento','Horas','Participación','Estado','Acciones'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>@foreach($equipo as $i=>$row)<tr class="border-t dark:border-gray-700 {{ ($row['estado_participacion'] ?? 'activo') === 'activo' ? '' : 'opacity-60' }}"><td class="px-3 py-2">{{ $row['nombre'] }} @if($row['es_coordinador'])<span class="text-xs text-blue-700">Coordinador</span>@endif @if(($row['estado_participacion'] ?? 'activo') !== 'activo')<p class="mt-1 text-xs">{{ $row['observacion_no_participacion'] }}</p>@endif</td><td class="px-3 py-2">{{ $row['numero_empleado'] }}</td><td class="px-3 py-2">{{ $row['correo'] }}</td><td class="px-3 py-2">{{ $row['categoria'] }}</td><td class="px-3 py-2">{{ $row['departamento'] }}</td><td class="px-3 py-2"><input type="number" min="0" step="0.5" wire:model="equipo.{{ $i }}.horas_dedicadas" class="{{ $input }} min-w-24"></td><td class="px-3 py-2"><span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">{{ $row['tipo_participacion'] }}</span></td><td class="px-3 py-2">{{ $this->estadoParticipacionVisual($row['estado_participacion'] ?? 'activo') }}</td><td class="px-3 py-2">@if(($row['estado_participacion'] ?? 'activo') === 'activo')<button type="button" wire:click="openNoParticipacionModal('equipo',{{ $i }})" class="text-sm text-blue-700">Cambiar estado</button>@else<button type="button" wire:click="restaurarParticipante('equipo',{{ $i }})" wire:confirm="¿Restaurar participación?" class="text-sm text-green-700">Restaurar participación</button>@endif</td></tr>@endforeach</tbody></table></div>
            <div class="mt-7 flex items-center justify-between"><h3 class="font-semibold">Cooperación internacional</h3><button type="button" wire:click="agregarFila('cooperacion')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar integrante</button></div>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre','Pasaporte','Correo','País','Universidad','Horas','Estado','Acciones'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>@forelse($cooperacion as $i=>$row)<tr class="border-t dark:border-gray-700 {{ ($row['estado_participacion'] ?? 'activo') === 'activo' ? '' : 'opacity-60' }}">@foreach(['nombre','pasaporte','correo','pais','universidad','horas_dedicadas'] as $field)<td class="px-3 py-2"><input @if($field==='horas_dedicadas') type="number" min="0" @endif wire:model="cooperacion.{{ $i }}.{{ $field }}" class="{{ $input }} min-w-32"></td>@endforeach<td class="px-3 py-2">{{ $this->estadoParticipacionVisual($row['estado_participacion'] ?? 'activo') }}@if(($row['estado_participacion'] ?? 'activo') !== 'activo')<p class="mt-1 text-xs">{{ $row['observacion_no_participacion'] }}</p>@endif</td><td class="px-3 py-2">@if(($row['estado_participacion'] ?? 'activo') === 'activo')<button type="button" wire:click="openNoParticipacionModal('cooperacion',{{ $i }})" class="text-sm text-blue-700">Cambiar estado</button>@else<button type="button" wire:click="restaurarParticipante('cooperacion',{{ $i }})" wire:confirm="¿Restaurar participación?" class="text-sm text-green-700">Restaurar participación</button>@endif</td></tr>@empty<tr><td colspan="8" class="p-3 text-gray-500">No hay cooperación internacional registrada.</td></tr>@endforelse</tbody></table></div>
        @elseif($currentStep === 3)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 3: Estudiantes y voluntarios</h2>
            <h3 class="mt-5 font-semibold">Grupos de estudiantes planificados</h3>
            <div class="mt-3 space-y-5">
                @forelse($this->gruposEstudiantesConRegistro as $grupo)
                    <section class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $grupo['tipo_etiqueta'] }}</h4>
                                @if($grupo['asignatura_etiqueta'])<p class="mt-1 text-sm">Asignatura: <strong>{{ $grupo['asignatura_etiqueta'] }}</strong></p>@endif
                                @if($grupo['periodo_academico'])<p class="mt-1 text-sm">Período académico: <strong>{{ $grupo['periodo_academico'] }}</strong></p>@endif
                            </div>
                            <button type="button" wire:click="openEstudianteModal(null, {{ $grupo['id'] }})" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar estudiante</button>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>Planificados</strong><p>Hombres: {{ $grupo['hombres_planificados'] }}</p><p>Mujeres: {{ $grupo['mujeres_planificadas'] }}</p><p>Total: {{ $grupo['total_planificado'] }}</p></div>
                            <div class="rounded bg-blue-50 p-3 text-sm dark:bg-blue-950/30"><strong>Registrados</strong><p>Hombres: {{ $grupo['hombres_registrados'] }} de {{ $grupo['hombres_planificados'] }}</p><p>Mujeres: {{ $grupo['mujeres_registradas'] }} de {{ $grupo['mujeres_planificadas'] }}</p><p>Total: {{ $grupo['total_registrado'] }} de {{ $grupo['total_planificado'] }}</p></div>
                            <div class="rounded bg-amber-50 p-3 text-sm dark:bg-amber-950/30"><strong>Pendientes</strong><p>Hombres: {{ $grupo['hombres_pendientes'] }}</p><p>Mujeres: {{ $grupo['mujeres_pendientes'] }}</p><p>Total: {{ $grupo['hombres_pendientes'] + $grupo['mujeres_pendientes'] }}</p></div>
                        </div>
                        @if($grupo['total_planificado'] > 0 && ($grupo['hombres_pendientes'] > 0 || $grupo['mujeres_pendientes'] > 0))
                            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                                <label class="{{ $label }}" for="observacion-grupo-{{ $grupo['id'] }}">Observación por estudiantes no incorporados <span class="text-red-500">*</span></label>
                                <textarea id="observacion-grupo-{{ $grupo['id'] }}" rows="3" maxlength="1000" wire:model.live.debounce.1000ms="gruposEstudiantes.{{ $grupo['indice_formulario'] }}.observacion_no_cumplimiento" class="{{ $input }} mt-1 w-full" placeholder="Explique la diferencia entre la planificación y la participación real."></textarea>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Explique por qué no se incorporó la totalidad de participantes planificados.</p>
                                @error("gruposEstudiantes.{$grupo['indice_formulario']}.observacion_no_cumplimiento")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endif
                        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre','Sexo','Cuenta','Carrera','Horas','Estado','Acciones'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>@forelse($grupo['estudiantes'] as $row)<tr class="border-t dark:border-gray-700 {{ ($row['estado_participacion'] ?? 'activo') === 'activo' ? '' : 'opacity-60' }}"><td class="px-3 py-2">{{ $row['nombre'] }}@if(($row['estado_participacion'] ?? 'activo') !== 'activo')<p class="mt-1 text-xs">{{ $row['observacion_no_participacion'] }}</p>@endif</td><td class="px-3 py-2">{{ $this->sexoVisual($row['sexo'] ?? null) }}</td><td class="px-3 py-2">{{ $row['numero_cuenta'] }}</td><td class="px-3 py-2">{{ $row['carrera'] }}</td><td class="px-3 py-2"><input type="number" min="0" wire:model.blur.number="estudiantes.{{ $row['indice_formulario'] }}.horas_dedicadas" class="{{ $input }} w-24"></td><td class="px-3 py-2">{{ $this->estadoParticipacionVisual($row['estado_participacion'] ?? 'activo') }}</td><td class="px-3 py-2"><div class="flex gap-2">@if(($row['estado_participacion'] ?? 'activo') === 'activo')<button type="button" wire:click="openEstudianteModal({{ $row['indice_formulario'] }})" class="text-sm text-blue-700">Editar</button><button type="button" wire:click="openNoParticipacionModal('estudiante',{{ $row['indice_formulario'] }})" class="text-sm text-blue-700">Cambiar estado</button>@else<button type="button" wire:click="restaurarParticipante('estudiante',{{ $row['indice_formulario'] }})" wire:confirm="¿Restaurar participación?" class="text-sm text-green-700">Restaurar participación</button>@endif</div></td></tr>@empty<tr><td colspan="7" class="p-3 text-gray-500">No hay estudiantes registrados en este grupo.</td></tr>@endforelse</tbody></table></div>
                    </section>
                @empty
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><p>El proyecto no tiene grupos de estudiantes planificados en el FORM-DVUS-001. Esto no bloquea el registro de la ejecución real.</p><div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end"><div class="flex-1"><label class="{{ $label }}">Tipo de participación real</label><select wire:model="tipoParticipacionSinPlanificacion" class="{{ $input }}"><option value="practica_asignatura">Práctica de asignatura</option><option value="pps_servicio_social">Servicio Social o PPS</option><option value="voluntariado">Voluntariado estudiantil</option></select></div><button type="button" wire:click="openEstudianteSinPlanificacionModal" class="{{ $button }} bg-blue-600 text-white">Agregar estudiante</button></div></div>
                @endforelse
            </div>
            <section class="mt-5 rounded-lg border border-gray-200 p-4 dark:border-gray-700"><h4 class="font-semibold">Resumen general de estudiantes</h4><div class="mt-3 grid gap-3 sm:grid-cols-3">@foreach(['planificados'=>'Planificados','registrados'=>'Registrados','pendientes'=>'Pendientes'] as $clave=>$titulo)<div class="rounded bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>{{ $titulo }}</strong><p>Hombres: {{ $this->resumenPlanificacionEstudiantes[$clave]['hombres'] }}</p><p>Mujeres: {{ $this->resumenPlanificacionEstudiantes[$clave]['mujeres'] }}</p><p>Total: {{ $this->resumenPlanificacionEstudiantes[$clave]['total'] }}</p></div>@endforeach</div></section>
            <div class="mt-7 flex items-center justify-between"><h3 class="font-semibold">Voluntarios</h3><button type="button" wire:click="openVoluntarioModal" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar voluntario</button></div>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre','Sexo','Identidad','Departamento','Tipo','Horas','Estado','Acciones'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>@forelse($voluntarios as $i=>$row)<tr class="border-t dark:border-gray-700 {{ ($row['estado_participacion'] ?? 'activo') === 'activo' ? '' : 'opacity-60' }}"><td class="px-3 py-2 font-medium">{{ $row['nombre'] }}@if(($row['estado_participacion'] ?? 'activo') !== 'activo')<p class="mt-1 text-xs font-normal">{{ $row['observacion_no_participacion'] }}</p>@endif</td><td class="px-3 py-2"><span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $this->sexoVisual($row['sexo'] ?? null) }}</span></td><td class="px-3 py-2">{{ $row['identidad'] ?: '—' }}</td><td class="px-3 py-2">{{ $row['departamento'] ?: '—' }}</td><td class="px-3 py-2">{{ Str::headline($row['tipo'] ?? '') }}</td><td class="px-3 py-2">{{ $row['horas_dedicadas'] ?: 0 }}</td><td class="px-3 py-2">{{ $this->estadoParticipacionVisual($row['estado_participacion'] ?? 'activo') }}</td><td class="px-3 py-2"><div class="flex gap-2">@if(($row['estado_participacion'] ?? 'activo') === 'activo')<button type="button" wire:click="openVoluntarioModal({{ $i }})" class="text-sm text-blue-700 dark:text-blue-400">Editar</button><button type="button" wire:click="openNoParticipacionModal('voluntario',{{ $i }})" class="text-sm text-blue-700 dark:text-blue-400">Cambiar estado</button>@else<button type="button" wire:click="restaurarParticipante('voluntario',{{ $i }})" wire:confirm="¿Restaurar participación?" class="text-sm text-green-700">Restaurar participación</button>@endif</div></td></tr>@empty<tr><td colspan="8" class="p-3 text-gray-500">No hay voluntarios registrados.</td></tr>@endforelse</tbody></table></div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2"><div class="rounded bg-gray-50 p-3 text-sm dark:bg-gray-800">Hombres: <strong>{{ $this->totalesParticipacion['voluntarios_hombres'] }}</strong></div><div class="rounded bg-gray-50 p-3 text-sm dark:bg-gray-800">Mujeres: <strong>{{ $this->totalesParticipacion['voluntarios_mujeres'] }}</strong></div></div>
            <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                <label for="observacion-voluntarios" class="{{ $label }}">Observación por voluntarios no incorporados</label>
                <textarea id="observacion-voluntarios" rows="3" maxlength="1000" wire:model.live.debounce.1000ms="general.observacion_voluntarios_no_incorporados" class="{{ $input }} mt-1 w-full" placeholder="Explique, si corresponde, por qué no hubo participación voluntaria."></textarea>
                <p class="mt-1 text-xs text-gray-500">El proyecto no contiene una planificación desglosada de voluntarios; esta observación es opcional.</p>
                @error('general.observacion_voluntarios_no_incorporados')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @elseif($currentStep === 4)
            <div class="flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 4: Contrapartes</h2><button type="button" wire:click="agregarFila('contrapartes')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar contraparte</button></div>
            <div class="mt-5 space-y-5">
                @foreach($this->contrapartesConInstrumentos as $i=>$row)
                    @php($esPlanificada = ($row['origen'] ?? 'PLANIFICADO') === 'PLANIFICADO')
                    <article class="rounded-lg border p-4 dark:border-gray-700">
                        <div class="flex justify-between"><h3 class="font-semibold">Contraparte {{ $i+1 }}</h3>@unless($esPlanificada)<button type="button" wire:click="quitarFila('contrapartes',{{ $i }})" class="text-sm text-red-600">Quitar</button>@endunless</div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div><label class="{{ $label }}">Nombre</label><input wire:model="contrapartes.{{ $i }}.nombre" @readonly($esPlanificada) class="{{ $esPlanificada ? $readonly : $input }}"></div>
                            <div><label class="{{ $label }}">Tipo</label>@if($esPlanificada)<input value="{{ Str::headline(str_replace('_', ' ', $row['tipo'])) }}" readonly class="{{ $readonly }}">@else<select wire:model="contrapartes.{{ $i }}.tipo" class="{{ $input }}">@foreach(['gobierno_nacional'=>'Gobierno nacional','gobierno_municipal'=>'Gobierno municipal','ong'=>'ONG','sociedad_civil'=>'Sociedad civil organizada','sector_privado'=>'Sector privado','internacional'=>'Internacional'] as $value=>$name)<option value="{{ $value }}">{{ $name }}</option>@endforeach</select>@endif</div>
                            @foreach(['contacto'=>'Contacto','correo'=>'Correo','cargo'=>'Cargo','telefono'=>'Teléfono','territorio'=>'Territorio'] as $field=>$name)<div><label class="{{ $label }}">{{ $name }}</label><input wire:model="contrapartes.{{ $i }}.{{ $field }}" @readonly($esPlanificada) class="{{ $esPlanificada ? $readonly : $input }}"></div>@endforeach
                            <section class="rounded-md border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950/30 sm:col-span-2 lg:col-span-4">
                                <div class="flex items-center justify-between gap-3"><h4 class="text-sm font-semibold">Instrumentos de formalización y respaldos</h4><span class="rounded-full px-2 py-1 text-xs font-medium {{ $row['estado_instrumento']==='Disponible' ? 'bg-green-100 text-green-800' : ($row['estado_instrumento']==='Pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">{{ $row['estado_instrumento'] }}</span></div>
                                <div class="mt-2 space-y-2">@forelse($row['instrumentos'] as $instrumento)<div class="flex flex-wrap items-center justify-between gap-2 rounded bg-white p-2 text-sm dark:bg-gray-900"><span><strong>{{ $instrumento['descripcion'] ?: 'Instrumento de contraparte' }}</strong><br><span class="text-xs text-gray-500">{{ $instrumento['nombre_archivo'] ?: 'Archivo pendiente' }}</span></span>@if($this->anexoDocumentoUrl($instrumento['id'] ?? null))<a href="{{ $this->anexoDocumentoUrl($instrumento['id']) }}" target="_blank" rel="noopener" class="text-sm text-blue-700 dark:text-blue-300">Ver documento</a>@endif</div>@empty<p class="text-sm text-gray-600 dark:text-gray-300">No aplica: no hay instrumentos registrados en el proyecto.</p>@endforelse</div>
                                <p class="mt-2 text-xs text-gray-500">La gestión de archivos se realiza en el paso Anexos.</p>
                            </section>
                            <div class="sm:col-span-2"><label class="{{ $label }}">Compromisos asumidos</label><textarea wire:model.live.debounce.1000ms="contrapartes.{{ $i }}.compromisos_asumidos" class="{{ $input }}"></textarea></div>
                            <div class="sm:col-span-2"><label class="{{ $label }}">Compromisos cumplidos</label><textarea wire:model.live.debounce.1000ms="contrapartes.{{ $i }}.compromisos_cumplidos" class="{{ $input }}"></textarea></div>
                            <div><label class="{{ $label }}">Aporte monetario</label><input type="number" min="0" step="0.01" wire:model="contrapartes.{{ $i }}.aporte_monetario" class="{{ $input }}"></div>
                            <div><label class="{{ $label }}">Aporte en especie valorado</label><input type="number" min="0" step="0.01" wire:model="contrapartes.{{ $i }}.aporte_especie" class="{{ $input }}"></div>
                        </div>
                    </article>
                @endforeach
            </div>
        @elseif($currentStep === 5)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 5: Resultados y ejecución de actividades</h2>
            @include('livewire.proyectos.informe-final.partials.resultados-actividades')
            @if(false)
            <div class="mt-4 rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>Objetivo general:</strong> {{ $general['objetivo_general'] ?: 'No registrado' }}</div>
            <div class="mt-5 flex items-center justify-between"><h3 class="font-semibold">Resultados</h3><button type="button" wire:click="agregarFila('resultados')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar resultado</button></div>
            <div class="mt-3 space-y-4">@foreach($resultados as $i=>$row)<article class="rounded-lg border p-4 dark:border-gray-700"><div class="flex justify-between"><strong>Resultado {{ $i+1 }}</strong><button type="button" wire:click="quitarFila('resultados',{{ $i }})" class="text-sm text-red-600">Quitar</button></div><div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach(['objetivo_especifico'=>'Objetivo específico','resultado_planificado'=>'Resultado planificado','indicador_propuesto'=>'Indicador','unidad_medida'=>'Unidad de medida','producto_logrado'=>'Producto logrado','observaciones'=>'Observaciones'] as $field=>$name)<div class="{{ in_array($field,['objetivo_especifico','resultado_planificado','observaciones']) ? 'sm:col-span-2' : '' }}"><label class="{{ $label }}">{{ $name }}</label><textarea rows="2" wire:model.live.debounce.1000ms="resultados.{{ $i }}.{{ $field }}" class="{{ $input }}"></textarea></div>@endforeach<div><label class="{{ $label }}">Meta numérica</label><input type="number" min="0" step="0.01" wire:model.blur.number="resultados.{{ $i }}.meta_numerica" class="{{ $input }}"></div><div><label class="{{ $label }}">Valor alcanzado</label><input type="number" min="0" step="0.01" wire:model.blur.number="resultados.{{ $i }}.valor_alcanzado" class="{{ $input }}"></div><div><label class="{{ $label }}">Cumplimiento %</label><input type="number" min="0" max="100" step="0.01" wire:model.blur.number="resultados.{{ $i }}.porcentaje_cumplimiento" class="{{ $input }}"></div><div><label class="{{ $label }}">Estado</label><select wire:model.live="resultados.{{ $i }}.estado" class="{{ $input }}"><option value="alcanzado">Alcanzado</option><option value="parcialmente_alcanzado">Parcialmente alcanzado</option><option value="no_alcanzado">No alcanzado</option><option value="no_aplica">No aplica</option></select></div></div></article>@endforeach</div>
            <div class="mt-7 flex items-center justify-between"><h3 class="font-semibold">Actividades</h3><button type="button" wire:click="agregarFila('actividades')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar actividad</button></div>
            <div class="mt-3 space-y-4">@foreach($actividades as $i=>$row)<article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"><div class="flex items-start justify-between gap-3"><div class="min-w-0 flex-1"><label class="{{ $label }}">Nombre de la actividad</label><textarea rows="2" wire:model.live.debounce.1000ms="actividades.{{ $i }}.actividad_planificada" class="{{ $input }}"></textarea></div><button type="button" wire:click="quitarFila('actividades',{{ $i }})" class="mt-6 text-sm text-red-600">Quitar</button></div><div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><div><label class="{{ $label }}">Período</label><div class="grid grid-cols-2 gap-2"><input type="date" wire:model.blur="actividades.{{ $i }}.fecha_inicial" class="{{ $input }}"><input type="date" wire:model.blur="actividades.{{ $i }}.fecha_final" class="{{ $input }}"></div></div><div><label class="{{ $label }}">Estado</label><select wire:model.live="actividades.{{ $i }}.estado" class="{{ $input }}"><option value="ejecutada">Ejecutada</option><option value="parcial">Parcial</option><option value="no_ejecutada">No ejecutada</option></select></div><div><label class="{{ $label }}">Horas</label><input type="number" min="0" wire:model.blur.number="actividades.{{ $i }}.horas_dedicadas" class="{{ $input }}"></div><div><label class="{{ $label }}">Origen</label><select wire:model.live="actividades.{{ $i }}.origen" class="{{ $input }}"><option value="planificada">Planificada</option><option value="emergente">Emergente</option></select></div><div class="sm:col-span-2"><label class="{{ $label }}">Actividad realizada</label><textarea rows="3" wire:model.live.debounce.1000ms="actividades.{{ $i }}.actividad_realizada" class="{{ $input }}"></textarea></div><div class="sm:col-span-2"><label class="{{ $label }}">Medio de verificación</label><textarea rows="3" wire:model.live.debounce.1000ms="actividades.{{ $i }}.medio_verificacion" class="{{ $input }}"></textarea></div><div class="sm:col-span-2"><label class="{{ $label }}">Responsable principal</label><input value="{{ $row['responsable'] ?? '' }}" readonly class="{{ $readonly }}">@error("actividades.$i.responsable")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div><div class="mt-5 rounded-md bg-gray-50 p-3 dark:bg-gray-800"><div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h4 class="text-sm font-semibold">Participantes</h4><p class="text-xs text-gray-500">Se muestran por persona y tipo, sin concatenar nombres.</p></div><div class="flex flex-col gap-2 sm:flex-row"><select wire:model="participanteSeleccion.{{ $i }}" class="{{ $input }} min-w-56"><option value="externo:nuevo">Participante externo</option>@foreach($this->opcionesParticipantesActividad as $group=>$options)<optgroup label="{{ $group }}">@foreach($options as $option)<option value="{{ $option['value'] }}">{{ $option['label'] }}</option>@endforeach</optgroup>@endforeach</select><button type="button" wire:click="agregarParticipanteActividad({{ $i }})" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar participante</button></div></div>@error("actividades.$i.participantes")<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<div class="mt-3 flex flex-wrap gap-2">@forelse(($row['participantes'] ?? []) as $pi=>$participant)<details class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"><summary class="cursor-pointer list-none"><span class="font-medium">{{ $participant['nombre'] ?: 'Participante externo' }}</span><span class="ml-1 text-xs text-gray-500">· {{ Str::headline($participant['tipo'] ?? 'externo') }}@if($participant['es_responsable'] ?? false) · Responsable @endif</span></summary><div class="mt-3 grid gap-2 sm:grid-cols-3"><div><label class="{{ $label }}">Nombre</label><input wire:model.live.debounce.1000ms="actividades.{{ $i }}.participantes.{{ $pi }}.nombre" @if(($participant['tipo'] ?? '')!=='externo') readonly @endif class="{{ ($participant['tipo'] ?? '')!=='externo' ? $readonly : $input }}">@error("actividades.$i.participantes.$pi.nombre")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="{{ $label }}">Rol</label><input wire:model.live.debounce.1000ms="actividades.{{ $i }}.participantes.{{ $pi }}.rol" class="{{ $input }}"></div><div><label class="{{ $label }}">Horas</label><input type="number" min="0" wire:model.blur.number="actividades.{{ $i }}.participantes.{{ $pi }}.horas_dedicadas" class="{{ $input }}"></div><button type="button" wire:click="marcarResponsableActividad({{ $i }},{{ $pi }})" @disabled($participant['es_responsable'] ?? false) class="text-left text-xs text-blue-600 disabled:text-gray-500">{{ ($participant['es_responsable'] ?? false) ? 'Responsable principal' : 'Marcar como responsable' }}</button><button type="button" wire:click="quitarParticipanteActividad({{ $i }},{{ $pi }})" class="text-left text-xs text-red-600">Quitar participante</button></div></details>@empty<span class="text-sm text-gray-500">No hay participantes relacionados con esta actividad.</span>@endforelse</div></div></article>@endforeach</div>
            @endif
        @elseif($currentStep === 6)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 6: Acciones no ejecutadas, emergentes y reflexión</h2>
            @foreach(['accionesNoEjecutadas'=>'Acciones planificadas no ejecutadas','accionesEmergentes'=>'Acciones emergentes'] as $group=>$title)@php($groupRows = $group === 'accionesNoEjecutadas' ? $accionesNoEjecutadas : $accionesEmergentes)<div class="mt-7 flex items-center justify-between"><h3 class="font-semibold">{{ $title }}</h3><button type="button" wire:click="agregarFila('{{ $group }}')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar</button></div><div class="mt-3 space-y-3">@foreach($groupRows as $i=>$row)<div class="grid gap-3 rounded border p-3 dark:border-gray-700 sm:grid-cols-2 lg:grid-cols-4">@foreach($row as $field=>$value)@if(!in_array($field,['id','informe_final_proyecto_id','created_at','updated_at','informe_final_resultado_id']))<div><label class="{{ $label }}">{{ Str::headline($field) }}</label>@if(in_array($field,['impacto']))<select wire:model="{{ $group }}.{{ $i }}.{{ $field }}" class="{{ $input }}"><option value="bajo">Bajo</option><option value="medio">Medio</option><option value="alto">Alto</option></select>@else<input @if(in_array($field,['fecha'])) type="date" @elseif(in_array($field,['horas'])) type="number" min="0" @endif wire:model="{{ $group }}.{{ $i }}.{{ $field }}" class="{{ $input }}">@endif</div>@endif @endforeach<button type="button" wire:click="quitarFila('{{ $group }}',{{ $i }})" class="self-end text-sm text-red-600">Quitar</button></div>@endforeach</div>@endforeach
            <h3 class="mt-7 font-semibold">Reflexión, transformación y sostenibilidad</h3>
            <div class="mt-5 space-y-4">@foreach(['dificultades'=>'Dificultades','acciones_dificultades'=>'Acciones para afrontar dificultades','lecciones_aprendidas'=>'Lecciones aprendidas','buenas_practicas'=>'Buenas prácticas','problema_inicial'=>'Problema inicial identificado','transformacion_lograda'=>'Transformación lograda','mecanismos_sostenibilidad'=>'Mecanismos de sostenibilidad','acciones_contraparte_sostenibilidad'=>'Acciones de la contraparte para sostenibilidad','desafios'=>'Desafíos','respuesta_reforma_universitaria'=>'Respuesta a la reforma universitaria','recomendaciones'=>'Recomendaciones','bibliografia'=>'Bibliografía'] as $field=>$name)<div class="w-full"><label class="{{ $label }}">{{ $name }}</label><textarea rows="4" wire:model.live.debounce.1000ms="general.{{ $field }}" @readonly($this->esCampoReflexionHeredado($field)) class="{{ $this->esCampoReflexionHeredado($field) ? $readonly : $input }}"></textarea>@error("general.$field")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>@endforeach</div>
            <div class="mt-7 flex items-center justify-between">
                <h3 class="font-semibold">Objetivos de Desarrollo Sostenible</h3>
                <button type="button" wire:click="agregarFila('ods')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar ODS</button>
            </div>
            <div class="mt-3 space-y-3">
                @foreach ($ods as $i => $odsItem)
                    <?php
                        $origenOds = strtoupper((string) ($odsItem['origen'] ?? 'PLANIFICADO'));
                        $esPlanificado = $origenOds === 'PLANIFICADO';
                        $odsSeleccionado = $odsCatalogo->firstWhere('id', $odsItem['ods_id'] ?? null);
                        $metaSeleccionada = $metasCatalogo->firstWhere('id', $odsItem['meta_contribuye_id'] ?? null);
                    ?>
                    <div
                        wire:key="ods-informe-final-{{ $odsItem['id'] ?? 'nuevo-'.$i }}"
                        class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                    >
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">ODS {{ $i + 1 }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $esPlanificado ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' }}">
                                {{ $esPlanificado ? 'Cargado desde el registro del proyecto' : 'Ejecución' }}
                            </span>
                        </div>

                        <div class="grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-6">
                            <div>
                                <label class="{{ $label }}">ODS</label>
                                @if($esPlanificado)
                                    <input
                                        value="{{ $odsSeleccionado?->nombre ?? 'ODS no catalogado' }}"
                                        readonly
                                        class="{{ $readonly }}"
                                    >
                                @else
                                    <select wire:model="ods.{{ $i }}.ods_id" class="{{ $input }}">
                                        <option value="">Seleccione</option>
                                        @foreach($odsCatalogo as $item)
                                            <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div>
                                <label class="{{ $label }}">Meta</label>
                                @if($esPlanificado)
                                    <input
                                        value="{{ $metaSeleccionada ? $metaSeleccionada->numero_meta.' — '.$metaSeleccionada->descripcion : (($odsItem['meta_ods'] ?? null) ?: 'Sin meta catalogada') }}"
                                        readonly
                                        class="{{ $readonly }}"
                                    >
                                @else
                                    <select wire:model="ods.{{ $i }}.meta_contribuye_id" class="{{ $input }}">
                                        <option value="">Sin meta catalogada</option>
                                        @foreach($metasCatalogo as $meta)
                                            <option value="{{ $meta->id }}">{{ $meta->numero_meta }} — {{ Str::limit($meta->descripcion,55) }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div>
                                <label class="{{ $label }}">Aporte</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    wire:model.live.debounce.1000ms="ods.{{ $i }}.descripcion_aporte"
                                    wire:blur="formatearAporteOds({{ $i }})"
                                    class="{{ $input }}"
                                >
                            </div>

                            <div>
                                <label class="{{ $label }}">Evidencia</label>
                                <input
                                    wire:model.live.debounce.1000ms="ods.{{ $i }}.evidencia"
                                    class="{{ $input }}"
                                >
                            </div>

                            <div>
                                <label class="{{ $label }}">Contribución</label>
                                <select wire:model="ods.{{ $i }}.nivel_contribucion" class="{{ $input }}">
                                    <option value="directa">Directa</option>
                                    <option value="indirecta">Indirecta</option>
                                </select>
                            </div>

                            @unless($esPlanificado)
                                <button
                                    type="button"
                                    wire:click="quitarFila('ods',{{ $i }})"
                                    class="self-end text-left text-sm text-red-600 hover:text-red-700"
                                >
                                    Quitar
                                </button>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($currentStep === 7)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 7: Evaluación comunitaria y ejecución presupuestaria</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-6"><div><label class="{{ $label }}">Total beneficiarios</label><input type="number" min="0" wire:model.live="general.valoracion_total_beneficiarios" class="{{ $input }}"></div><div><label class="{{ $label }}">Tamaño de muestra</label><input type="number" min="0" wire:model.live="general.valoracion_muestra" class="{{ $input }}"></div>@foreach(['excelente'=>'Excelente','muy_buena'=>'Muy buena','regular'=>'Regular','mala'=>'Mala'] as $field=>$name)<div><label class="{{ $label }}">{{ $name }}</label><input type="number" min="0" wire:model.live="general.valoracion_{{ $field }}" class="{{ $input }}"><p class="mt-1 text-xs text-gray-500">{{ $this->porcentajesValoracion[$field] }}%</p></div>@endforeach</div>
            <div class="mt-7 flex items-center justify-between"><h3 class="font-semibold">Detalle presupuestario</h3><button type="button" wire:click="agregarFila('presupuesto')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar concepto</button></div>
            <div class="mt-3 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Fuente','Concepto','Unidad','Cantidad','Costo unitario','Costo total','Origen',''] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>@foreach($presupuesto as $i=>$row)@php($protegida = filled($row['id'] ?? null) || ($row['origen_fondos'] ?? null) === 'contrapartes_proyecto')<tr class="border-t dark:border-gray-700"><td><select wire:model.live="presupuesto.{{ $i }}.fuente" @disabled($protegida) class="{{ $input }}"><option>UNAH</option><option>CONTRAPARTE</option></select></td><td><input wire:model="presupuesto.{{ $i }}.concepto" @readonly($protegida) class="{{ $input }} min-w-36"></td><td><input wire:model="presupuesto.{{ $i }}.unidad" @readonly($protegida) class="{{ $input }} min-w-24"></td><td><input type="number" min="0" step="0.01" wire:model.live="presupuesto.{{ $i }}.cantidad" @readonly($protegida) class="{{ $input }} w-28"></td><td><input type="number" min="0" step="0.01" wire:model.live="presupuesto.{{ $i }}.costo_unitario" @readonly($protegida) class="{{ $input }} w-32"></td><td class="px-3 py-2 font-medium">L {{ number_format((float)($row['cantidad']??0)*(float)($row['costo_unitario']??0),2) }}</td><td><input wire:model="presupuesto.{{ $i }}.origen_fondos" @readonly($protegida) class="{{ $input }} min-w-32"></td><td>@unless($protegida)<button type="button" wire:click="quitarFila('presupuesto',{{ $i }})" class="text-red-600">×</button>@endunless</td></tr>@endforeach</tbody></table></div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><div><label class="{{ $label }}">Presupuesto planificado</label><input type="number" min="0" step="0.01" wire:model.live="general.presupuesto_planificado" class="{{ $input }}"></div><div><label class="{{ $label }}">Aporte beneficiarios</label><input type="number" min="0" step="0.01" wire:model.live="general.aporte_beneficiarios" class="{{ $input }}"></div><div><label class="{{ $label }}">Otros aportes</label><input type="number" min="0" step="0.01" wire:model.live="general.otros_aportes" class="{{ $input }}"></div></div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach(['subtotal'=>'Subtotal base UNAH','infraestructura'=>'Infraestructura 3%','servicios'=>'Servicios públicos 3%','unah'=>'Total institucional','contraparte'=>'Aporte contraparte','ejecucion'=>'Ejecución total','porcentaje'=>'Porcentaje de ejecución'] as $field=>$name)<div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">{{ $name }}</p><p class="mt-1 font-semibold">{{ $field==='porcentaje' ? number_format($this->totalesPresupuesto[$field],2).'%' : 'L '.number_format($this->totalesPresupuesto[$field],2) }}</p></div>@endforeach</div>
        @else
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 8: Anexos, cierre y validación</h2>
            <section class="mt-5 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="flex items-center justify-between"><div><h3 class="font-semibold">Documentos generales</h3><p class="text-xs text-gray-500">Instrumentos, certificaciones, respaldos, bitácoras, encuestas, informes, enlaces y videos.</p></div><button type="button" wire:click="agregarFila('anexos')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar documento</button></div>
                <div class="mt-3 space-y-3">
                    @foreach($this->documentosAnexos as $row)
                        <?php
                            $i = $row['indice_formulario'];
                            $esAnexoPlanificado = in_array(($row['origen'] ?? 'INFORME'), ['PLANIFICADO', 'PROYECTO'], true);
                        ?>
                        <div
                            wire:key="documento-general-{{ strtolower($row['origen'] ?? 'informe') }}-{{ $row['id'] ?? 'nuevo-'.$i }}"
                            class="rounded-lg border border-gray-200 bg-white p-4"
                        >
                            <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 xl:grid-cols-8">
                                {{-- Categoría --}}
                                <div class="min-w-0">
                                    <label class="{{ $label }}">Categoría</label>
                                    @if($esAnexoPlanificado)
                                        <input value="Instrumento de contraparte" readonly class="{{ $readonly }}">
                                    @else
                                        <select wire:model.live="anexos.{{ $i }}.categoria" class="{{ $input }}">
                                            <option value="documento_general">Documento general</option>
                                            <option value="instrumento_contraparte">Instrumento o respaldo de contraparte</option>
                                        </select>
                                    @endif
                                </div>

                                {{-- Tipo --}}
                                <div class="min-w-0">
                                    <label class="{{ $label }}">Tipo</label>
                                    <select
                                        wire:model="anexos.{{ $i }}.tipo"
                                        @disabled($esAnexoPlanificado)
                                        class="{{ $input }}"
                                    >
                                        @foreach(['materiales'=>'Materiales generados','encuestas'=>'Formularios de encuesta','procesamiento'=>'Informes de procesamiento','videos'=>'Videos','difusion'=>'Evidencias de difusión','asistencia'=>'Listas de asistencia','manuales'=>'Manuales','guias'=>'Guías','actas'=>'Actas','otros'=>'Otros'] as $value=>$name)
                                            <option value="{{ $value }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Contraparte cuando corresponda --}}
                                @if(($row['categoria'] ?? 'documento_general') === 'instrumento_contraparte')
                                    <div class="min-w-0">
                                        <label class="{{ $label }}">Contraparte</label>
                                        <select
                                            wire:model="anexos.{{ $i }}.informe_final_contraparte_id"
                                            @disabled($esAnexoPlanificado)
                                            class="{{ $input }}"
                                        >
                                            <option value="">Seleccione</option>
                                            @foreach($contrapartes as $contraparte)
                                                <option value="{{ $contraparte['id'] ?? '' }}">{{ $contraparte['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                {{-- Descripción --}}
                                <div class="min-w-0">
                                    <label class="{{ $label }}">Descripción</label>
                                    <input wire:model.live.debounce.1000ms="anexos.{{ $i }}.descripcion" @readonly($esAnexoPlanificado) class="{{ $esAnexoPlanificado ? $readonly : $input }}">
                                </div>

                                {{-- Archivo y Ver documento --}}
                                <div class="min-w-0">
                                    <label class="{{ $label }}">Archivo</label>
                                    @if($esAnexoPlanificado)
                                        <p class="break-words rounded bg-gray-50 p-2 text-xs dark:bg-gray-800">
                                            {{ $row['nombre_archivo'] ?: 'Archivo precargado' }}
                                        </p>
                                    @else
                                        <input type="file" wire:model="anexoArchivos.{{ $i }}" class="{{ $input }}">
                                    @endif
                                    @if($this->anexoDocumentoUrl($row['id'] ?? null))
                                        <a
                                            href="{{ $this->anexoDocumentoUrl($row['id']) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="mt-1 block text-xs text-blue-700"
                                        >
                                            Ver documento
                                        </a>
                                    @endif
                                </div>

                                {{-- Enlace --}}
                                <div class="min-w-0">
                                    <label class="{{ $label }}">Enlace</label>
                                    <input type="url" wire:model.live.debounce.1000ms="anexos.{{ $i }}.enlace" class="{{ $input }}">
                                </div>

                                {{-- Fecha --}}
                                <div class="min-w-0">
                                    <label class="{{ $label }}">Fecha</label>
                                    <input type="date" wire:model="anexos.{{ $i }}.fecha" @readonly($esAnexoPlanificado) class="{{ $esAnexoPlanificado ? $readonly : $input }}">
                                </div>

                                {{-- Quitar cuando corresponda --}}
                                @unless($esAnexoPlanificado)
                                    <div class="flex min-w-0 items-end xl:self-end">
                                        <button
                                            type="button"
                                            wire:click="quitarFila('anexos',{{ $i }})"
                                            class="whitespace-nowrap text-sm text-red-600 hover:text-red-700"
                                        >
                                            Quitar
                                        </button>
                                    </div>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="mb-4 font-semibold">Fotografías del proyecto</h3>
                <x-forms.image-dropzone model="fotografiasTemporales" id="inf001-fotografias" />
                @if($this->fotografias)
                    <h4 class="mt-6 text-sm font-semibold">Fotografías guardadas</h4>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach($this->fotografias as $foto)<article class="overflow-hidden rounded-lg border dark:border-gray-700"><img src="{{ $foto['id'] ? route('informes-finales.anexos.mostrar', ['anexo' => $foto['id']], false) : '' }}" alt="{{ $foto['descripcion'] ?: 'Fotografía '.$foto['nombre_archivo'] }}" class="h-36 w-full object-cover"><div class="space-y-2 p-3 text-xs"><div class="flex items-center justify-between gap-2"><p class="truncate font-medium" title="{{ $foto['nombre_archivo'] }}">{{ $foto['nombre_archivo'] ?: 'Fotografía' }}</p><span class="rounded-full bg-green-100 px-2 py-1 text-[10px] font-medium text-green-800">Guardada</span></div><p class="text-gray-500">{{ $foto['tamano_bytes'] ? number_format($foto['tamano_bytes']/1024,1).' KB' : 'Tamaño no disponible' }} · {{ $foto['fecha'] ?: 'Sin fecha' }}</p><input wire:model.live.debounce.1000ms="anexos.{{ $foto['indice_formulario'] }}.descripcion" aria-label="Descripción de {{ $foto['nombre_archivo'] ?: 'la fotografía' }}" placeholder="Descripción opcional" class="{{ $input }}"><div class="flex gap-3"><a href="{{ $foto['id'] ? route('informes-finales.anexos.mostrar', ['anexo' => $foto['id']], false) : '#' }}" data-route="informes-finales.anexos.mostrar" target="_blank" rel="noopener noreferrer" class="text-blue-700">Ver</a><a href="{{ $foto['id'] ? route('informes-finales.anexos.descargar', ['anexo' => $foto['id']], false) : '#' }}" data-route="informes-finales.anexos.descargar" class="text-blue-700">Descargar</a><button type="button" wire:click="quitarFotografia({{ $foto['id'] }})" wire:confirm="¿Quitar esta fotografía del Informe Final?" class="text-red-600">Quitar</button></div></div></article>@endforeach</div>
                @endif
            </section>
            <h3 class="mt-7 font-semibold">Cierre</h3><div class="mt-3 grid gap-4 sm:grid-cols-2"><div><label class="{{ $label }}">Fecha de cierre</label><input type="date" wire:model="general.fecha_cierre" class="{{ $input }}"></div><div class="sm:col-span-2"><label class="{{ $label }}">Observaciones finales</label><textarea rows="4" wire:model.live.debounce.1000ms="general.observaciones_finales" class="{{ $input }}"></textarea></div><label class="flex items-start gap-2 sm:col-span-2"><input type="checkbox" wire:model="general.confirmacion_veracidad" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"><span class="text-sm">Confirmo que la información consignada en el INF-001 es veraz.</span></label></div>
            <div class="mt-7 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-100"><strong>Firmas:</strong> la plantilla reserva los espacios para coordinador, jefatura de unidad académica, coordinación de vinculación y decanato/dirección. Completar este formulario no crea firmas, no cambia etapas y no envía notificaciones.</div>
            <section class="mt-7 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Resumen final del informe</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach($this->resumenRevision as $name=>$value)<div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">{{ $name }}</p><p class="mt-1 font-semibold">{{ $value }}</p></div>@endforeach</div>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100"><strong>Campos pendientes</strong>@if($this->camposPendientes)<ul class="mt-2 list-disc pl-5">@foreach($this->camposPendientes as $pending)<li>{{ $pending }}</li>@endforeach</ul>@else<p class="mt-2">No hay campos esenciales pendientes.</p>@endif</div>
                    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/30 dark:text-red-100"><strong>Inconsistencias detectadas</strong>@if($this->inconsistenciasRevision)<ul class="mt-2 list-disc pl-5">@foreach($this->inconsistenciasRevision as $issue)<li>{{ $issue }}</li>@endforeach</ul>@else<p class="mt-2">No se detectaron inconsistencias.</p>@endif</div>
                </div>
            </section>
        @endif

        <div class="mt-8 flex flex-col gap-3 pt-4 border-t border-gray-200 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <div>@if($currentStep>1)<button type="button" wire:click="anterior" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">← Anterior</button>@else<a href="{{ route('historialproyecto',$proyecto) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Volver</a>@endif</div>
            <div class="flex flex-wrap items-center justify-end gap-3">
                <span class="text-xs min-w-[78px] text-right" aria-live="polite">@if($estadoGuardado==='guardando')<span class="text-gray-500 dark:text-gray-400">Guardando...</span>@elseif($estadoGuardado==='error')<span class="text-red-600 dark:text-red-400">Error al guardar</span>@else<span class="text-green-600 dark:text-green-400">Guardado</span>@endif</span>
                <button type="button" wire:click="guardarBorrador" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 disabled:opacity-60">Guardar borrador</button>
                @if($currentStep<8)<button type="button" wire:click="siguiente" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60">Siguiente →</button>@else<a href="{{ route('informes-finales.inf-001.preview',$informe) }}" class="{{ $button }} border border-blue-300 text-blue-700 dark:border-blue-700 dark:text-blue-300">Vista previa</a><a href="{{ route('informes-finales.inf-001.pdf',$informe) }}" class="{{ $button }} border border-blue-300 text-blue-700 dark:border-blue-700 dark:text-blue-300">Descargar PDF preliminar</a><button type="button" wire:click="validarInforme" wire:loading.attr="disabled" wire:confirm="¿Marcar el INF-001 como completo? El flujo de cierre se iniciará únicamente al enviarlo desde Ver proyecto." class="{{ $button }} bg-green-700 text-white hover:bg-green-800 disabled:opacity-60">Marcar completo</button><a href="{{ route('historialproyecto',$proyecto) }}" class="{{ $button }} border border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300">Volver al proyecto</a>@endif
            </div>
        </div>
    </main>

    @if($showEstudianteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="estudiante-modal-title">
            <div class="fixed inset-0 bg-black/50" wire:click="closeEstudianteModal"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                        <h4 id="estudiante-modal-title" class="text-sm font-semibold text-gray-900 dark:text-white">Agregar estudiante participante</h4>
                        <button wire:click="closeEstudianteModal" type="button" class="text-lg leading-none text-gray-500 hover:text-gray-800" aria-label="Cerrar">✕</button>
                    </div>
                    <div class="space-y-4 p-5">
                        @if($this->grupoEstudianteActivo)
                            <input type="hidden" wire:model="grupoEstudianteSeleccionadoId">
                            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm dark:border-indigo-900 dark:bg-indigo-950/30">
                                <p><strong>Tipo:</strong> {{ $this->grupoEstudianteActivo['tipo_etiqueta'] }}</p>
                                @if($this->grupoEstudianteActivo['asignatura_etiqueta'])<p class="mt-1"><strong>Asignatura:</strong> {{ $this->grupoEstudianteActivo['asignatura_etiqueta'] }}</p>@endif
                                @if($this->grupoEstudianteActivo['periodo_academico'])<p class="mt-1"><strong>Período académico:</strong> {{ $this->grupoEstudianteActivo['periodo_academico'] }}</p>@endif
                            </div>
                        @endif
                        @if($editEstudianteIndex === null)
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <h5 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Buscar estudiante institucional</h5>
                                <div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-[1fr_auto]">
                                    <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Buscar por número de cuenta <span class="text-red-500">*</span></label><input wire:model="estudianteBusquedaCuenta" wire:keydown.enter="buscarEstudiante" class="{{ $input }}" placeholder="Ej. 20201234567">@error('estudianteBusquedaCuenta')<p class="mt-1 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</p>@enderror<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Presiona Enter o usa el botón para buscar.</p></div>
                                    <button wire:click="buscarEstudiante" wire:loading.attr="disabled" wire:target="buscarEstudiante" type="button" class="mt-6 inline-flex items-center justify-center rounded-md bg-orange-600 px-3 py-2 text-xs font-medium text-white hover:bg-orange-700 disabled:opacity-60"><span wire:loading.remove wire:target="buscarEstudiante">Buscar estudiante</span><span wire:loading wire:target="buscarEstudiante">Buscando…</span></button>
                                </div>
                            </div>
                        @endif

                        @if($estudianteEncontrado)
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                                <div class="flex items-center justify-between gap-3"><div><h5 class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Datos del estudiante</h5><p class="mt-1 text-xs text-blue-700 dark:text-blue-200">Completa o confirma la información.</p></div><button type="button" wire:click="limpiarSeleccionEstudiante" class="text-xs font-medium text-blue-700 hover:text-blue-900 dark:text-blue-300">Limpiar selección</button></div>
                                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2"><div><dt class="text-xs text-gray-500">Nombre</dt><dd class="font-medium">{{ $estudianteEncontrado['nombre'] }}</dd></div><div><dt class="text-xs text-gray-500">Número de cuenta</dt><dd>{{ $estudianteEncontrado['numero_cuenta'] }}</dd></div><div><dt class="text-xs text-gray-500">Sexo</dt><dd>{{ $this->sexoVisual($estudianteEncontrado['sexo'] ?? null) }}</dd></div><div><dt class="text-xs text-gray-500">Carrera</dt><dd>{{ $estudianteEncontrado['carrera'] ?: '—' }}</dd></div><div class="sm:col-span-2"><dt class="text-xs text-gray-500">Correo</dt><dd>{{ $estudianteEncontrado['correo'] ?? '—' }}</dd></div></dl>
                            </div>
                            <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Horas reales dedicadas</label><input type="number" min="0" step="0.5" wire:model="estudianteModal.horas_dedicadas" class="{{ $input }}">@error('estudianteModal.horas_dedicadas')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div>
                        @endif
                        @if($mostrarRegistroManual)
                            <div class="flex items-center gap-3"><div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div><span class="text-xs font-medium text-gray-500 dark:text-gray-400">O registrar estudiante manualmente</span><div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div></div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2"><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nombres</label><input wire:model="estudianteManual.nombres" class="{{ $input }}">@error('estudianteManual.nombres')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Apellidos</label><input wire:model="estudianteManual.apellidos" class="{{ $input }}">@error('estudianteManual.apellidos')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Número de cuenta</label><input wire:model="estudianteManual.numero_cuenta" class="{{ $input }}">@error('estudianteManual.numero_cuenta')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Sexo</label><select wire:model="estudianteManual.sexo" class="{{ $input }}"><option value="">Seleccione el sexo</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select>@error('estudianteManual.sexo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Carrera</label><input wire:model="estudianteManual.carrera" class="{{ $input }}"></div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Correo electrónico</label><input type="email" wire:model="estudianteManual.correo" class="{{ $input }}">@error('estudianteManual.correo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Horas reales dedicadas</label><input type="number" min="0" step="0.5" wire:model="estudianteManual.horas_dedicadas" class="{{ $input }}">@error('estudianteManual.horas_dedicadas')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div></div>
                        @endif
                        <p class="text-xs text-gray-500">Los datos institucionales son solo de lectura; el registro manual se conserva únicamente en este informe.</p>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-3 dark:border-gray-700"><button wire:click="closeEstudianteModal" type="button" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>@if($estudianteEncontrado)<button wire:click="saveEstudianteModal" wire:loading.attr="disabled" type="button" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-60">{{ $editEstudianteIndex === null ? 'Agregar estudiante' : 'Guardar cambios' }}</button>@elseif($mostrarRegistroManual)<button wire:click="saveEstudianteManual" wire:loading.attr="disabled" type="button" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-60">{{ $editEstudianteIndex === null ? 'Agregar estudiante' : 'Guardar cambios' }}</button>@endif</div>
                </div>
            </div>
        </div>
    @endif

    @if($showVoluntarioModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="voluntario-modal-title">
            <div class="fixed inset-0 bg-black/50" wire:click="closeVoluntarioModal"></div>
            <div class="relative flex min-h-full items-center justify-center p-4"><div class="relative w-full max-w-2xl rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700"><h4 id="voluntario-modal-title" class="text-sm font-semibold text-gray-900 dark:text-white">Agregar voluntario participante</h4><button wire:click="closeVoluntarioModal" type="button" class="text-lg leading-none text-gray-500 hover:text-gray-800" aria-label="Cerrar">✕</button></div>
                <div class="space-y-4 p-5">
                    @if($editVoluntarioIndex === null)<div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"><h5 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Seleccionar persona existente</h5><div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-[1fr_auto]"><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Número de empleado</label><input wire:model="voluntarioBusquedaNumero" wire:keydown.enter="buscarVoluntario" class="{{ $input }}">@error('voluntarioBusquedaNumero')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><button wire:click="buscarVoluntario" wire:loading.attr="disabled" wire:target="buscarVoluntario" type="button" class="mt-6 rounded-md bg-orange-600 px-3 py-2 text-xs font-medium text-white hover:bg-orange-700 disabled:opacity-60"><span wire:loading.remove wire:target="buscarVoluntario">Buscar persona</span><span wire:loading wire:target="buscarVoluntario">Buscando…</span></button></div></div><div class="flex items-center gap-3"><div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div><span class="text-xs font-medium text-gray-500 dark:text-gray-400">O registrar voluntario manualmente</span><div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div></div>@endif
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2"><div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nombre completo</label><input wire:model="voluntarioModal.nombre" @if($voluntarioEncontrado) readonly @endif class="{{ $voluntarioEncontrado ? $readonly : $input }}">@error('voluntarioModal.nombre')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Sexo</label><select wire:model="voluntarioModal.sexo" @if($voluntarioEncontrado) disabled @endif class="{{ $input }}"><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select>@error('voluntarioModal.sexo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Identidad / número</label><input wire:model="voluntarioModal.identidad" @if($voluntarioEncontrado) readonly @endif class="{{ $voluntarioEncontrado ? $readonly : $input }}"></div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Departamento</label><input wire:model="voluntarioModal.departamento" class="{{ $input }}"></div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipo</label><select wire:model="voluntarioModal.tipo" class="{{ $input }}"><option value="profesor_hora">Profesor por hora</option><option value="pas">PAS</option><option value="profesor_permanente">Profesor permanente</option><option value="egresado">Egresado</option></select></div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Horas dedicadas</label><input type="number" min="0" step="0.5" wire:model="voluntarioModal.horas_dedicadas" class="{{ $input }}"></div></div>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-3 dark:border-gray-700"><button wire:click="closeVoluntarioModal" type="button" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button><button wire:click="saveVoluntarioModal" wire:loading.attr="disabled" type="button" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-60">{{ $editVoluntarioIndex === null ? 'Agregar voluntario' : 'Guardar cambios' }}</button></div>
            </div></div>
        </div>
    @endif

    @if($showNoParticipacionModal)
        @php($personaNoParticipante = $this->participanteNoParticipacionActual())
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="no-participacion-modal-title">
            <div class="fixed inset-0 bg-black/50" wire:click="closeNoParticipacionModal"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700"><h4 id="no-participacion-modal-title" class="font-semibold">Marcar como no participante</h4><p class="mt-1 text-sm text-gray-500">Solo se excluye del Informe Final; el registro y la planificación original del proyecto se conservan.</p></div>
                    <div class="space-y-4 p-5">
                        <div class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>Persona:</strong> {{ $personaNoParticipante['nombre'] ?? 'Participante' }}<br><strong>Rol o grupo:</strong> {{ $personaNoParticipante['tipo_participacion'] ?? $personaNoParticipante['universidad'] ?? Str::headline($tipoParticipanteNoParticipacion) }}</div>
                        <div><label class="{{ $label }}">Estado</label><select wire:model="estadoNoParticipacion" class="{{ $input }}"><option value="no_participo">No participó</option><option value="no_finalizo">No finalizó</option><option value="retirado">Retirado</option></select></div>
                        <div><label class="{{ $label }}">Motivo u observación <span class="text-red-500">*</span></label><textarea rows="4" maxlength="500" wire:model="observacionNoParticipacion" class="{{ $input }}" placeholder="Explique por qué la persona no participó."></textarea><p class="mt-1 text-xs text-gray-500">Entre 10 y 500 caracteres.</p>@error('observacionNoParticipacion')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-3 dark:border-gray-700"><button type="button" wire:click="closeNoParticipacionModal" class="rounded-md bg-gray-100 px-3 py-2 text-sm">Cancelar</button><button type="button" wire:click="confirmarNoParticipacion" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white">Confirmar</button></div>
                </div>
            </div>
        </div>
    @endif
</div>
