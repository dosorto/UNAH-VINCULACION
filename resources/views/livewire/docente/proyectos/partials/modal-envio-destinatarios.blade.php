{{--
    Modal de envío a un flujo cuyas etapas piden elegir destinatario (Informe Intermedio y cierre).
    Recibe: $titulo, $descripcion, $opciones (etapas seleccionables), $candidatos (por etapa),
    $modelo (propiedad Livewire, p. ej. «destinatariosCierre»), $seleccionados, $obligatorio,
    $accionEnviar, $accionCerrar, $textoEnviar y $clave (prefijo de wire:key).
--}}
<div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50" wire:click="{{ $accionCerrar }}"></div>
    <div class="relative flex min-h-full items-start justify-center p-4">
        <div class="relative my-4 w-full max-w-xl rounded-lg bg-white shadow-xl dark:bg-gray-900">
            <div class="rounded-t-lg border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $titulo }}</h4>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $descripcion }}</p>
            </div>
            <div class="space-y-4 p-5">
                @foreach($opciones as $etapaId => $opcion)
                    <div wire:key="{{ $clave }}-destinatario-{{ $etapaId }}">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ $opcion['etapa']->nombre }}
                            @if($obligatorio)<span class="text-red-500">*</span>@else<span class="font-normal text-gray-400">(opcional)</span>@endif
                        </label>
                        <x-forms.searchable-user-select
                            :model="$modelo.'.'.$etapaId"
                            :options="$candidatos[$etapaId] ?? []"
                            :selected="$seleccionados[$etapaId] ?? null"
                            placeholder="Escriba el nombre del destinatario..."
                            :wire-key="$clave.'-select-'.$etapaId"
                        />
                    </div>
                @endforeach
                @error($modelo)<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-2 rounded-b-lg border-t border-gray-200 px-5 py-3 dark:border-gray-700">
                <button type="button" wire:click="{{ $accionCerrar }}" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Cancelar</button>
                <button type="button" wire:click="{{ $accionEnviar }}" wire:loading.attr="disabled" wire:target="{{ $accionEnviar }}" class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="{{ $accionEnviar }}">{{ $textoEnviar }}</span>
                    <span wire:loading wire:target="{{ $accionEnviar }}">Enviando…</span>
                </button>
            </div>
        </div>
    </div>
</div>
