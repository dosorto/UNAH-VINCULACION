@php
    $steps = [1=>'Info General',2=>'Equipo',3=>'Participantes',4=>'Contrapartes',5=>'Resultados',6=>'Ejecución',7=>'Evaluación',8=>'Anexos'];
    $input = 'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
    // Estilo para campos de solo lectura (datos precargados que no se pueden editar):
    // fondo gris, sin resaltado de foco y cursor "no editable" para que se distingan a simple vista.
    $readonly = 'w-full rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 focus:outline-none focus:ring-0 focus:border-gray-200';
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

    <main class="bg-white dark:bg-gray-900 shadow rounded-lg p-5">
        @if($currentStep === 1)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paso 1: Información general y beneficiarios</h2>
            @php
                $fechaRegistro = filled($general['fecha_registro'] ?? null) ? \Illuminate\Support\Carbon::parse($general['fecha_registro'])->format('d/m/Y') : null;
                $precargados = [
                    '1. Nombre del Programa/Proyecto' => $general['nombre_proyecto'] ?? null,
                    '2. Número de registro' => $general['numero_registro'] ?: 'Pendiente de asignación',
                    '3. Fecha de registro' => $fechaRegistro,
                    'Facultad / Centro Regional / Instituto Tecnológico' => $general['facultad_centro'] ?? null,
                    'Escuela / Departamento / Instituto / Observatorio / Consultorio / Centro especializado' => $general['departamento_academico'] ?? null,
                    'Carrera' => filled($general['carrera'] ?? null) ? $general['carrera'] : 'No aplica',
                    'Programa de vinculación' => $general['programa_vinculacion'] ?? null,
                    'Línea de investigación' => $general['linea_investigacion'] ?? null,
                    '5. Modalidad' => $general['modalidad'] ?? null,
                    '6. Alineamiento con ejes prioritarios de la UNAH' => $general['ejes_prioritarios'] ?? null,
                    '7. Categoría del proyecto' => $general['categoria'] ?? null,
                    'Tiempo total del proyecto en semanas' => trim(($informe->duracion_semanas ?? 0).' semanas'),
                ];
            @endphp
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/40">
                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400"><span class="font-medium text-blue-600 dark:text-blue-400">Datos precargados</span> del proyecto (solo lectura).</p>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-1.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($precargados as $etiqueta => $valor)
                        <div class="min-w-0">
                            <dt class="text-[11px] font-medium uppercase tracking-wide text-gray-400">{{ $etiqueta }}</dt>
                            <dd class="text-sm text-gray-800 dark:text-gray-200">@if(filled($valor)){{ $valor }}@else<span class="text-xs italic text-gray-400">No aplica</span>@endif</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div><label class="{{ $label }}">Fecha de inicio <span class="text-red-500">*</span></label><input type="date" wire:model="general.fecha_inicio" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Fecha de finalización <span class="text-red-500">*</span></label><input type="date" wire:model="general.fecha_finalizacion" class="{{ $input }}"></div>
            </div>
            <h3 class="mt-6 font-semibold">10. Sitio de ejecución del proyecto</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Puede seleccionar más de un país, departamento y municipio.</p>
            @php
                $paisOptions = $this->paisesTerritorio->mapWithKeys(fn ($p) => [$p->nombre => $p->nombre])->all();
                $sinDepartamento = empty($departamentosTerritorioSel);
            @endphp
            <div class="mt-3 grid gap-4 lg:grid-cols-3">
                <div>
                    <label class="{{ $label }}">País <span class="text-red-500">*</span></label>
                    <x-multi-select wire:model="paisesTerritorioSel" :options="$paisOptions" search-placeholder="Buscar país…" placeholder="Sin país" />
                    @error('paisesTerritorioSel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $label }}">Departamento <span class="text-red-500">*</span></label>
                    <x-multi-select wire:model="departamentosTerritorioSel" :options="$this->departamentosTerritorio" search-placeholder="Buscar departamento…" placeholder="Sin departamento" />
                </div>
                <div>
                    <label class="{{ $label }}">Municipio <span class="text-red-500">*</span></label>
                    <x-multi-select wire:model="municipiosTerritorioSel" :options="$this->municipiosTerritorio" :disabled="$sinDepartamento" search-placeholder="Buscar municipio…" placeholder="Seleccione un departamento" />
                </div>
            </div>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div><label class="{{ $label }}">Región <span class="text-red-500">*</span></label><input type="text" wire:model.live.debounce.1000ms="general.region" class="{{ $input }}">@error('general.region')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="{{ $label }}">Aldea (incluye ciudad) <span class="text-red-500">*</span></label><input type="text" wire:model.live.debounce.1000ms="general.aldea_ciudad" class="{{ $input }}">@error('general.aldea_ciudad')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="{{ $label }}">Caserío <span class="text-red-500">*</span></label><input type="text" wire:model.live.debounce.1000ms="general.caserio" class="{{ $input }}">@error('general.caserio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <h3 class="mt-6 font-semibold">9. Beneficiarios directos <span class="text-red-500">*</span></h3>
            @error('beneficiarios')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Indique las cantidades en números. Los tres desgloses deben sumar el mismo total.</p>
            @php
                $celInput = 'w-16 rounded border border-gray-300 bg-white px-2 py-1 text-center text-sm focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800';
                $errBenef = 'Debe ser un número entero.';
                $rangosEdad = ['edad_0_10'=>'0 – 10 años','edad_11_18'=>'11 – 18 años','edad_19_25'=>'19 – 25 años','edad_26_35'=>'26 – 35 años','edad_36_50'=>'36 – 50 años','edad_51_65'=>'51 – 65 años','edad_66_80'=>'66 – 80 años','edad_81_mas'=>'Mayor de 81 años'];
                $etnias = ['indigena'=>'Indígena','afrodescendiente'=>'Afrodescendiente','mestizo'=>'Mestizo'];
            @endphp
            <div class="mt-3 grid gap-4 lg:grid-cols-2">
                <div class="space-y-4">
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                            <span class="text-sm font-semibold">Total por sexo</span>
                            @foreach(['hombres'=>'Hombres','mujeres'=>'Mujeres'] as $field=>$name)
                                <label class="flex items-center gap-2 text-sm">{{ $name }}
                                    <input type="number" min="0" step="1" inputmode="numeric" wire:model.live="beneficiarios.{{ $field }}" class="{{ $celInput }}">
                                </label>
                            @endforeach
                            <span class="ml-auto rounded bg-gray-100 px-2 py-1 text-sm dark:bg-gray-800">Total: <strong>{{ $this->totalesBeneficiarios['sexo'] }}</strong></span>
                        </div>
                        @error('beneficiarios.hombres')<p class="mt-1 text-xs text-red-600">{{ $errBenef }}</p>@enderror
                        @error('beneficiarios.mujeres')<p class="mt-1 text-xs text-red-600">{{ $errBenef }}</p>@enderror
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-2 text-left font-semibold" colspan="2">Por tipo de etnia</th></tr>
                                <tr class="text-xs text-gray-500"><th class="px-3 py-1.5 text-left"></th><th class="px-3 py-1.5">Hombres</th><th class="px-3 py-1.5">Mujeres</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($etnias as $key=>$name)
                                    <tr>
                                        <td class="px-3 py-1.5">{{ $name }}</td>
                                        <td class="px-3 py-1.5 text-center"><input type="number" min="0" step="1" inputmode="numeric" wire:model.live="beneficiarios.{{ $key }}_hombres" class="{{ $celInput }}"></td>
                                        <td class="px-3 py-1.5 text-center"><input type="number" min="0" step="1" inputmode="numeric" wire:model.live="beneficiarios.{{ $key }}_mujeres" class="{{ $celInput }}"></td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 dark:bg-gray-800"><td class="px-3 py-1.5 font-medium" colspan="2">Total etnia</td><td class="px-3 py-1.5 text-center font-semibold">{{ $this->totalesBeneficiarios['etnia'] }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-2 text-left font-semibold">Rango de edad</th><th class="px-3 py-2 font-semibold">Cantidad</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($rangosEdad as $field=>$name)
                                <tr>
                                    <td class="px-3 py-1.5">{{ $name }}</td>
                                    <td class="px-3 py-1.5 text-center"><input type="number" min="0" step="1" inputmode="numeric" wire:model.live="beneficiarios.{{ $field }}" class="{{ $celInput }}"></td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50 dark:bg-gray-800"><td class="px-3 py-1.5 font-medium">Total por edad</td><td class="px-3 py-1.5 text-center font-semibold">{{ $this->totalesBeneficiarios['edad'] }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @if(count(array_unique($this->totalesBeneficiarios)) > 1)<p class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">Advertencia: las distribuciones por sexo, edad y etnia no suman el mismo total ({{ implode(' / ', $this->totalesBeneficiarios) }}).</p>@endif
        @elseif($currentStep === 2)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paso 2: Equipo ejecutor</h2>
            <h3 class="mt-5 font-semibold">Equipo docente</h3>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre','N.º empleado','Correo','Categoría','Departamento','Horas dedicadas *','Participación','Estado','Acciones'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>@foreach($equipo as $i=>$row)<tr class="border-t dark:border-gray-700 {{ ($row['estado_participacion'] ?? 'activo') === 'activo' ? '' : 'opacity-60' }}"><td class="px-3 py-2">{{ $row['nombre'] }} @if($row['es_coordinador'])<span class="text-xs text-blue-700">Coordinador</span>@endif @if(($row['estado_participacion'] ?? 'activo') !== 'activo')<p class="mt-1 text-xs">{{ $row['observacion_no_participacion'] }}</p>@endif</td><td class="px-3 py-2">{{ $row['numero_empleado'] }}</td><td class="px-3 py-2">{{ $row['correo'] }}</td><td class="px-3 py-2">{{ $row['categoria'] }}</td><td class="px-3 py-2">{{ $row['departamento'] }}</td><td class="px-3 py-2"><input type="number" min="0" step="0.5" wire:model="equipo.{{ $i }}.horas_dedicadas" class="{{ $input }} min-w-24">@error('equipo.'.$i.'.horas_dedicadas')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</td><td class="px-3 py-2"><span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">{{ $row['tipo_participacion'] }}</span></td><td class="px-3 py-2">{{ $this->estadoParticipacionVisual($row['estado_participacion'] ?? 'activo') }}</td><td class="px-3 py-2">@if(($row['estado_participacion'] ?? 'activo') === 'activo')<button type="button" wire:click="openNoParticipacionModal('equipo',{{ $i }})" class="text-sm text-blue-700">Cambiar estado</button>@else<button type="button" x-on:click.prevent="confirmDialog('¿Restaurar participación?').then((ok) => ok && $wire.restaurarParticipante('equipo',{{ $i }}))"  class="text-sm text-green-700">Restaurar participación</button>@endif</td></tr>@endforeach</tbody></table></div>
            <div class="mt-6 flex items-center justify-between"><h3 class="font-semibold">Cooperación internacional</h3><button type="button" wire:click="openCooperacionModal" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar integrante</button></div>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre completo','Pasaporte','Correo electrónico','País','Universidad','Horas dedicadas *','Estado','Acciones'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>
                @forelse($cooperacion as $i=>$row)
                @php $cActivo = ($row['estado_participacion'] ?? 'activo') === 'activo'; @endphp
                <tr class="border-t dark:border-gray-700 {{ $cActivo ? '' : 'opacity-60' }}">
                <td class="px-3 py-2 font-medium">{{ $row['nombre'] ?: '—' }}@if(!$cActivo)<p class="mt-1 text-xs font-normal">{{ $row['observacion_no_participacion'] }}</p>@endif</td>
                <td class="px-3 py-2">{{ $row['pasaporte'] ?: '—' }}</td>
                <td class="px-3 py-2">{{ $row['correo'] ?: '—' }}</td>
                <td class="px-3 py-2">{{ $row['pais'] ?: '—' }}</td>
                <td class="px-3 py-2">{{ $row['universidad'] ?: '—' }}</td>
                <td class="px-3 py-2 text-center">{{ $row['horas_dedicadas'] ?: 0 }}</td>
                <td class="px-3 py-2">{{ $this->estadoParticipacionVisual($row['estado_participacion'] ?? 'activo') }}</td>
                <td class="px-3 py-2 whitespace-nowrap">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="openCooperacionModal({{ $i }})" class="text-sm text-blue-700 dark:text-blue-400">Editar</button>
                        @if($cActivo)
                            <button type="button" wire:click="openNoParticipacionModal('cooperacion',{{ $i }})" class="text-sm text-blue-700 dark:text-blue-400">Cambiar estado</button>
                        @else
                            <button type="button" x-on:click.prevent="confirmDialog('¿Restaurar participación?').then((ok) => ok && $wire.restaurarParticipante('cooperacion',{{ $i }}))"  class="text-sm text-green-700">Restaurar</button>
                        @endif
                        <button type="button" x-on:click.prevent="confirmDialog('¿Eliminar este integrante de cooperación internacional?', { type: 'danger' }).then((ok) => ok && $wire.quitarFila('cooperacion',{{ $i }}))"  class="text-sm text-red-600">Borrar</button>
                    </div>
                </td>
            </tr>@empty<tr><td colspan="8" class="p-3 text-gray-500">No hay cooperación internacional registrada.</td></tr>@endforelse</tbody></table></div>

            @if($showCooperacionModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
                <div class="fixed inset-0 bg-black/50" wire:click="closeCooperacionModal"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-xl rounded-lg bg-white shadow-xl dark:bg-gray-900">
                        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $editCooperacionIndex !== null ? 'Editar' : 'Nuevo' }} integrante de cooperación internacional</h4>
                            <button type="button" wire:click="closeCooperacionModal" class="text-lg leading-none text-gray-500 hover:text-gray-800">✕</button>
                        </div>
                        <div class="grid gap-4 p-5 sm:grid-cols-2">
                            @if($editCooperacionIndex === null)
                            <div class="sm:col-span-2 rounded-md border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950/30">
                                <label class="{{ $label }}">Seleccionar de la lista de integrantes ya registrados</label>
                                <select wire:model.live="cooperacionIntegranteId" class="{{ $input }}">
                                    <option value="">— Registrar uno nuevo / escribir manualmente —</option>
                                    @foreach($this->integrantesInternacionalesCatalogo as $integrante)
                                        <option value="{{ $integrante->id }}">{{ $integrante->nombre_completo }} ({{ $integrante->pais ?: 'sin país' }})</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Al elegir uno se rellenan los campos; puede ajustarlos.</p>
                            </div>
                            @endif
                            <div class="sm:col-span-2"><label class="{{ $label }}">Nombre completo <span class="text-red-500">*</span></label><input wire:model="cooperacionModal.nombre" class="{{ $input }}">@error('cooperacionModal.nombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Pasaporte</label><input wire:model="cooperacionModal.pasaporte" class="{{ $input }}">@error('cooperacionModal.pasaporte')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Correo electrónico</label><input type="email" wire:model="cooperacionModal.correo" class="{{ $input }}">@error('cooperacionModal.correo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">País</label><input wire:model="cooperacionModal.pais" class="{{ $input }}">@error('cooperacionModal.pais')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Universidad</label><input wire:model="cooperacionModal.universidad" class="{{ $input }}">@error('cooperacionModal.universidad')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Horas dedicadas <span class="text-red-500">*</span></label><input type="number" min="0" step="0.5" wire:model="cooperacionModal.horas_dedicadas" class="{{ $input }}">@error('cooperacionModal.horas_dedicadas')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-3 dark:border-gray-700">
                            <button type="button" wire:click="closeCooperacionModal" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>
                            <button type="button" wire:click="saveCooperacionModal" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">{{ $editCooperacionIndex !== null ? 'Guardar cambios' : 'Agregar' }}</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @elseif($currentStep === 3)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paso 3: Estudiantes y voluntarios</h2>
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
                            @php
                                $extra = function ($r, $p) {
                                    return $r > $p
                                        ? ' <span class="text-green-700 dark:text-green-400">(+' . ($r - $p) . ')</span>'
                                        : '';
                                };
                            @endphp
                            <div class="rounded bg-blue-50 p-3 text-sm dark:bg-blue-950/30"><strong>Registrados</strong><p>Hombres: {{ $grupo['hombres_registrados'] }} de {{ $grupo['hombres_planificados'] }}{!! $extra($grupo['hombres_registrados'],$grupo['hombres_planificados']) !!}</p><p>Mujeres: {{ $grupo['mujeres_registradas'] }} de {{ $grupo['mujeres_planificadas'] }}{!! $extra($grupo['mujeres_registradas'],$grupo['mujeres_planificadas']) !!}</p><p>Total: {{ $grupo['total_registrado'] }} de {{ $grupo['total_planificado'] }}{!! $extra($grupo['total_registrado'],$grupo['total_planificado']) !!}</p></div>
                            <div class="rounded bg-amber-50 p-3 text-sm dark:bg-amber-950/30"><strong>Pendientes</strong><p>Hombres: {{ $grupo['hombres_pendientes'] }}</p><p>Mujeres: {{ $grupo['mujeres_pendientes'] }}</p><p>Total: {{ $grupo['hombres_pendientes'] + $grupo['mujeres_pendientes'] }}</p></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">La cantidad planificada es un aproximado; puede registrar más estudiantes de los previstos.</p>
                        @if($grupo['total_planificado'] > 0 && ($grupo['hombres_pendientes'] > 0 || $grupo['mujeres_pendientes'] > 0))
                            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                                <label class="{{ $label }}" for="observacion-grupo-{{ $grupo['id'] }}">Observación por estudiantes no incorporados <span class="text-red-500">*</span></label>
                                <textarea id="observacion-grupo-{{ $grupo['id'] }}" rows="3" maxlength="1000" wire:model.live.debounce.1000ms="gruposEstudiantes.{{ $grupo['indice_formulario'] }}.observacion_no_cumplimiento" class="{{ $input }} mt-1 w-full" placeholder="Explique la diferencia entre la planificación y la participación real."></textarea>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Explique por qué no se incorporó la totalidad de participantes planificados.</p>
                                @error("gruposEstudiantes.{$grupo['indice_formulario']}.observacion_no_cumplimiento")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endif
                        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre','Sexo','Cuenta','Carrera','Horas','Estado','Acciones'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>@forelse($grupo['estudiantes'] as $row)<tr class="border-t dark:border-gray-700 {{ ($row['estado_participacion'] ?? 'activo') === 'activo' ? '' : 'opacity-60' }}"><td class="px-3 py-2">{{ $row['nombre'] }}@if(($row['estado_participacion'] ?? 'activo') !== 'activo')<p class="mt-1 text-xs">{{ $row['observacion_no_participacion'] }}</p>@endif</td><td class="px-3 py-2">{{ $this->sexoVisual($row['sexo'] ?? null) }}</td><td class="px-3 py-2">{{ $row['numero_cuenta'] }}</td><td class="px-3 py-2">{{ $row['carrera'] }}</td><td class="px-3 py-2"><input type="number" min="0" wire:model.blur.number="estudiantes.{{ $row['indice_formulario'] }}.horas_dedicadas" class="{{ $input }} w-24"></td><td class="px-3 py-2">{{ $this->estadoParticipacionVisual($row['estado_participacion'] ?? 'activo') }}</td><td class="px-3 py-2"><div class="flex flex-wrap gap-2">@if(($row['estado_participacion'] ?? 'activo') === 'activo')<button type="button" wire:click="openEstudianteModal({{ $row['indice_formulario'] }})" class="text-sm text-blue-700">Editar</button><button type="button" wire:click="openNoParticipacionModal('estudiante',{{ $row['indice_formulario'] }})" class="text-sm text-blue-700">Cambiar estado</button>@else<button type="button" x-on:click.prevent="confirmDialog('¿Restaurar participación?').then((ok) => ok && $wire.restaurarParticipante('estudiante',{{ $row['indice_formulario'] }}))"  class="text-sm text-green-700">Restaurar participación</button>@endif
@if(($row['origen'] ?? 'PROYECTO') !== 'PROYECTO')<button type="button" x-on:click.prevent="confirmDialog('¿Quitar este estudiante del grupo?', { type: 'danger' }).then((ok) => ok && $wire.quitarFila('estudiantes',{{ $row['indice_formulario'] }}))"  class="text-sm text-red-600">Quitar</button>@endif</div></td></tr>@empty<tr><td colspan="7" class="p-3 text-gray-500">No hay estudiantes registrados en este grupo.</td></tr>@endforelse</tbody></table></div>
                    </section>
                @empty
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><p>El proyecto no tiene grupos de estudiantes planificados en el FORM-DVUS-001. Esto no bloquea el registro de la ejecución real.</p><div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end"><div class="flex-1"><label class="{{ $label }}">Tipo de participación real</label><select wire:model="tipoParticipacionSinPlanificacion" class="{{ $input }}"><option value="practica_asignatura">Práctica de asignatura</option><option value="pps_servicio_social">Servicio Social o PPS</option><option value="voluntariado">Voluntariado estudiantil</option></select></div><button type="button" wire:click="openEstudianteSinPlanificacionModal" class="{{ $button }} bg-blue-600 text-white">Agregar estudiante</button></div></div>
                @endforelse
            </div>
            <section class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700"><h4 class="font-semibold">Resumen general de estudiantes</h4><div class="mt-3 grid gap-3 sm:grid-cols-3">@foreach(['planificados'=>'Planificados','registrados'=>'Registrados','pendientes'=>'Pendientes'] as $clave=>$titulo)<div class="rounded bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>{{ $titulo }}</strong><p>Hombres: {{ $this->resumenPlanificacionEstudiantes[$clave]['hombres'] }}</p><p>Mujeres: {{ $this->resumenPlanificacionEstudiantes[$clave]['mujeres'] }}</p><p>Total: {{ $this->resumenPlanificacionEstudiantes[$clave]['total'] }}</p></div>@endforeach</div></section>
            <div class="mt-6 flex items-center justify-between"><h3 class="font-semibold">Voluntarios</h3><button type="button" wire:click="openVoluntarioModal" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar voluntario</button></div>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nombre','Sexo','Identidad','Departamento','Tipo','Horas','Estado','Acciones'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead><tbody>@forelse($voluntarios as $i=>$row)<tr class="border-t dark:border-gray-700 {{ ($row['estado_participacion'] ?? 'activo') === 'activo' ? '' : 'opacity-60' }}"><td class="px-3 py-2 font-medium">{{ $row['nombre'] }}@if(($row['estado_participacion'] ?? 'activo') !== 'activo')<p class="mt-1 text-xs font-normal">{{ $row['observacion_no_participacion'] }}</p>@endif</td><td class="px-3 py-2"><span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $this->sexoVisual($row['sexo'] ?? null) }}</span></td><td class="px-3 py-2">{{ $row['identidad'] ?: '—' }}</td><td class="px-3 py-2">{{ $row['departamento'] ?: '—' }}</td><td class="px-3 py-2">{{ Str::headline($row['tipo'] ?? '') }}</td><td class="px-3 py-2">{{ $row['horas_dedicadas'] ?: 0 }}</td><td class="px-3 py-2">{{ $this->estadoParticipacionVisual($row['estado_participacion'] ?? 'activo') }}</td><td class="px-3 py-2"><div class="flex gap-2">@if(($row['estado_participacion'] ?? 'activo') === 'activo')<button type="button" wire:click="openVoluntarioModal({{ $i }})" class="text-sm text-blue-700 dark:text-blue-400">Editar</button><button type="button" wire:click="openNoParticipacionModal('voluntario',{{ $i }})" class="text-sm text-blue-700 dark:text-blue-400">Cambiar estado</button>@else<button type="button" x-on:click.prevent="confirmDialog('¿Restaurar participación?').then((ok) => ok && $wire.restaurarParticipante('voluntario',{{ $i }}))"  class="text-sm text-green-700">Restaurar participación</button>@endif</div></td></tr>@empty<tr><td colspan="8" class="p-3 text-gray-500">No hay voluntarios registrados.</td></tr>@endforelse</tbody></table></div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2"><div class="rounded bg-gray-50 p-3 text-sm dark:bg-gray-800">Hombres: <strong>{{ $this->totalesParticipacion['voluntarios_hombres'] }}</strong></div><div class="rounded bg-gray-50 p-3 text-sm dark:bg-gray-800">Mujeres: <strong>{{ $this->totalesParticipacion['voluntarios_mujeres'] }}</strong></div></div>
            <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                <label for="observacion-voluntarios" class="{{ $label }}">Observación por voluntarios no incorporados</label>
                <textarea id="observacion-voluntarios" rows="3" maxlength="1000" wire:model.live.debounce.1000ms="general.observacion_voluntarios_no_incorporados" class="{{ $input }} mt-1 w-full" placeholder="Explique, si corresponde, por qué no hubo participación voluntaria."></textarea>
                <p class="mt-1 text-xs text-gray-500">El proyecto no contiene una planificación desglosada de voluntarios; esta observación es opcional.</p>
                @error('general.observacion_voluntarios_no_incorporados')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @elseif($currentStep === 4)
            @php
                $tiposContraparte = ['gobierno_nacional'=>'Gobierno nacional','gobierno_municipal'=>'Gobierno municipal','ong'=>'ONG','sociedad_civil'=>'Sociedad civil organizada','sector_privado'=>'Sector privado','internacional'=>'Internacional'];
                $tiposInstrumento = ['carta_formal'=>'Carta formal de solicitud a la unidad académica','carta_intenciones'=>'Carta de intenciones con la UNAH','convenio_marco'=>'Convenio marco con la UNAH'];
            @endphp
            <div class="flex items-center justify-between">
                <div><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 4: Contrapartes</h2><p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Las contrapartes del proyecto vienen precargadas; registre aquí lo ocurrido durante la ejecución.</p></div>
                <button type="button" wire:click="openContraparteModal" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar contraparte</button>
            </div>
            <p class="mt-3 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                Cada contraparte debe tener el <strong>tipo de instrumento que da lugar a la alianza</strong> y la <strong>breve descripción de los compromisos que fueron asumidos</strong>. Los aportes de la contraparte se registran por concepto en el paso 7 (X. Ejecución presupuestaria).
            </p>
            @php($erroresContrapartes = collect($errors->getMessages())->filter(fn ($m, $k) => preg_match('/^contrapartes\.\d+$/', $k))->flatten())
            @if($erroresContrapartes->isNotEmpty())
                <div class="mt-3 rounded-md border border-red-300 bg-red-50 px-3 py-2 dark:border-red-700 dark:bg-red-900/20">
                    @foreach($erroresContrapartes as $mensaje)<p class="text-xs text-red-700 dark:text-red-300">{{ $mensaje }}</p>@endforeach
                </div>
            @endif

            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left dark:bg-gray-800">
                        <tr>@foreach(['Contraparte','Contacto','Instrumento / apoyo','Acciones'] as $h)<th class="px-3 py-2 font-semibold">{{ $h }}</th>@endforeach</tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($this->contrapartesConInstrumentos as $i=>$row)
                            @php($esPlanificada = ($row['origen'] ?? 'PLANIFICADO') === 'PLANIFICADO')
                            @php($pendientesContraparte = $this->camposPendientesContraparte($row))
                            <tr class="align-top">
                                <td class="px-3 py-2">
                                    <p class="font-medium">{{ $row['nombre'] ?: '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ $tiposContraparte[$row['tipo']] ?? Str::headline(str_replace('_',' ',(string) $row['tipo'])) }}</p>
                                    @if($esPlanificada)<span class="mt-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Del proyecto</span>@else<span class="mt-1 inline-block rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Agregada en la ejecución</span>@endif
                                    @if($pendientesContraparte !== [])
                                        <p class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400">Pendiente: {{ implode(', ', $pendientesContraparte) }}.</p>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    @if($row['contacto'])<p>{{ $row['contacto'] }}@if($row['cargo']) · {{ $row['cargo'] }}@endif</p>@endif
                                    @if($row['correo'])<p class="text-gray-500">{{ $row['correo'] }}</p>@endif
                                    @if($row['telefono'])<p class="text-gray-500">{{ $row['telefono'] }}</p>@endif
                                    @if(!$row['contacto'] && !$row['correo'] && !$row['telefono'])<span class="text-gray-400">—</span>@endif
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <p>{{ $tiposInstrumento[$row['tipo_instrumento']] ?? 'Instrumento sin especificar' }}</p>
                                    <p class="mt-1">
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-medium {{ $row['estado_instrumento']==='Disponible' ? 'bg-green-100 text-green-800' : ($row['estado_instrumento']==='Pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">Doc.: {{ $row['estado_instrumento'] }}</span>
                                        <span class="ml-1 rounded-full px-2 py-0.5 text-[10px] font-medium {{ ($row['existe_apoyo'] ?? true) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">Apoyo: {{ ($row['existe_apoyo'] ?? true) ? 'Sí' : 'No' }}</span>
                                    </p>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" wire:click="openContraparteModal({{ $i }})" class="text-sm text-blue-700 dark:text-blue-400">Editar</button>
                                        @unless($esPlanificada)<button type="button" x-on:click.prevent="confirmDialog('¿Quitar esta contraparte del informe?', { type: 'danger' }).then((ok) => ok && $wire.quitarFila('contrapartes',{{ $i }}))"  class="text-sm text-red-600">Quitar</button>@endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">No hay contrapartes registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @error('contrapartes')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror

            @if($showContraparteModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
                <div class="fixed inset-0 bg-black/50" wire:click="closeContraparteModal"></div>
                <div class="relative flex min-h-full items-start justify-center p-4">
                    <div class="relative my-4 w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-gray-900">
                        <div class="sticky top-0 flex items-center justify-between rounded-t-lg border-b border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $editContraparteIndex !== null ? 'Editar' : 'Nueva' }} contraparte @if($contraparteModalEsPlanificada)<span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Del proyecto</span>@endif</h4>
                            <button type="button" wire:click="closeContraparteModal" class="text-lg leading-none text-gray-500 hover:text-gray-800">✕</button>
                        </div>
                        <div class="space-y-5 p-5">
                            @if($contraparteModalEsPlanificada)<p class="rounded-md bg-gray-50 p-2 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">Los datos de identificación provienen del registro del proyecto (solo lectura). Registre el instrumento, el apoyo, los compromisos cumplidos y los aportes de la ejecución.</p>@endif

                            <section>
                                <h5 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Identificación</h5>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div><label class="{{ $label }}">Nombre @unless($contraparteModalEsPlanificada)<span class="text-red-500">*</span>@endunless</label><input wire:model="contraparteModal.nombre" @readonly($contraparteModalEsPlanificada) class="{{ $contraparteModalEsPlanificada ? $readonly : $input }}">@error('contraparteModal.nombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                    <div><label class="{{ $label }}">Tipo de contraparte @unless($contraparteModalEsPlanificada)<span class="text-red-500">*</span>@endunless</label>@if($contraparteModalEsPlanificada)<input value="{{ $tiposContraparte[$contraparteModal['tipo']] ?? '' }}" readonly class="{{ $readonly }}">@else<select wire:model="contraparteModal.tipo" class="{{ $input }}">@foreach($tiposContraparte as $value=>$name)<option value="{{ $value }}">{{ $name }}</option>@endforeach</select>@endif</div>
                                </div>
                            </section>

                            <section>
                                <h5 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Contacto</h5>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach(['contacto'=>['Nombre del contacto directo',true],'cargo'=>['Cargo del contacto del proyecto',true],'correo'=>['Correo electrónico',true],'telefono'=>['Teléfono',false]] as $f=>$meta)
                                        <div><label class="{{ $label }}">{{ $meta[0] }} @if($meta[1] && !$contraparteModalEsPlanificada)<span class="text-red-500">*</span>@endif</label><input @if($f==='correo') type="email" @endif wire:model="contraparteModal.{{ $f }}" @readonly($contraparteModalEsPlanificada) class="{{ $contraparteModalEsPlanificada ? $readonly : $input }}">@error('contraparteModal.'.$f)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                    @endforeach
                                </div>
                            </section>

                            <section>
                                <h5 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Alianza y apoyo</h5>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="{{ $label }}">Tipo de instrumento que da lugar a la alianza <span class="text-red-500">*</span></label>
                                        @if($contraparteModalInstrumentoHeredado)
                                            <input value="{{ $tiposInstrumento[$contraparteModal['tipo_instrumento']] ?? '' }}" readonly class="{{ $readonly }}">
                                            <p class="mt-1 text-xs text-gray-500">Definido en el registro del proyecto.</p>
                                        @else
                                            <select wire:model="contraparteModal.tipo_instrumento" class="{{ $input }}"><option value="">— Seleccione —</option>@foreach($tiposInstrumento as $value=>$name)<option value="{{ $value }}">{{ $name }}</option>@endforeach</select>
                                        @endif
                                        @error('contraparteModal.tipo_instrumento')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="flex items-end pb-2"><label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="contraparteModal.existe_apoyo" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">El proyecto se ejecutó con apoyo de esta contraparte</label></div>
                                </div>

                                @php($instrumentoActual = $editContraparteIndex !== null ? collect($this->contrapartesConInstrumentos[$editContraparteIndex]['instrumentos'] ?? [])->first() : null)
                                <div class="mt-3">
                                    <label class="{{ $label }}">Documento del instrumento</label>
                                    @if($instrumentoActual)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded border border-gray-200 bg-gray-50 p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ $instrumentoActual['nombre_archivo'] ?: 'Archivo sin nombre' }}</span>
                                            @if($this->anexoDocumentoUrl($instrumentoActual['id'] ?? null))<a href="{{ $this->anexoDocumentoUrl($instrumentoActual['id']) }}" target="_blank" rel="noopener" class="text-xs font-medium text-blue-700 dark:text-blue-300">Ver documento</a>@endif
                                        </div>
                                    @elseif($contraparteModalEsPlanificada)
                                        <p class="text-xs text-gray-500">Sin documento cargado. El respaldo del instrumento se gestiona desde el registro del proyecto.</p>
                                    @else
                                        <input type="file" wire:model="contraparteModalDocumento" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="block w-full text-xs text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-blue-700 dark:text-gray-300">
                                        <p class="mt-1 text-xs text-gray-500">PDF, Word o imagen (máx. 10 MB). Se adjuntará como anexo de esta contraparte.</p>
                                        <div wire:loading wire:target="contraparteModalDocumento" class="mt-1 text-xs text-gray-500">Cargando documento…</div>
                                        @error('contraparteModalDocumento')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    @endif
                                </div>
                            </section>

                            <section>
                                <h5 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Compromisos</h5>
                                <div><label class="{{ $label }}">Breve descripción de los compromisos que fueron asumidos por la contraparte <span class="text-red-500">*</span></label><textarea rows="3" wire:model="contraparteModal.compromisos_asumidos" @readonly(! $contraparteModalCompromisosEditables) class="{{ $contraparteModalCompromisosEditables ? $input : $readonly }}"></textarea>@unless($contraparteModalCompromisosEditables)<p class="mt-1 text-xs text-gray-500">Definidos en el registro del proyecto.</p>@endunless @error('contraparteModal.compromisos_asumidos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            </section>

                            @if($editContraparteIndex !== null && count($this->contrapartesConInstrumentos[$editContraparteIndex]['instrumentos'] ?? []) > 1)
                            <section>
                                <h5 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Instrumentos de formalización y respaldos</h5>
                                <div class="space-y-2">
                                    @foreach($this->contrapartesConInstrumentos[$editContraparteIndex]['instrumentos'] as $instrumento)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded border border-gray-200 bg-gray-50 p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                            <span><strong>{{ $instrumento['descripcion'] ?: 'Instrumento de contraparte' }}</strong><br><span class="text-xs text-gray-500">{{ $instrumento['nombre_archivo'] ?: 'Archivo pendiente' }}</span></span>
                                            @if($this->anexoDocumentoUrl($instrumento['id'] ?? null))<a href="{{ $this->anexoDocumentoUrl($instrumento['id']) }}" target="_blank" rel="noopener" class="text-sm text-blue-700 dark:text-blue-300">Ver documento</a>@endif
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs text-gray-500">La carga de archivos se realiza en el paso Anexos.</p>
                            </section>
                            @endif
                        </div>
                        <div class="sticky bottom-0 flex justify-end gap-2 rounded-b-lg border-t border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <button type="button" wire:click="closeContraparteModal" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>
                            <button type="button" wire:click="saveContraparteModal" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">{{ $editContraparteIndex !== null ? 'Guardar cambios' : 'Agregar' }}</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @elseif($currentStep === 5)
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paso 5: Resultados y ejecución de actividades</h2>
            @include('livewire.proyectos.informe-final.partials.resultados-actividades')
            @if(false)
            <div class="mt-4 rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>Objetivo general:</strong> {{ $general['objetivo_general'] ?: 'No registrado' }}</div>
            <div class="mt-5 flex items-center justify-between"><h3 class="font-semibold">Resultados</h3><button type="button" wire:click="agregarFila('resultados')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar resultado</button></div>
            <div class="mt-3 space-y-4">@foreach($resultados as $i=>$row)<article class="rounded-lg border p-4 dark:border-gray-700"><div class="flex justify-between"><strong>Resultado {{ $i+1 }}</strong><button type="button" wire:click="quitarFila('resultados',{{ $i }})" class="text-sm text-red-600">Quitar</button></div><div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach(['objetivo_especifico'=>'Objetivo específico','resultado_planificado'=>'Resultado planificado','indicador_propuesto'=>'Indicador','unidad_medida'=>'Unidad de medida','producto_logrado'=>'Producto logrado','observaciones'=>'Observaciones'] as $field=>$name)<div class="{{ in_array($field,['objetivo_especifico','resultado_planificado','observaciones']) ? 'sm:col-span-2' : '' }}"><label class="{{ $label }}">{{ $name }}</label><textarea rows="2" wire:model.live.debounce.1000ms="resultados.{{ $i }}.{{ $field }}" class="{{ $input }}"></textarea></div>@endforeach<div><label class="{{ $label }}">Meta numérica</label><input type="number" min="0" step="0.01" wire:model.blur.number="resultados.{{ $i }}.meta_numerica" class="{{ $input }}"></div><div><label class="{{ $label }}">Valor alcanzado</label><input type="number" min="0" step="0.01" wire:model.blur.number="resultados.{{ $i }}.valor_alcanzado" class="{{ $input }}"></div><div><label class="{{ $label }}">Cumplimiento %</label><input type="number" min="0" max="100" step="0.01" wire:model.blur.number="resultados.{{ $i }}.porcentaje_cumplimiento" class="{{ $input }}"></div><div><label class="{{ $label }}">Estado</label><select wire:model.live="resultados.{{ $i }}.estado" class="{{ $input }}"><option value="alcanzado">Alcanzado</option><option value="parcialmente_alcanzado">Parcialmente alcanzado</option><option value="no_alcanzado">No alcanzado</option><option value="no_aplica">No aplica</option></select></div></div></article>@endforeach</div>
            <div class="mt-6 flex items-center justify-between"><h3 class="font-semibold">Actividades</h3><button type="button" wire:click="agregarFila('actividades')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar actividad</button></div>
            <div class="mt-3 space-y-4">@foreach($actividades as $i=>$row)<article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"><div class="flex items-start justify-between gap-3"><div class="min-w-0 flex-1"><label class="{{ $label }}">Nombre de la actividad</label><textarea rows="2" wire:model.live.debounce.1000ms="actividades.{{ $i }}.actividad_planificada" class="{{ $input }}"></textarea></div><button type="button" wire:click="quitarFila('actividades',{{ $i }})" class="mt-6 text-sm text-red-600">Quitar</button></div><div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><div><label class="{{ $label }}">Período</label><div class="grid grid-cols-2 gap-2"><input type="date" wire:model.blur="actividades.{{ $i }}.fecha_inicial" class="{{ $input }}"><input type="date" wire:model.blur="actividades.{{ $i }}.fecha_final" class="{{ $input }}"></div></div><div><label class="{{ $label }}">Estado</label><select wire:model.live="actividades.{{ $i }}.estado" class="{{ $input }}"><option value="ejecutada">Ejecutada</option><option value="parcial">Parcial</option><option value="no_ejecutada">No ejecutada</option></select></div><div><label class="{{ $label }}">Horas</label><input type="number" min="0" wire:model.blur.number="actividades.{{ $i }}.horas_dedicadas" class="{{ $input }}"></div><div><label class="{{ $label }}">Origen</label><select wire:model.live="actividades.{{ $i }}.origen" class="{{ $input }}"><option value="planificada">Planificada</option><option value="emergente">Emergente</option></select></div><div class="sm:col-span-2"><label class="{{ $label }}">Actividad realizada</label><textarea rows="3" wire:model.live.debounce.1000ms="actividades.{{ $i }}.actividad_realizada" class="{{ $input }}"></textarea></div><div class="sm:col-span-2"><label class="{{ $label }}">Medio de verificación</label><textarea rows="3" wire:model.live.debounce.1000ms="actividades.{{ $i }}.medio_verificacion" class="{{ $input }}"></textarea></div><div class="sm:col-span-2"><label class="{{ $label }}">Responsable principal</label><input value="{{ $row['responsable'] ?? '' }}" readonly class="{{ $readonly }}">@error("actividades.$i.responsable")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div><div class="mt-5 rounded-md bg-gray-50 p-3 dark:bg-gray-800"><div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h4 class="text-sm font-semibold">Participantes</h4><p class="text-xs text-gray-500">Se muestran por persona y tipo, sin concatenar nombres.</p></div><div class="flex flex-col gap-2 sm:flex-row"><select wire:model="participanteSeleccion.{{ $i }}" class="{{ $input }} min-w-56"><option value="externo:nuevo">Participante externo</option>@foreach($this->opcionesParticipantesActividad as $group=>$options)<optgroup label="{{ $group }}">@foreach($options as $option)<option value="{{ $option['value'] }}">{{ $option['label'] }}</option>@endforeach</optgroup>@endforeach</select><button type="button" wire:click="agregarParticipanteActividad({{ $i }})" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar participante</button></div></div>@error("actividades.$i.participantes")<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror<div class="mt-3 flex flex-wrap gap-2">@forelse(($row['participantes'] ?? []) as $pi=>$participant)<details class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"><summary class="cursor-pointer list-none"><span class="font-medium">{{ $participant['nombre'] ?: 'Participante externo' }}</span><span class="ml-1 text-xs text-gray-500">· {{ Str::headline($participant['tipo'] ?? 'externo') }}@if($participant['es_responsable'] ?? false) · Responsable @endif</span></summary><div class="mt-3 grid gap-2 sm:grid-cols-3"><div><label class="{{ $label }}">Nombre</label><input wire:model.live.debounce.1000ms="actividades.{{ $i }}.participantes.{{ $pi }}.nombre" @if(($participant['tipo'] ?? '')!=='externo') readonly @endif class="{{ ($participant['tipo'] ?? '')!=='externo' ? $readonly : $input }}">@error("actividades.$i.participantes.$pi.nombre")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="{{ $label }}">Rol</label><input wire:model.live.debounce.1000ms="actividades.{{ $i }}.participantes.{{ $pi }}.rol" class="{{ $input }}"></div><div><label class="{{ $label }}">Horas</label><input type="number" min="0" wire:model.blur.number="actividades.{{ $i }}.participantes.{{ $pi }}.horas_dedicadas" class="{{ $input }}"></div><button type="button" wire:click="marcarResponsableActividad({{ $i }},{{ $pi }})" @disabled($participant['es_responsable'] ?? false) class="text-left text-xs text-blue-600 disabled:text-gray-500">{{ ($participant['es_responsable'] ?? false) ? 'Responsable principal' : 'Marcar como responsable' }}</button><button type="button" wire:click="quitarParticipanteActividad({{ $i }},{{ $pi }})" class="text-left text-xs text-red-600">Quitar participante</button></div></details>@empty<span class="text-sm text-gray-500">No hay participantes relacionados con esta actividad.</span>@endforelse</div></div></article>@endforeach</div>
            @endif
        @elseif($currentStep === 6)
            <?php
                $thc = 'px-3 py-2 text-left font-semibold text-xs uppercase tracking-wide text-gray-500';
                $tdc = 'px-3 py-2 align-top';
            ?>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Paso 6: Reflexión, transformación y sostenibilidad</h2>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Redacte la narrativa del cierre y registre las acciones y ODS en forma de fichas.</p>

            <h3 class="font-semibold">Narrativa del cierre</h3>
            <div class="mt-4 space-y-3">@foreach(['dificultades'=>'1. Descripción de las dificultades que se presentaron en la ejecución del proyecto','acciones_dificultades'=>'1. Acciones realizadas para afrontar las dificultades','lecciones_aprendidas'=>'2. Lecciones aprendidas','buenas_practicas'=>'3. Buenas prácticas (resaltar aspectos que contribuirán a mejorar el proceso académico de su unidad académica a partir de la experiencia)','problema_inicial'=>'4. Problema inicial identificado','transformacion_lograda'=>'4. Cambios que se logró con el proyecto','mecanismos_sostenibilidad'=>'6. Descripción de los mecanismos aplicados para garantizar la sostenibilidad del proyecto','acciones_contraparte_sostenibilidad'=>'6. Acciones ejecutadas por la contraparte para garantizar la sostenibilidad de las acciones ejecutadas','desafios'=>'7. Desafíos','respuesta_reforma_universitaria'=>'8. Explique brevemente cómo el proyecto respondió a lo esencial de la reforma universitaria','recomendaciones'=>'9. Recomendaciones','bibliografia'=>'10. Bibliografía utilizada'] as $field=>$name)<div class="w-full"><label class="{{ $label }}">{{ $name }} <span class="text-red-500">*</span></label><textarea rows="4" wire:model.live.debounce.1000ms="general.{{ $field }}" @readonly($this->esCampoReflexionHeredado($field)) class="{{ $this->esCampoReflexionHeredado($field) ? $readonly : $input }}"></textarea>@error("general.$field")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>@endforeach</div>

            {{-- Acciones planificadas no ejecutadas --}}
            <section class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div><h3 class="font-semibold">VII. Reporte de acciones planificadas que no fueron ejecutadas</h3><p class="text-xs text-gray-500">Obligatorio para cada actividad marcada como «No ejecutada» en el paso 5.</p></div>
                    <button type="button" wire:click="openAccionModal('accionesNoEjecutadas')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar acción</button>
                </div>
                <div class="mt-3 w-full overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full min-w-[820px] text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Resultado previsto','Actividad planificada','Breve explicación del porqué no se ejecutó','Afectación al proyecto',''] as $h)<th class="{{ $thc }}">{{ $h }}</th>@endforeach</tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($accionesNoEjecutadas as $i=>$row)
                                <tr wire:key="ane-{{ $row['id'] ?? 'nuevo-'.$i }}">
                                    <td class="{{ $tdc }} max-w-[200px]"><p class="whitespace-pre-line">{{ Str::limit($row['resultado_previsto'] ?? '', 120) ?: '—' }}</p></td>
                                    <td class="{{ $tdc }} max-w-[220px]"><p class="whitespace-pre-line">{{ $row['actividad_planificada'] ?: '—' }}</p></td>
                                    <td class="{{ $tdc }} max-w-[240px]"><p class="whitespace-pre-line">{{ Str::limit($row['explicacion'] ?? '', 160) ?: '—' }}</p></td>
                                    <td class="{{ $tdc }} max-w-[220px]"><p class="whitespace-pre-line">{{ Str::limit($row['afectacion_proyecto'] ?? '', 140) ?: '—' }}</p></td>
                                    <td class="{{ $tdc }} whitespace-nowrap text-right"><button type="button" wire:click="openAccionModal('accionesNoEjecutadas',{{ $i }})" class="text-xs text-blue-700 dark:text-blue-400">Editar</button><button type="button" x-on:click.prevent="confirmDialog('¿Quitar esta acción?', { type: 'danger' }).then((ok) => ok && $wire.quitarFila('accionesNoEjecutadas',{{ $i }}))"  class="ml-3 text-xs text-red-600">Quitar</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">Sin acciones registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Acciones emergentes --}}
            <section class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div><h3 class="font-semibold">VIII. Reporte de acciones emergentes</h3><p class="text-xs text-gray-500">Actividades realizadas que no estaban originalmente planificadas.</p></div>
                    <button type="button" wire:click="openAccionModal('accionesEmergentes')" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar acción</button>
                </div>
                <div class="mt-3 w-full overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full min-w-[860px] text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Actividad realizada / producto logrado','Breve justificación del porqué se realizó','Responsables de la ejecución','Fecha','Horas',''] as $h)<th class="{{ $thc }}">{{ $h }}</th>@endforeach</tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($accionesEmergentes as $i=>$row)
                                <tr wire:key="aem-{{ $row['id'] ?? 'nuevo-'.$i }}">
                                    <td class="{{ $tdc }} max-w-[240px]"><p class="whitespace-pre-line">{{ $row['actividad_realizada'] ?: '—' }}</p>@if($row['producto_logrado'] ?? null)<p class="mt-1 text-xs text-gray-500">Producto: {{ Str::limit($row['producto_logrado'], 90) }}</p>@endif</td>
                                    <td class="{{ $tdc }} max-w-[240px]"><p class="whitespace-pre-line">{{ Str::limit($row['justificacion'] ?? '', 160) ?: '—' }}</p></td>
                                    <td class="{{ $tdc }} whitespace-pre-line">{{ $row['responsables'] ?: '—' }}</td>
                                    <td class="{{ $tdc }} whitespace-nowrap">{{ $row['fecha'] ? \Illuminate\Support\Carbon::parse($row['fecha'])->format('d/m/Y') : '—' }}</td>
                                    <td class="{{ $tdc }}">{{ rtrim(rtrim(number_format((float) ($row['horas'] ?? 0), 2), '0'), '.') ?: '0' }}</td>
                                    <td class="{{ $tdc }} whitespace-nowrap text-right"><button type="button" wire:click="openAccionModal('accionesEmergentes',{{ $i }})" class="text-xs text-blue-700 dark:text-blue-400">Editar</button><button type="button" x-on:click.prevent="confirmDialog('¿Quitar esta acción?', { type: 'danger' }).then((ok) => ok && $wire.quitarFila('accionesEmergentes',{{ $i }}))"  class="ml-3 text-xs text-red-600">Quitar</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Sin acciones registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- ODS --}}
            <section class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div><h3 class="font-semibold">5. Aportes a los Objetivos de Desarrollo Sostenible <span class="text-red-500">*</span></h3><p class="text-xs text-gray-500">Los ODS del registro del proyecto vienen precargados; agregue aquí los aportes de la ejecución.</p></div>
                    <button type="button" wire:click="openOdsModal" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar ODS</button>
                </div>
                <div class="mt-3 w-full overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['ODS','Meta','Aporte','Evidencia','Contribución','Origen',''] as $h)<th class="{{ $thc }}">{{ $h }}</th>@endforeach</tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($ods as $i => $odsItem)
                                <?php
                                    $esPlanificado = strtoupper((string) ($odsItem['origen'] ?? 'PLANIFICADO')) !== 'EJECUCION';
                                    $odsSeleccionado = $odsCatalogo->firstWhere('id', $odsItem['ods_id'] ?? null);
                                    $metaSeleccionada = $metasCatalogo->firstWhere('id', $odsItem['meta_contribuye_id'] ?? null);
                                ?>
                                <tr wire:key="ods-{{ $odsItem['id'] ?? 'nuevo-'.$i }}">
                                    <td class="{{ $tdc }} max-w-[200px]">{{ $odsSeleccionado?->nombre ?? 'ODS no catalogado' }}</td>
                                    <td class="{{ $tdc }} max-w-[220px]">{{ $metaSeleccionada ? $metaSeleccionada->numero_meta.' — '.Str::limit($metaSeleccionada->descripcion, 60) : (($odsItem['meta_ods'] ?? null) ?: '—') }}</td>
                                    <td class="{{ $tdc }} max-w-[200px]"><p class="whitespace-pre-line">{{ Str::limit($odsItem['descripcion_aporte'] ?? '', 120) ?: '—' }}</p></td>
                                    <td class="{{ $tdc }} max-w-[180px]"><p class="whitespace-pre-line">{{ Str::limit($odsItem['evidencia'] ?? '', 120) ?: '—' }}</p></td>
                                    <td class="{{ $tdc }}">{{ ($odsItem['nivel_contribucion'] ?? 'directa') === 'indirecta' ? 'Indirecta' : 'Directa' }}</td>
                                    <td class="{{ $tdc }}"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $esPlanificado ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' }}">{{ $esPlanificado ? 'Proyecto' : 'Ejecución' }}</span></td>
                                    <td class="{{ $tdc }} whitespace-nowrap text-right"><button type="button" wire:click="openOdsModal({{ $i }})" class="text-xs text-blue-700 dark:text-blue-400">Editar</button>@unless($esPlanificado)<button type="button" x-on:click.prevent="confirmDialog('¿Quitar este ODS?', { type: 'danger' }).then((ok) => ok && $wire.quitarFila('ods',{{ $i }}))"  class="ml-3 text-xs text-red-600">Quitar</button>@endunless</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">Sin ODS registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Modal: acción no ejecutada / emergente --}}
            @if($showAccionModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
                <div class="fixed inset-0 bg-black/50" wire:click="closeAccionModal"></div>
                <div class="relative flex min-h-full items-start justify-center p-4">
                    <div class="relative my-4 w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-gray-900">
                        <div class="sticky top-0 flex items-center justify-between rounded-t-lg border-b border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $accionModalIndex !== null ? 'Editar' : 'Nueva' }} {{ $accionModalGrupo === 'accionesEmergentes' ? 'acción emergente' : 'acción no ejecutada' }}</h4>
                            <button type="button" wire:click="closeAccionModal" class="text-lg leading-none text-gray-500 hover:text-gray-800">✕</button>
                        </div>
                        <div class="space-y-4 p-5">
                            @if($accionModalGrupo === 'accionesEmergentes')
                                <div><label class="{{ $label }}">Resultado vinculado</label><select wire:model="accionModal.informe_final_resultado_id" class="{{ $input }}"><option value="">Sin vincular</option>@foreach($this->resultadosOpciones as $op)<option value="{{ $op['id'] }}">{{ $op['label'] }}</option>@endforeach</select></div>
                                <div><label class="{{ $label }}">Actividad realizada <span class="text-red-500">*</span></label><textarea rows="3" wire:model="accionModal.actividad_realizada" class="{{ $input }}"></textarea>@error('accionModal.actividad_realizada')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="{{ $label }}">Producto logrado <span class="text-red-500">*</span></label><textarea rows="2" wire:model="accionModal.producto_logrado" class="{{ $input }}"></textarea>@error('accionModal.producto_logrado')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="{{ $label }}">Breve justificación del porqué se realizó <span class="text-red-500">*</span></label><textarea rows="3" wire:model="accionModal.justificacion" class="{{ $input }}"></textarea>@error('accionModal.justificacion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div>
                                    <label for="responsable-accion" class="{{ $label }}">Responsables de la ejecución <span class="text-red-500">*</span></label>
                                    @if($this->opcionesResponsablesAccion)
                                        <div class="flex gap-2">
                                            <select id="responsable-accion" wire:model="responsableSeleccionAccion" class="{{ $input }}">
                                                <option value="">— Seleccione una persona —</option>
                                                @foreach($this->opcionesResponsablesAccion as $grupoResponsables => $nombresResponsables)
                                                    <optgroup label="{{ $grupoResponsables }}">@foreach($nombresResponsables as $nombreResponsable)<option value="{{ $nombreResponsable }}" @disabled(in_array($nombreResponsable, $accionModal['responsables'] ?? [], true))>{{ $nombreResponsable }}</option>@endforeach</optgroup>
                                                @endforeach
                                            </select>
                                            <button type="button" wire:click="agregarResponsableAccion" class="{{ $button }} shrink-0 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">+ Añadir</button>
                                        </div>
                                    @else
                                        <p class="rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">No hay personas registradas en el informe. Registre el equipo, los participantes o las contrapartes en los pasos 2 a 4.</p>
                                    @endif
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @forelse($accionModal['responsables'] ?? [] as $iResponsable => $nombreResponsable)
                                            <span wire:key="responsable-accion-{{ $iResponsable }}" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">{{ $nombreResponsable }}<button type="button" wire:click="quitarResponsableAccion({{ $iResponsable }})" class="text-gray-500 hover:text-red-600" aria-label="Quitar a {{ $nombreResponsable }}">✕</button></span>
                                        @empty
                                            <span class="text-xs text-gray-500">Aún no hay responsables seleccionados.</span>
                                        @endforelse
                                    </div>
                                    @error('accionModal.responsables')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div><label class="{{ $label }}">Fecha</label><input type="date" wire:model="accionModal.fecha" class="{{ $input }}">@error('accionModal.fecha')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                    <div><label class="{{ $label }}">Horas</label><input type="number" min="0" step="0.01" wire:model="accionModal.horas" class="{{ $input }}">@error('accionModal.horas')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                </div>
                            @else
                                <div><label class="{{ $label }}">Resultado previsto <span class="text-red-500">*</span></label><textarea rows="2" wire:model="accionModal.resultado_previsto" class="{{ $input }}"></textarea>@error('accionModal.resultado_previsto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="{{ $label }}">Actividad planificada <span class="text-red-500">*</span></label><textarea rows="3" wire:model="accionModal.actividad_planificada" class="{{ $input }}"></textarea>@error('accionModal.actividad_planificada')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="{{ $label }}">Explicación <span class="text-red-500">*</span></label><textarea rows="3" wire:model="accionModal.explicacion" class="{{ $input }}"></textarea>@error('accionModal.explicacion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                <div><label class="{{ $label }}">Afectación al proyecto <span class="text-red-500">*</span></label><textarea rows="2" wire:model="accionModal.afectacion_proyecto" class="{{ $input }}"></textarea>@error('accionModal.afectacion_proyecto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            @endif
                        </div>
                        <div class="sticky bottom-0 flex justify-end gap-2 rounded-b-lg border-t border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <button type="button" wire:click="closeAccionModal" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>
                            <button type="button" wire:click="saveAccionModal" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">{{ $accionModalIndex !== null ? 'Guardar cambios' : 'Agregar' }}</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Modal: ODS --}}
            @if($showOdsModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
                <div class="fixed inset-0 bg-black/50" wire:click="closeOdsModal"></div>
                <div class="relative flex min-h-full items-start justify-center p-4">
                    <div class="relative my-4 w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-gray-900">
                        <div class="sticky top-0 flex items-center justify-between rounded-t-lg border-b border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $odsModalIndex !== null ? 'Editar' : 'Nuevo' }} ODS @if($odsModalEsPlanificado)<span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Del proyecto</span>@endif</h4>
                            <button type="button" wire:click="closeOdsModal" class="text-lg leading-none text-gray-500 hover:text-gray-800">✕</button>
                        </div>
                        <div class="space-y-4 p-5">
                            @if($odsModalEsPlanificado)<p class="rounded-md bg-gray-50 p-2 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">El ODS, la meta y el nivel de contribución provienen del registro del proyecto. Solo se editan el aporte y la evidencia de la ejecución.</p>@endif
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div><label class="{{ $label }}">ODS <span class="text-red-500">*</span></label>@if($odsModalEsPlanificado)<input value="{{ optional($odsCatalogo->firstWhere('id', $odsModal['ods_id']))->nombre ?? 'ODS no catalogado' }}" readonly class="{{ $readonly }}">@else<select wire:model.live="odsModal.ods_id" class="{{ $input }}"><option value="">Seleccione</option>@foreach($odsCatalogo as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach</select>@error('odsModal.ods_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif</div>
                                <div><label class="{{ $label }}">Meta</label>@if($odsModalEsPlanificado)<input value="{{ optional($metasCatalogo->firstWhere('id', $odsModal['meta_contribuye_id']))->numero_meta ?? '—' }}" readonly class="{{ $readonly }}">@else<select wire:model="odsModal.meta_contribuye_id" class="{{ $input }}"><option value="">Sin meta catalogada</option>@foreach($metasCatalogo->where('ods_id', $odsModal['ods_id'] ?: null) as $meta)<option value="{{ $meta->id }}">{{ $meta->numero_meta }} — {{ Str::limit($meta->descripcion,55) }}</option>@endforeach</select>@error('odsModal.meta_contribuye_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif</div>
                            </div>
                            <div><label class="{{ $label }}">Descripción del aporte <span class="text-xs text-gray-500">(opcional)</span></label><textarea rows="3" wire:model="odsModal.descripcion_aporte" class="{{ $input }}"></textarea>@error('odsModal.descripcion_aporte')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Evidencia</label><textarea rows="2" wire:model="odsModal.evidencia" class="{{ $input }}"></textarea>@error('odsModal.evidencia')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div class="sm:w-1/2"><label class="{{ $label }}">Nivel de contribución <span class="text-red-500">*</span></label>@if($odsModalEsPlanificado)<input value="{{ ($odsModal['nivel_contribucion'] ?? 'directa') === 'indirecta' ? 'Indirecta' : 'Directa' }}" readonly class="{{ $readonly }}">@else<select wire:model="odsModal.nivel_contribucion" class="{{ $input }}"><option value="directa">Directa</option><option value="indirecta">Indirecta</option></select>@endif</div>
                        </div>
                        <div class="sticky bottom-0 flex justify-end gap-2 rounded-b-lg border-t border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <button type="button" wire:click="closeOdsModal" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>
                            <button type="button" wire:click="saveOdsModal" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">{{ $odsModalIndex !== null ? 'Guardar cambios' : 'Agregar' }}</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @elseif($currentStep === 7)
            <?php
                $catalogoX = \App\Support\InformeFinal\ConceptosPresupuestoInf001::class;
                $totales = $this->totalesPresupuesto;
                $filasX = function (string $fuente) use ($presupuesto, $catalogoX) {
                    $orden = array_keys($catalogoX::catalogo($fuente));
                    return collect($presupuesto)
                        ->map(fn ($row, $i) => $row + ['indice' => $i, 'codigo' => $catalogoX::codigoDeFila($fuente, $row['concepto_codigo'] ?? null, $row['concepto'] ?? null)])
                        ->filter(fn ($row) => ($row['fuente'] ?? null) === $fuente && ! $catalogoX::esIndirecto($row['codigo']))
                        ->sortBy(fn ($row) => ($pos = array_search($row['codigo'], $orden, true)) === false ? 999 + $row['indice'] : $pos)
                        ->values();
                };
                $moneda = fn ($valor) => 'L '.number_format((float) $valor, 2);
                $totalFila = fn ($row) => (float) ($row['cantidad'] ?? 0) * (float) ($row['costo_unitario'] ?? 0);
            ?>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paso 7: Evaluación comunitaria y ejecución presupuestaria</h2>

            <h3 class="font-semibold">11. Resultados de la valoración del proyecto por parte de la comunidad beneficiada <span class="text-red-500">*</span></h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Deberá de aplicarse un formulario de consulta a los beneficiados para que evalúen el proyecto. Cada respuesta es una persona encuestada: registre cuántas eligieron cada opción y el porcentaje se calcula.</p>
            <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div><label class="{{ $label }}">Total beneficiarios</label><input type="number" value="{{ $general['valoracion_total_beneficiarios'] ?? 0 }}" readonly class="{{ $readonly }}"><p class="mt-1 text-xs text-gray-500">Del ítem 9 (paso 1).</p></div>
                <div><label class="{{ $label }}">Total muestra de consultas realizadas <span class="text-red-500">*</span></label><input type="number" min="0" max="{{ $general['valoracion_total_beneficiarios'] ?? 0 }}" wire:model.live="general.valoracion_muestra" class="{{ $input }}"><p class="mt-1 text-xs text-gray-500">Máximo {{ $general['valoracion_total_beneficiarios'] ?? 0 }} (ítem 9).</p>@error('general.valoracion_muestra')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @foreach(['excelente'=>'Excelente','muy_buena'=>'Muy buena','regular'=>'Regular','mala'=>'Mala'] as $field=>$name)<div><label class="{{ $label }}">{{ $name }}</label><input type="number" min="0" max="{{ $this->resumenValoracion['sobran'] > 0 ? $this->resumenValoracion['muestra'] : (int) ($general['valoracion_'.$field] ?? 0) + $this->resumenValoracion['faltan'] }}" wire:model.live="general.valoracion_{{ $field }}" class="{{ $input }}"><p class="mt-1 text-xs text-gray-500">{{ $this->porcentajesValoracion[$field] }}%</p></div>@endforeach
            </div>
            <p class="mt-2 text-xs {{ $this->resumenValoracion['cuadra'] ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">
                @if($this->resumenValoracion['muestra'] < 1)
                    Registre primero el total de la muestra de consultas realizadas.
                @elseif($this->resumenValoracion['cuadra'])
                    Las {{ $this->resumenValoracion['muestra'] }} respuestas de la muestra están clasificadas.
                @elseif($this->resumenValoracion['sobran'] > 0)
                    Hay {{ $this->resumenValoracion['respuestas'] }} respuestas clasificadas y la muestra es de {{ $this->resumenValoracion['muestra'] }}: quite {{ $this->resumenValoracion['sobran'] }}.
                @else
                    Clasificadas {{ $this->resumenValoracion['respuestas'] }} de {{ $this->resumenValoracion['muestra'] }} respuestas: faltan {{ $this->resumenValoracion['faltan'] }} por repartir entre las cuatro opciones.
                @endif
            </p>

            <h3 class="mt-6 font-semibold">X. Ejecución presupuestaria</h3>
            @error('presupuesto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            <h4 class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Aporte de la UNAH (manifestado en lempiras) <span class="text-red-500">*</span></h4>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Concepto','Unidad','Cantidad','Costo unitario','Costo total'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($filasX('UNAH') as $row)
                        <tr class="border-t dark:border-gray-700" wire:key="x-unah-{{ $row['indice'] }}">
                            <td class="px-3 py-2">{{ $row['codigo'] ? $catalogoX::etiqueta('UNAH', $row['codigo']) : $row['concepto'] }}</td>
                            <td class="px-3 py-2">{{ $row['codigo'] ? $catalogoX::unidad('UNAH', $row['codigo']) : ($row['unidad'] ?: 'Global') }}</td>
                            <td class="px-3 py-2">
                                @if(array_key_exists($row['codigo'], $this->horasRegistradas))
                                    <input type="number" value="{{ $this->horasRegistradas[$row['codigo']] }}" readonly class="{{ $readonly }} w-28"><p class="mt-1 text-xs text-gray-500">{{ $row['codigo'] === 'horas_trabajo_docentes' ? 'Horas del equipo docente (paso 2).' : 'Horas de los estudiantes (paso 3).' }}</p>
                                @else
                                    <input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="presupuesto.{{ $row['indice'] }}.cantidad" class="{{ $input }} w-28">
                                @endif
                            </td>
                            <td class="px-3 py-2"><input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="presupuesto.{{ $row['indice'] }}.costo_unitario" class="{{ $input }} w-32">@if(array_key_exists($row['codigo'], $this->horasRegistradas))<p class="mt-1 text-xs text-gray-500">Costo por hora.</p>@endif</td>
                            <td class="px-3 py-2 font-medium">{{ $moneda($totalFila($row)) }}</td>
                        </tr>
                    @endforeach
                    @foreach(['costos_indirectos_infraestructura' => 'infraestructura', 'costos_indirectos_servicios' => 'servicios'] as $codigoIndirecto => $claveTotal)
                        <tr class="border-t bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                            <td class="px-3 py-2">{{ $catalogoX::etiqueta('UNAH', $codigoIndirecto) }}</td>
                            <td class="px-3 py-2">3%</td>
                            <td class="px-3 py-2 text-xs text-gray-500" colspan="2">Calculado: 3% de a) + b)</td>
                            <td class="px-3 py-2 font-medium">{{ $moneda($totales[$claveTotal]) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t font-semibold dark:border-gray-700"><td class="px-3 py-2 text-right" colspan="4">Total aporte institucional</td><td class="px-3 py-2">{{ $moneda($totales['unah']) }}</td></tr>
                </tbody>
            </table></div>

            <h4 class="mt-6 text-sm font-semibold text-gray-700 dark:text-gray-300">Aporte de la contraparte (manifestado en lempiras)</h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Si un concepto tiene monto, describa el origen de los fondos.
                @if($this->aporteContrapartePlanificado > 0) Referencia: en el registro se planificó un aporte de contraparte de <strong>{{ $moneda($this->aporteContrapartePlanificado) }}</strong>; lo ejecutado no tiene que coincidir. @endif
            </p>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Concepto','Unidad','Cantidad','Costo unitario','Costo total','Descripción del origen de los fondos'] as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($filasX('CONTRAPARTE') as $row)
                        <tr class="border-t dark:border-gray-700" wire:key="x-contraparte-{{ $row['indice'] }}">
                            <td class="px-3 py-2">{{ $row['codigo'] ? $catalogoX::etiqueta('CONTRAPARTE', $row['codigo']) : $row['concepto'] }}</td>
                            <td class="px-3 py-2">{{ $row['codigo'] ? $catalogoX::unidad('CONTRAPARTE', $row['codigo']) : ($row['unidad'] ?: 'Global') }}</td>
                            <td class="px-3 py-2"><input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="presupuesto.{{ $row['indice'] }}.cantidad" class="{{ $input }} w-28"></td>
                            <td class="px-3 py-2"><input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="presupuesto.{{ $row['indice'] }}.costo_unitario" class="{{ $input }} w-32"></td>
                            <td class="px-3 py-2 font-medium">{{ $moneda($totalFila($row)) }}</td>
                            <td class="px-3 py-2"><input wire:model.blur="presupuesto.{{ $row['indice'] }}.origen_fondos" class="{{ $input }} min-w-48">@error('presupuesto.'.$row['indice'].'.origen_fondos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</td>
                        </tr>
                    @endforeach
                    <tr class="border-t font-semibold dark:border-gray-700"><td class="px-3 py-2 text-right" colspan="4">Total aporte de las contrapartes</td><td class="px-3 py-2" colspan="2">{{ $moneda($totales['contraparte']) }}</td></tr>
                    <tr class="border-t dark:border-gray-700"><td class="px-3 py-2 text-right" colspan="4">Aporte de los beneficiarios (comunidad)</td><td class="px-3 py-2" colspan="2"><input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="general.aporte_beneficiarios" class="{{ $input }} w-40"></td></tr>
                    <tr class="border-t dark:border-gray-700"><td class="px-3 py-2 text-right" colspan="4">Otros aportes</td><td class="px-3 py-2" colspan="2"><input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="general.otros_aportes" class="{{ $input }} w-40"></td></tr>
                    <tr class="border-t font-semibold dark:border-gray-700"><td class="px-3 py-2 text-right" colspan="4">Total Ejecución de la contraparte</td><td class="px-3 py-2" colspan="2">{{ $moneda($totales['ejecucion_contraparte']) }}</td></tr>
                </tbody>
            </table></div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><strong>Nota:</strong> La entidad contraparte deberá de presentar el reporte de gastos, desglosado, firmado y sellado por el responsable contable (gerente, tesorero, Alcalde Municipal, etc) y del representante legal de la institución, certificando la veracidad de los fondos. En el caso de no poder contar con esta carta aval, no deberá de registrarse valor alguno en el cuadro de la contraparte.</p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="{{ $label }}">Presupuesto planificado (registro) <span class="text-red-500">*</span></label>
                    @if($this->presupuestoPlanificadoRegistro > 0)
                        <input type="number" value="{{ $this->presupuestoPlanificadoRegistro }}" readonly class="{{ $readonly }}">
                        <p class="mt-1 text-xs text-gray-500">Del registro del proyecto: aporte institucional más los aportes de contraparte, comunidad, cooperación internacional, otras universidades y otros.</p>
                    @else
                        <input type="number" min="0" step="0.01" wire:model.live="general.presupuesto_planificado" class="{{ $input }}">
                        <p class="mt-1 text-xs text-gray-500">El registro del proyecto no tiene presupuesto, así que se escribe aquí.</p>
                    @endif
                    @error('general.presupuesto_planificado')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Ejecución total (UNAH + contraparte)</p><p class="mt-1 font-semibold">{{ $moneda($totales['ejecucion']) }}</p></div>
                <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Porcentaje de ejecución</p><p class="mt-1 font-semibold">{{ number_format($totales['porcentaje'], 2) }}%</p></div>
            </div>
        @else
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paso 8: Anexos, cierre y validación</h2>
            <section class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="flex items-center justify-between"><div><h3 class="font-semibold">XII. Anexos</h3><p class="text-xs text-gray-500">Obligatorios: 1) Material generado por el proyecto (enlace a carpeta digital), 2) Formularios de encuestas, 3) Informes de procesamiento de datos, 4) Fotografías de todo el proceso (fotografías o enlace a carpeta digital), 6) Evidencias de difusión{{ collect($estudiantes)->contains(fn ($e) => ($e['estado_participacion'] ?? 'activo') === 'activo') ? ' y la bitácora de cada estudiante' : '' }}. 5) Videos cortos solo si se realizaron.</p>@foreach($errors->getMessages() as $claveError => $mensajesError)@if(str_starts_with($claveError, 'anexos.'))@foreach($mensajesError as $mensajeError)<p class="mt-1 text-xs text-red-600">{{ $mensajeError }}</p>@endforeach @endif @endforeach</div><button type="button" wire:click="openAnexoModal" class="{{ $button }} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">Agregar documento</button></div>
                <?php
                    $thAnexo = 'px-3 py-2 text-left font-semibold text-xs uppercase tracking-wide text-gray-500';
                    $tdAnexo = 'px-3 py-2 align-top';
                ?>
                @if($this->instrumentosContraparteAnexos)
                    <h4 class="mt-4 text-sm font-semibold">Instrumentos de formalización y respaldos de contraparte</h4>
                    <p class="text-xs text-gray-500">Vienen del registro del proyecto y se anexan tal cual.</p>
                    <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Contraparte','Instrumento o respaldo','Archivo o enlace',''] as $h)<th class="{{ $thAnexo }}">{{ $h }}</th>@endforeach</tr></thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($this->instrumentosContraparteAnexos as $row)
                                    <tr wire:key="anexo-instrumento-{{ $row['id'] ?? 'nuevo-'.$row['indice_formulario'] }}">
                                        <td class="{{ $tdAnexo }}">{{ collect($contrapartes)->firstWhere('id', $row['informe_final_contraparte_id'] ?? null)['nombre'] ?? '—' }}</td>
                                        <td class="{{ $tdAnexo }}">{{ $row['descripcion'] ?: 'Instrumento de contraparte' }}</td>
                                        <td class="{{ $tdAnexo }}">@if($this->anexoDocumentoUrl($row['id'] ?? null) && filled($row['archivo'] ?? null))<a href="{{ $this->anexoDocumentoUrl($row['id']) }}" target="_blank" rel="noopener" class="text-blue-700">{{ $row['nombre_archivo'] ?: 'Ver documento' }}</a>@elseif(filled($row['enlace'] ?? null))<a href="{{ $row['enlace'] }}" target="_blank" rel="noopener" class="break-all text-blue-700">{{ $row['enlace'] }}</a>@else<span class="text-amber-700">Pendiente</span>@endif</td>
                                        <td class="{{ $tdAnexo }} whitespace-nowrap text-right">@unless(in_array($row['origen'] ?? 'INFORME', ['PLANIFICADO','PROYECTO'], true))<button type="button" wire:click="openAnexoModal({{ $row['indice_formulario'] }})" class="text-xs text-blue-700 dark:text-blue-400">Editar</button><button type="button" x-on:click.prevent="confirmDialog('¿Quitar este respaldo del informe?', { type: 'danger' }).then((ok) => ok && $wire.quitarFila('anexos',{{ $row['indice_formulario'] }}))"  class="ml-3 text-xs text-red-600">Quitar</button>@else<span class="text-xs text-gray-500">Del registro</span>@endunless</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h4 class="mt-5 text-sm font-semibold">Documentos del informe</h4>
                <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Tipo','Descripción','Archivo o enlace','Fecha',''] as $h)<th class="{{ $thAnexo }}">{{ $h }}</th>@endforeach</tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($this->documentosGenerales as $row)
                                <tr wire:key="anexo-documento-{{ $row['id'] ?? 'nuevo-'.$row['indice_formulario'] }}">
                                    <td class="{{ $tdAnexo }}">{{ $this->tiposAnexo[$row['tipo']] ?? $row['tipo'] }}</td>
                                    <td class="{{ $tdAnexo }} max-w-[260px]"><p class="whitespace-pre-line">{{ $row['descripcion'] ?: '—' }}</p></td>
                                    <td class="{{ $tdAnexo }}">
                                        @if($this->anexoDocumentoUrl($row['id'] ?? null) && filled($row['archivo'] ?? null))
                                            <a href="{{ $this->anexoDocumentoUrl($row['id']) }}" target="_blank" rel="noopener" class="text-blue-700">{{ $row['nombre_archivo'] ?: 'Ver documento' }}</a>
                                        @elseif(filled($row['enlace'] ?? null))
                                            <a href="{{ $row['enlace'] }}" target="_blank" rel="noopener" class="break-all text-blue-700">{{ $row['enlace'] }}</a>
                                        @else
                                            <span class="text-amber-700">Sin archivo ni enlace</span>
                                        @endif
                                    </td>
                                    <td class="{{ $tdAnexo }} whitespace-nowrap">{{ $row['fecha'] ? \Illuminate\Support\Carbon::parse($row['fecha'])->format('d/m/Y') : '—' }}</td>
                                    <td class="{{ $tdAnexo }} whitespace-nowrap text-right"><button type="button" wire:click="openAnexoModal({{ $row['indice_formulario'] }})" class="text-xs text-blue-700 dark:text-blue-400">Editar</button><button type="button" x-on:click.prevent="confirmDialog('¿Quitar este documento del informe?', { type: 'danger' }).then((ok) => ok && $wire.quitarFila('anexos',{{ $row['indice_formulario'] }}))"  class="ml-3 text-xs text-red-600">Quitar</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">Sin documentos adjuntos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="mb-4 font-semibold">Fotografías del proyecto</h3>
                <x-forms.image-dropzone model="fotografiasTemporales" id="inf001-fotografias" />
                @if($this->fotografias)
                    <h4 class="mt-6 text-sm font-semibold">Fotografías guardadas</h4>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach($this->fotografias as $foto)<article class="overflow-hidden rounded-lg border dark:border-gray-700"><img src="{{ $foto['id'] ? route('informes-finales.anexos.mostrar', ['anexo' => $foto['id']], false) : '' }}" alt="{{ $foto['descripcion'] ?: 'Fotografía '.$foto['nombre_archivo'] }}" class="h-36 w-full object-cover"><div class="space-y-2 p-3 text-xs"><div class="flex items-center justify-between gap-2"><p class="truncate font-medium" title="{{ $foto['nombre_archivo'] }}">{{ $foto['nombre_archivo'] ?: 'Fotografía' }}</p><span class="rounded-full bg-green-100 px-2 py-1 text-[10px] font-medium text-green-800">Guardada</span></div><p class="text-gray-500">{{ $foto['tamano_bytes'] ? number_format($foto['tamano_bytes']/1024,1).' KB' : 'Tamaño no disponible' }} · {{ $foto['fecha'] ?: 'Sin fecha' }}</p><input wire:model.live.debounce.1000ms="anexos.{{ $foto['indice_formulario'] }}.descripcion" aria-label="Descripción de {{ $foto['nombre_archivo'] ?: 'la fotografía' }}" placeholder="Descripción opcional" class="{{ $input }}"><div class="flex gap-3"><a href="{{ $foto['id'] ? route('informes-finales.anexos.mostrar', ['anexo' => $foto['id']], false) : '#' }}" data-route="informes-finales.anexos.mostrar" target="_blank" rel="noopener noreferrer" class="text-blue-700">Ver</a><a href="{{ $foto['id'] ? route('informes-finales.anexos.descargar', ['anexo' => $foto['id']], false) : '#' }}" data-route="informes-finales.anexos.descargar" class="text-blue-700">Descargar</a><button type="button" x-on:click.prevent="confirmDialog('¿Quitar esta fotografía del Informe Final?', { type: 'danger' }).then((ok) => ok && $wire.quitarFotografia({{ $foto['id'] }}))"  class="text-red-600">Quitar</button></div></div></article>@endforeach</div>
                @endif
            </section>
            <h3 class="mt-6 font-semibold">Cierre</h3><div class="mt-3 grid gap-4 sm:grid-cols-2"><div><label class="{{ $label }}">Fecha de cierre</label><input type="date" wire:model="general.fecha_cierre" class="{{ $input }}"></div><div class="sm:col-span-2"><label class="{{ $label }}">Observaciones finales</label><textarea rows="4" wire:model.live.debounce.1000ms="general.observaciones_finales" class="{{ $input }}"></textarea></div><label class="flex items-start gap-2 sm:col-span-2"><input type="checkbox" wire:model="general.confirmacion_veracidad" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"><span class="text-sm">Confirmo que la información consignada en el INF-001 es veraz.</span></label></div>
            <div class="mt-6 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-100"><strong>Firmas:</strong> la plantilla reserva los espacios para coordinador, jefatura de unidad académica, coordinación de vinculación y decanato/dirección. Al marcar el informe como completo se envía al flujo de cierre: se genera el PDF, se crean las firmas de estas cuatro etapas, se notifica a quien revisa y la edición queda bloqueada.</div>
            <section class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="font-semibold">Resumen final del informe</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach($this->resumenRevision as $name=>$value)<div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">{{ $name }}</p><p class="mt-1 font-semibold">{{ $value }}</p></div>@endforeach</div>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100"><strong>Campos pendientes</strong>@if($this->camposPendientes)<ul class="mt-2 list-disc pl-5">@foreach($this->camposPendientes as $pending)<li>{{ $pending }}</li>@endforeach</ul>@else<p class="mt-2">No hay campos esenciales pendientes.</p>@endif</div>
                    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/30 dark:text-red-100"><strong>Inconsistencias detectadas</strong>@if($this->inconsistenciasRevision)<ul class="mt-2 list-disc pl-5">@foreach($this->inconsistenciasRevision as $issue)<li>{{ $issue }}</li>@endforeach</ul>@else<p class="mt-2">No se detectaron inconsistencias.</p>@endif</div>
                </div>
            </section>
            @if($showEnvioCierreModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
                <div class="fixed inset-0 bg-black/50" wire:click="cerrarEnvioCierreModal"></div>
                <div class="relative flex min-h-full items-start justify-center p-4">
                    <div class="relative my-4 w-full max-w-xl rounded-lg bg-white shadow-xl dark:bg-gray-900">
                        <div class="rounded-t-lg border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Destinatarios del flujo de cierre</h4>
                            <p class="mt-1 text-xs text-gray-500">Elija a quién le llega el informe en cada etapa de revisión. Al enviarlo se genera el PDF, se crean las firmas y la edición queda bloqueada.</p>
                        </div>
                        <div class="space-y-4 p-5">
                            @foreach($this->opcionesDestinatariosCierre as $etapaIdCierre => $opcionCierre)
                                <div wire:key="destinatario-cierre-{{ $etapaIdCierre }}">
                                    <label class="{{ $label }}">{{ $opcionCierre['etapa']->nombre }} <span class="text-red-500">*</span></label>
                                    <x-forms.searchable-user-select
                                        :model="'destinatariosCierre.'.$etapaIdCierre"
                                        :options="$this->candidatosDestinatariosCierre[$etapaIdCierre] ?? []"
                                        :selected="$destinatariosCierre[$etapaIdCierre] ?? null"
                                        placeholder="Escriba el nombre del destinatario..."
                                        :wire-key="'destinatario-cierre-select-'.$etapaIdCierre"
                                    />
                                </div>
                            @endforeach
                            @error('destinatariosCierre')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex justify-end gap-2 rounded-b-lg border-t border-gray-200 px-5 py-3 dark:border-gray-700">
                            <button type="button" wire:click="cerrarEnvioCierreModal" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>
                            <button type="button" wire:click="validarInforme" wire:loading.attr="disabled" class="rounded-md bg-green-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-800 disabled:opacity-60">Completar y enviar</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($showAnexoModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
                <div class="fixed inset-0 bg-black/50" wire:click="closeAnexoModal"></div>
                <div class="relative flex min-h-full items-start justify-center p-4">
                    <div class="relative my-4 w-full max-w-xl rounded-lg bg-white shadow-xl dark:bg-gray-900">
                        <div class="sticky top-0 flex items-center justify-between rounded-t-lg border-b border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $anexoModalIndex !== null ? 'Editar documento' : 'Agregar documento' }}</h4>
                            <button type="button" wire:click="closeAnexoModal" class="text-lg leading-none text-gray-500 hover:text-gray-800">✕</button>
                        </div>
                        <div class="space-y-4 p-5">
                            <div><label class="{{ $label }}">Categoría <span class="text-red-500">*</span></label><select wire:model.live="anexoModal.categoria" class="{{ $input }}"><option value="documento_general">Documento del informe</option><option value="instrumento_contraparte">Instrumento o respaldo de contraparte</option></select>@error('anexoModal.categoria')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            @if(($anexoModal['categoria'] ?? 'documento_general') === 'instrumento_contraparte')
                                <div><label class="{{ $label }}">Contraparte <span class="text-red-500">*</span></label><select wire:model="anexoModal.informe_final_contraparte_id" class="{{ $input }}"><option value="">— Seleccione —</option>@foreach($contrapartes as $contraparteAnexo)<option value="{{ $contraparteAnexo['id'] ?? '' }}">{{ $contraparteAnexo['nombre'] }}</option>@endforeach</select>@error('anexoModal.informe_final_contraparte_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            @endif
                            <div><label class="{{ $label }}">Tipo de anexo <span class="text-red-500">*</span></label><select wire:model="anexoModal.tipo" class="{{ $input }}">@foreach($this->tiposAnexo as $valorAnexo => $nombreAnexo)<option value="{{ $valorAnexo }}">{{ $nombreAnexo }}</option>@endforeach</select>@error('anexoModal.tipo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Descripción <span class="text-red-500">*</span></label><input wire:model="anexoModal.descripcion" class="{{ $input }}">@error('anexoModal.descripcion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div>
                                <label class="{{ $label }}">Archivo</label>
                                <input type="file" wire:model="anexoModalArchivo" class="{{ $input }}">
                                <div wire:loading wire:target="anexoModalArchivo" class="mt-1 text-xs text-gray-500">Subiendo el archivo…</div>
                                @if(filled($anexoModal['archivo'] ?? null))<p class="mt-1 text-xs text-gray-500">Adjunto actual: {{ $anexoModal['nombre_archivo'] ?: 'archivo guardado' }}. Suba otro para reemplazarlo.</p>@endif
                                @error('anexoModalArchivo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div><label class="{{ $label }}">Enlace a carpeta digital</label><input type="url" wire:model="anexoModal.enlace" placeholder="https://" class="{{ $input }}"><p class="mt-1 text-xs text-gray-500">Adjunte el archivo o escriba el enlace; al menos uno de los dos.</p>@error('anexoModal.enlace')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Fecha</label><input type="date" wire:model="anexoModal.fecha" class="{{ $input }}">@error('anexoModal.fecha')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                        <div class="sticky bottom-0 flex justify-end gap-2 rounded-b-lg border-t border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <button type="button" wire:click="closeAnexoModal" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>
                            <button type="button" wire:click="guardarAnexoModal" wire:loading.attr="disabled" wire:target="anexoModalArchivo,guardarAnexoModal" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">{{ $anexoModalIndex !== null ? 'Guardar cambios' : 'Agregar' }}</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endif

        <div class="mt-8 flex flex-col gap-3 pt-4 border-t border-gray-200 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <div>@if($currentStep>1)<button type="button" wire:click="anterior" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">← Anterior</button>@else<a href="{{ route('historialproyecto',$proyecto) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Volver</a>@endif</div>
            <div class="flex flex-wrap items-center justify-end gap-3">
                <span class="text-xs min-w-[78px] text-right" aria-live="polite">@if($estadoGuardado==='guardando')<span class="text-gray-500 dark:text-gray-400">Guardando...</span>@elseif($estadoGuardado==='error')<span class="text-red-600 dark:text-red-400">Error al guardar</span>@else<span class="text-green-600 dark:text-green-400">Guardado</span>@endif</span>
                <button type="button" wire:click="guardarBorrador" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 disabled:opacity-60">Guardar borrador</button>
                @if($currentStep<8)<button type="button" wire:click="siguiente" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60">Siguiente →</button>@else<a href="{{ route('informes-finales.inf-001.preview',$informe) }}" class="{{ $button }} border border-blue-300 text-blue-700 dark:border-blue-700 dark:text-blue-300">Vista previa</a><a href="{{ route('informes-finales.inf-001.pdf',$informe) }}" class="{{ $button }} border border-blue-300 text-blue-700 dark:border-blue-700 dark:text-blue-300">Descargar PDF preliminar</a>@if($this->opcionesDestinatariosCierre->isNotEmpty())<button type="button" wire:click="validarInforme" wire:loading.attr="disabled" class="{{ $button }} bg-green-700 text-white hover:bg-green-800 disabled:opacity-60">Marcar completo y enviar</button>@else<button type="button" x-on:click.prevent="confirmDialog('Se generará el PDF, se crearán las firmas y el informe pasará a revisión. La edición quedará bloqueada hasta que se resuelva.', { title: '¿Marcar el INF-001 como completo y enviarlo al flujo de cierre?', confirmText: 'Completar y enviar' }).then((ok) => ok && $wire.validarInforme())" wire:loading.attr="disabled" class="{{ $button }} bg-green-700 text-white hover:bg-green-800 disabled:opacity-60">Marcar completo y enviar</button>@endif<a href="{{ route('historialproyecto',$proyecto) }}" class="{{ $button }} border border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300">Volver al proyecto</a>@endif
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
                            <div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Horas reales dedicadas <span class="text-red-500">*</span></label><input type="number" min="0.5" step="0.5" wire:model="estudianteModal.horas_dedicadas" class="{{ $input }}">@error('estudianteModal.horas_dedicadas')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div>
                        @endif
                        @if($mostrarRegistroManual)
                            <div class="flex items-center gap-3"><div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div><span class="text-xs font-medium text-gray-500 dark:text-gray-400">O registrar estudiante manualmente</span><div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div></div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2"><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nombres <span class="text-red-500">*</span></label><input wire:model="estudianteManual.nombres" class="{{ $input }}">@error('estudianteManual.nombres')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Apellidos @if($editEstudianteIndex === null)<span class="text-red-500">*</span>@endif</label><input wire:model="estudianteManual.apellidos" class="{{ $input }}">@error('estudianteManual.apellidos')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Número de cuenta <span class="text-red-500">*</span></label><input wire:model="estudianteManual.numero_cuenta" class="{{ $input }}">@error('estudianteManual.numero_cuenta')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Sexo <span class="text-red-500">*</span></label><select wire:model="estudianteManual.sexo" class="{{ $input }}"><option value="">Seleccione el sexo</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select>@error('estudianteManual.sexo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Carrera <span class="text-red-500">*</span></label><select wire:model="estudianteManual.carrera" class="{{ $input }}"><option value="">Seleccione la carrera</option>@foreach($this->carrerasCatalogo as $nombreCarrera)<option value="{{ $nombreCarrera }}">{{ $nombreCarrera }}</option>@endforeach</select>@error('estudianteManual.carrera')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Correo electrónico <span class="text-red-500">*</span></label><input type="email" wire:model="estudianteManual.correo" class="{{ $input }}">@error('estudianteManual.correo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Horas reales dedicadas <span class="text-red-500">*</span></label><input type="number" min="0.5" step="0.5" wire:model="estudianteManual.horas_dedicadas" class="{{ $input }}">@error('estudianteManual.horas_dedicadas')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div></div>
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
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2"><div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nombre completo <span class="text-red-500">*</span></label><input wire:model="voluntarioModal.nombre" @if($voluntarioEncontrado) readonly @endif class="{{ $voluntarioEncontrado ? $readonly : $input }}">@error('voluntarioModal.nombre')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Sexo <span class="text-red-500">*</span></label><select wire:model="voluntarioModal.sexo" @if($voluntarioEncontrado) disabled @endif class="{{ $voluntarioEncontrado ? $readonly : $input }}"><option value="">Seleccione el sexo</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select>@error('voluntarioModal.sexo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Identidad / número</label><input wire:model="voluntarioModal.identidad" @if($voluntarioEncontrado) readonly @endif class="{{ $voluntarioEncontrado ? $readonly : $input }}"></div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Departamento al que pertenece</label><select wire:model="voluntarioModal.departamento" @if($voluntarioEncontrado) disabled @endif class="{{ $voluntarioEncontrado ? $readonly : $input }}"><option value="">Seleccione el departamento</option>@foreach($this->departamentosAcademicosCatalogo as $nombreDepto)<option value="{{ $nombreDepto }}">{{ $nombreDepto }}</option>@endforeach</select>@error('voluntarioModal.departamento')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipo</label><select wire:model="voluntarioModal.tipo" class="{{ $input }}"><option value="profesor_hora">Profesor por hora</option><option value="pas">PAS</option><option value="profesor_permanente">Profesor permanente</option><option value="egresado">Egresado</option></select></div><div><label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Horas dedicadas <span class="text-red-500">*</span></label><input type="number" min="0" step="0.5" wire:model="voluntarioModal.horas_dedicadas" class="{{ $input }}"></div></div>
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
