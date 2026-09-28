@props([
    'options' => [],
    'placeholder' => 'Buscar o seleccionar...',
    'emptyText' => 'Sin resultados.',
    'disabled' => false,
])

@php
    // Acepta [id => etiqueta] o [['id' => ..., 'label' => ...], ...].
    $normalized = collect($options)->map(function ($value, $key) {
        if (is_array($value)) {
            return ['id' => (string) ($value['id'] ?? $key), 'label' => (string) ($value['label'] ?? $value['nombre'] ?? '')];
        }

        return ['id' => (string) $key, 'label' => (string) $value];
    })->values()->all();

    $wireModel = $attributes->wire('model')->value();
    // Las opciones viven en x-data: si cambian (otro país, otro departamento) el componente se recrea.
    $componentKey = 'searchable-select-'.$wireModel.'-'.md5(json_encode($normalized).($disabled ? '1' : '0'));
@endphp

{{-- Selección única con el mismo diseño que los selectores con búsqueda del registro de proyectos. --}}
<div
    wire:key="{{ $componentKey }}"
    x-data="{
        open: false,
        search: '',
        disabled: @js((bool) $disabled),
        options: @js($normalized),
        selected: @entangle($wireModel).live,
        normalize(value) {
            return String(value ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        },
        selectedOption() {
            return this.options.find(option => option.id === String(this.selected ?? ''));
        },
        filteredOptions() {
            const term = this.normalize(this.search.trim());
            return term ? this.options.filter(option => this.normalize(option.label).includes(term)) : this.options;
        },
        openList() {
            if (this.disabled) return;
            this.open = true;
            this.$nextTick(() => this.$refs.search?.focus());
        },
        close() {
            this.open = false;
            this.search = '';
        },
        choose(option) {
            this.selected = option.id;
            this.close();
        },
        clear() {
            this.selected = null;
            this.close();
        },
    }"
    @click.outside="close()"
    class="relative"
>
    <div
        @click="openList()"
        class="min-h-[42px] w-full rounded-md border px-3 py-2 flex flex-wrap gap-1.5 items-center transition"
        :class="disabled
            ? 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/60 cursor-not-allowed'
            : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 cursor-text focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500'"
    >
        <span x-show="selectedOption() && search === ''" class="truncate text-sm text-gray-900 dark:text-white" x-text="selectedOption()?.label"></span>
        <input
            x-ref="search"
            x-model="search"
            @focus="openList()"
            @keydown.escape="close()"
            @keydown.enter.prevent="filteredOptions().length && choose(filteredOptions()[0])"
            :disabled="disabled"
            :placeholder="selectedOption() ? '' : @js($placeholder)"
            class="min-w-[120px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 disabled:cursor-not-allowed disabled:text-gray-500 dark:text-white"
            type="text"
        />
        <button type="button" x-show="selectedOption() && !disabled" @click.stop="clear()" class="font-bold leading-none text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Quitar selección">×</button>
        <span class="ml-auto text-gray-400 text-xs" x-text="open && !disabled ? '▴' : '▾'"></span>
    </div>
    <div
        x-show="open && !disabled"
        x-cloak
        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto"
    >
        <template x-if="filteredOptions().length === 0">
            <div class="px-3 py-2 text-sm text-gray-500">{{ $emptyText }}</div>
        </template>
        <template x-for="option in filteredOptions()" :key="option.id">
            <div
                @click="choose(option)"
                class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between gap-3"
                :class="selectedOption()?.id === option.id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-700 dark:text-gray-300'"
            >
                <span x-text="option.label"></span>
                <span x-show="selectedOption()?.id === option.id" class="text-blue-600 dark:text-blue-300 text-xs">✓</span>
            </div>
        </template>
    </div>
</div>
