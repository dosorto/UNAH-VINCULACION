@props([
    'options' => [],
    'placeholder' => 'Seleccionar…',
    'searchPlaceholder' => 'Buscar…',
    'disabled' => false,
    'emptyText' => 'Sin resultados.',
])

@php
    // Normaliza a [['id' => '...', 'label' => '...'], ...]
    $normalized = collect($options)->map(function ($value, $key) {
        if (is_array($value)) {
            return ['id' => (string) ($value['id'] ?? $key), 'label' => (string) ($value['label'] ?? $value['nombre'] ?? '')];
        }
        if (is_object($value)) {
            return ['id' => (string) ($value->id ?? $key), 'label' => (string) ($value->label ?? $value->nombre ?? '')];
        }
        return ['id' => (string) $key, 'label' => (string) $value];
    })->values()->all();

    $wireModel = $attributes->wire('model')->value();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        disabled: @js($disabled),
        options: @js($normalized),
        selected: @entangle($wireModel).live,
        selectedValues() {
            const ids = this.options.map(o => o.id);
            return [...new Set((this.selected || []).map(String).filter(id => id !== '' && ids.includes(id)))];
        },
        selectedLabels() {
            return this.selectedValues().map(id => this.options.find(o => o.id === id)).filter(Boolean);
        },
        filteredOptions() {
            const t = this.search.trim().toLowerCase();
            return this.options.filter(o => !t || o.label.toLowerCase().includes(t));
        },
        isSelected(id) { return this.selectedValues().includes(String(id)); },
        toggle(id) {
            if (this.disabled) return;
            id = String(id);
            const cur = this.selectedValues();
            const i = cur.indexOf(id);
            i === -1 ? cur.push(id) : cur.splice(i, 1);
            this.selected = cur;
            this.search = '';
            this.$nextTick(() => this.$refs.search?.focus());
        },
        remove(id) {
            if (this.disabled) return;
            this.selected = this.selectedValues().filter(v => v !== String(id));
        },
    }"
    @click.outside="open = false"
    class="relative"
>
    <div
        @click="!disabled && (open = true, $nextTick(() => $refs.search?.focus()))"
        class="min-h-[42px] max-h-24 w-full overflow-y-auto rounded-md border px-2.5 py-2"
        :class="disabled
            ? 'cursor-not-allowed border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800'
            : 'cursor-text border-gray-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800'"
    >
        <div class="flex min-w-0 flex-wrap items-center gap-1.5 pr-4">
            <template x-for="item in selectedLabels()" :key="item.id">
                <span class="inline-flex max-w-[180px] items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                    <span class="truncate" x-text="item.label"></span>
                    <button type="button" x-show="!disabled" @click.stop="remove(item.id)" class="shrink-0 font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                </span>
            </template>
            <input
                x-ref="search"
                x-model="search"
                x-show="!disabled"
                @focus="open = true"
                @keydown.escape="open = false"
                :placeholder="selectedValues().length ? '' : @js($searchPlaceholder)"
                type="text"
                class="min-w-[120px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white"
            >
            <span x-show="disabled && !selectedValues().length" class="text-sm italic text-gray-500">{{ $placeholder }}</span>
            <span x-show="!disabled" class="ml-auto shrink-0 text-xs text-gray-400" x-text="open ? '▴' : '▾'"></span>
        </div>
    </div>

    <div x-show="open" x-cloak class="absolute left-0 right-0 z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
        <template x-if="filteredOptions().length === 0">
            <div class="px-3 py-2 text-sm text-gray-500">{{ $emptyText }}</div>
        </template>
        <template x-for="option in filteredOptions()" :key="option.id">
            <div
                @click="toggle(option.id)"
                class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-700"
                :class="isSelected(option.id) ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'"
            >
                <span x-text="option.label"></span>
                <span x-show="isSelected(option.id)" class="text-xs">✓</span>
            </div>
        </template>
    </div>
</div>
