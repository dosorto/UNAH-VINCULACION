{{--
    Fila de resultado del marco lógico (paso 7): Resultado | Indicador | Medio de verificación.
    Recibe: $ruta (base del wire:model), $numero, $wireKey, $quitar (acción de wire:click) y $ejemplos.
--}}
@php
    $campos = [
        'nombre_resultado' => ['Resultado', $ejemplos['resultado'] ?? ''],
        'nombre_indicador' => ['Indicador', $ejemplos['indicador'] ?? ''],
        'nombre_medio_verificacion' => ['Medio de verificación', $ejemplos['medio'] ?? ''],
    ];
@endphp
<div wire:key="{{ $wireKey }}" class="grid grid-cols-1 gap-2 py-2 md:grid-cols-[1.5rem_1fr_1fr_1fr_2rem] md:items-start md:gap-3">
    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 md:pt-2"><span class="md:hidden">Resultado </span>{{ $numero }}</span>
    @foreach($campos as $campo => [$etiqueta, $ejemplo])
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400 md:sr-only">{{ $etiqueta }} <span class="text-red-500">*</span></label>
            <textarea rows="2" wire:model.live.debounce.1000ms="{{ $ruta }}.{{ $campo }}" placeholder="{{ $ejemplo }}"
                class="w-full resize-y rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
            @error("{$ruta}.{$campo}") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    @endforeach
    <button wire:click="{{ $quitar }}" type="button" title="Quitar resultado"
        class="justify-self-end text-xs font-medium text-red-600 hover:text-red-800 md:justify-self-center md:pt-2">
        ✕<span class="md:sr-only"> Quitar resultado</span>
    </button>
</div>
