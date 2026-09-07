@php
    $badgeEstado = function (?string $estado) {
        $estado = $estado ?: 'no_registrado';
        $mapa = [
            'alcanzado' => ['Alcanzado', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
            'parcialmente_alcanzado' => ['Parcialmente alcanzado', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
            'no_alcanzado' => ['No alcanzado', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
            'no_aplica' => ['No aplica', 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'],
        ];
        [$texto, $clase] = $mapa[$estado] ?? ['Sin registrar', 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'];

        return new \Illuminate\Support\HtmlString('<span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium '.$clase.'">'.$texto.'</span>');
    };
@endphp

<div class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>Objetivo general:</strong> {{ $general['objetivo_general'] ?: 'No registrado' }}</div>

@php
    $gruposResultados = [
        ['clave'=>'corto_plazo', 'rows'=>$this->resultadosCortoPlazo, 'titulo'=>'Resultados por objetivo específico', 'ayuda'=>'Productos de corto plazo comprometidos para cada objetivo específico del proyecto.', 'col1'=>'Objetivo específico'],
        ['clave'=>'mediano_plazo', 'rows'=>$this->resultadosMedianoLargoPlazo, 'titulo'=>'Resultados de mediano y largo plazo', 'ayuda'=>'Efectos e impacto del proyecto. No están ligados a un objetivo específico.', 'col1'=>'Plazo'],
    ];
    $plazoTexto = ['corto_plazo'=>'Corto plazo','mediano_plazo'=>'Mediano plazo','largo_plazo'=>'Largo plazo'];
@endphp

@foreach($gruposResultados as $gi => $grupo)
<section class="{{ $gi === 0 ? 'mt-6' : 'mt-8 border-t border-gray-200 pt-8 dark:border-gray-700' }}">
    <div><h3 class="font-semibold">{{ $grupo['titulo'] }}</h3><p class="text-sm text-gray-500">{{ $grupo['ayuda'] }}</p></div>

    <div class="mt-3 w-full overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[1100px] text-sm">
            <thead class="bg-gray-50 text-left align-bottom dark:bg-gray-800">
                <tr>@foreach([$grupo['col1'],'Resultado planificado','Indicador propuesto','Unidad','Meta','Valor alcanzado','Cumpl. %','Estado','Producto logrado','Observaciones'] as $heading)<th class="px-3 py-2 font-semibold">{{ $heading }}</th>@endforeach</tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($grupo['rows'] as $row)
                    @php($i = $row['indice_formulario'])
                    @php($esPlan = filled($row['resultado_esperado_id'] ?? null))
                    <tr wire:key="resultado-{{ $row['id'] ?? 'nuevo-'.$i }}" class="align-top">
                        <td class="max-w-[220px] px-3 py-2">{{ $grupo['clave'] === 'corto_plazo' ? ($row['objetivo_especifico'] ?: '—') : $plazoTexto[$row['plazo'] ?? 'mediano_plazo'] }}</td>
                        <td class="max-w-[220px] px-3 py-2"><p class="whitespace-pre-line">{{ $row['resultado_planificado'] ?: '—' }}</p></td>
                        <td class="max-w-[200px] px-3 py-2"><p class="whitespace-pre-line">{{ $row['indicador_propuesto'] ?: '—' }}</p></td>
                        <td class="px-3 py-2"><input wire:model.blur="resultados.{{ $i }}.unidad_medida" class="{{ $input }} w-24"></td>
                        <td class="px-3 py-2"><input type="number" min="0" step="0.01" wire:model.blur.number="resultados.{{ $i }}.meta_numerica" class="{{ $input }} w-24"></td>
                        <td class="px-3 py-2"><input type="number" min="0" step="0.01" wire:model.blur.number="resultados.{{ $i }}.valor_alcanzado" class="{{ $input }} w-24"></td>
                        <td class="px-3 py-2"><span class="text-gray-600 dark:text-gray-300" title="Calculado automáticamente">{{ rtrim(rtrim(number_format((float) ($row['porcentaje_cumplimiento'] ?? 0), 2), '0'), '.') }}%</span></td>
                        <td class="px-3 py-2" title="Calculado a partir de la meta y el valor alcanzado">{!! $badgeEstado($row['estado'] ?? null) !!}</td>
                        <td class="px-3 py-2"><textarea rows="2" wire:model.blur="resultados.{{ $i }}.producto_logrado" class="{{ $input }} min-w-[200px]"></textarea>@error('resultados.'.$i.'.producto_logrado')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</td>
                        <td class="px-3 py-2"><textarea rows="2" wire:model.blur="resultados.{{ $i }}.observaciones" class="{{ $input }} min-w-[200px]"></textarea></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-3 py-6 text-center text-gray-500">No hay resultados registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-1 text-[11px] text-gray-400">El objetivo, el resultado planificado y el indicador provienen del registro del proyecto. El cumplimiento (%) y el estado se calculan automáticamente a partir de la meta y el valor alcanzado.</p>
</section>
@endforeach

<section class="mt-10">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold">Ejecución de actividades</h3><p class="text-sm text-gray-500">Las actividades se gestionan independientemente de los resultados.</p></div><button type="button" wire:click="openActividadModal" class="{{ $button }} bg-blue-600 text-white hover:bg-blue-700">Agregar actividad ejecutada</button></div>
    <div class="mt-3 w-full overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[880px] text-sm">
            <thead class="bg-gray-50 text-left dark:bg-gray-800"><tr>@foreach(['#','Actividad planificada','Período','Estado','Producto / evidencia','Participantes','Acciones'] as $heading)<th class="px-3 py-3 font-semibold">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($actividades as $i => $row)
                    <tr wire:key="actividad-{{ $row['id'] ?? 'nueva-'.$i }}">
                        <td class="px-3 py-3">{{ $i + 1 }}</td>
                        <td class="max-w-60 px-3 py-3 align-top"><p class="line-clamp-2" title="{{ $row['actividad_planificada'] ?? '' }}">{{ $row['actividad_planificada'] ?: '—' }}</p></td>
                        <td class="px-3 py-3 whitespace-nowrap">{{ $this->formatearPeriodoActividad($row['fecha_inicial'] ?? null, $row['fecha_final'] ?? null) }}</td>
                        <td class="px-3 py-3">@php($actEstado = $row['estado'] ?? '')<span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $actEstado === 'ejecutada' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : ($actEstado === 'parcial' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : ($actEstado === 'no_ejecutada' ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400')) }}">{{ Str::headline($actEstado) ?: 'Sin registrar' }}</span></td>
                        <td class="max-w-52 px-3 py-3 align-top"><p class="line-clamp-2" title="{{ $row['medio_verificacion'] ?? '' }}">{{ $row['medio_verificacion'] ?: ($row['actividad_realizada'] ?: '—') }}</p></td>
                        <td class="max-w-52 px-3 py-3 align-top"><div class="flex flex-wrap gap-1">@forelse(collect($row['participantes'] ?? [])->sortBy('orden') as $participant)<span wire:key="actividad-participante-{{ $row['id'] ?? 'nueva-'.$i }}-{{ $participant['id'] ?? $participant['orden'] ?? $loop->index }}" class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">{{ $participant['nombre'] ?: 'Externo' }}</span>@empty<span class="text-xs text-gray-500">Sin participantes</span>@endforelse</div></td>
                        <td class="px-3 py-3 align-top whitespace-nowrap"><div class="flex flex-wrap gap-2"><button type="button" wire:click="openActividadModal({{ $i }})" class="inline-flex items-center gap-1 rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50" aria-label="Editar ejecución de la actividad">Editar</button>@if(blank($row['actividad_id'] ?? null) && ($row['origen'] ?? null) !== 'planificada')<button type="button" wire:click="quitarFila('actividades', {{ $i }})" wire:confirm="¿Eliminar esta actividad?" class="inline-flex items-center gap-1 rounded border border-red-200 px-2 py-1 text-xs text-red-700 hover:bg-red-50" aria-label="Eliminar actividad">Eliminar</button>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-8 text-center text-gray-500">No hay actividades ejecutadas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if($showActividadParticipanteEstadoModal)
<div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"><div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-900"><div class="border-b px-5 py-4"><h3 class="font-semibold">Cambiar estado de participación</h3></div><div class="space-y-4 p-5"><div><label class="{{ $label }}">Estado</label><select wire:model="actividadParticipanteEstado" class="{{ $input }}"><option value="activo">Participó</option><option value="no_finalizo">No finalizó</option></select></div><div><label class="{{ $label }}">Observación</label><textarea wire:model="actividadParticipanteObservacion" rows="4" class="{{ $input }}"></textarea>@error('actividadParticipanteObservacion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div><div class="flex justify-end gap-3 border-t px-5 py-4"><button type="button" wire:click="$set('showActividadParticipanteEstadoModal', false)" class="{{ $button }} border">Cancelar</button><button type="button" wire:click="guardarEstadoParticipanteActividad" class="{{ $button }} bg-blue-600 text-white">Guardar</button></div></div></div>
@endif

@if($showActividadModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:keydown.escape="closeActividadModal"><div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-xl bg-white shadow-xl dark:bg-gray-900"><div class="sticky top-0 flex items-center justify-between border-b bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-900"><h3 class="font-semibold">{{ $actividadModalSoloLectura ? 'Detalle de la actividad' : ($actividadModalIndex === null ? 'Agregar actividad ejecutada' : 'Editar actividad') }}</h3><button type="button" wire:click="closeActividadModal" class="text-xl text-gray-500">×</button></div><div class="grid gap-4 p-6 sm:grid-cols-2"><div class="sm:col-span-2"><label class="{{ $label }}">Actividad planificada</label><textarea rows="3" wire:model="actividadModal.actividad_planificada" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}"></textarea>@error('actividadModal.actividad_planificada')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="{{ $label }}">Fecha inicial</label><input type="date" wire:model="actividadModal.fecha_inicial" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}"></div><div><label class="{{ $label }}">Fecha final</label><input type="date" wire:model="actividadModal.fecha_final" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}"></div><div><label class="{{ $label }}">Estado</label><select wire:model="actividadModal.estado" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}"><option value="ejecutada">Ejecutada</option><option value="parcial">Parcial</option><option value="no_ejecutada">No ejecutada</option></select></div><div><label class="{{ $label }}">Origen</label><select wire:model="actividadModal.origen" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}"><option value="planificada">Planificada</option><option value="emergente">Emergente</option></select></div><div><label class="{{ $label }}">Horas dedicadas</label><input type="number" min="0" wire:model="actividadModal.horas_dedicadas" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}"></div><div><label class="{{ $label }}">Responsable principal</label><input wire:model="actividadModal.responsable" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}">@error('actividadModal.responsable')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div class="sm:col-span-2"><label class="{{ $label }}">Actividad realizada</label><textarea rows="3" wire:model="actividadModal.actividad_realizada" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}"></textarea></div><div class="sm:col-span-2"><label class="{{ $label }}">Medio de verificación</label><textarea rows="3" wire:model="actividadModal.medio_verificacion" @disabled($actividadModalSoloLectura) class="{{ $actividadModalSoloLectura ? $readonly : $input }}"></textarea></div><div class="sm:col-span-2 rounded-lg bg-gray-50 p-4 dark:bg-gray-800"><div class="flex flex-wrap items-end justify-between gap-3"><div><h4 class="font-medium">Participantes</h4><p class="text-xs text-gray-500">Se conservan los participantes y responsable de la actividad.</p></div>@unless($actividadModalSoloLectura)<div class="flex gap-2"><select wire:model="participanteSeleccionActividadModal" class="{{ $input }} min-w-52"><option value="externo:nuevo">Participante externo</option>@foreach($this->opcionesParticipantesActividad as $group=>$options)<optgroup label="{{ $group }}">@foreach($options as $option)<option value="{{ $option['value'] }}">{{ $option['label'] }}</option>@endforeach</optgroup>@endforeach</select><button type="button" wire:click="agregarParticipanteActividadModal" class="{{ $button }} bg-blue-50 text-blue-700">Agregar</button></div>@endunless</div><div class="mt-3 space-y-2">@forelse($actividadModal['participantes'] ?? [] as $i=>$participant)<div class="flex items-center justify-between rounded border bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"><span>{{ $participant['nombre'] ?: 'Participante externo' }} · {{ $participant['rol'] ?: 'Participante' }}</span>@unless($actividadModalSoloLectura)<button type="button" wire:click="quitarParticipanteActividadModal({{ $i }})" class="text-red-600">Quitar</button>@endunless</div>@empty<p class="text-sm text-gray-500">No hay participantes relacionados.</p>@endforelse</div></div></div><div class="sticky bottom-0 flex justify-end gap-3 border-t bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-900"><button type="button" wire:click="closeActividadModal" class="{{ $button }} border border-gray-300">Cerrar</button>@unless($actividadModalSoloLectura)<button type="button" wire:click="guardarActividadModal" class="{{ $button }} bg-blue-600 text-white">{{ $actividadModalIndex === null ? 'Guardar actividad' : 'Actualizar actividad' }}</button>@endunless</div></div></div>
@endif
