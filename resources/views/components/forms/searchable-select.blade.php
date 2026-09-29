@props([
    'model' => null,
    'options' => [],
    'selected' => null,
    'disabled' => false,
    'label' => 'Seleccionar opción',
    'placeholder' => null,
    'emptyText' => 'No se encontraron opciones.',
])

@php
    // Admite tanto :model="'form.campo'" como wire:model.live="campo".
    $wireModel = $model ?: $attributes->wire('model')->value();

    if (blank($wireModel)) {
        throw new InvalidArgumentException('El componente searchable-select requiere model o wire:model.');
    }

    // Admite [id => etiqueta] y listas de objetos con id/label/nombre.
    $items = collect($options)->map(function ($value, $key) {
        if (is_array($value)) {
            return [
                'id' => (string) ($value['id'] ?? $key),
                'label' => (string) ($value['label'] ?? $value['nombre'] ?? $value['text'] ?? ''),
            ];
        }

        return ['id' => (string) $key, 'label' => (string) $value];
    })->values()->all();

    $componentKey = 'searchable-select-'.md5(
        $wireModel.json_encode($items).json_encode($selected).(int) $disabled
    );
@endphp

<div wire:key="{{ $componentKey }}" class="relative" x-id="['selector', 'opciones']"
    x-data="{
        open: false,
        search: '',
        active: -1,
        disabled: @js((bool) $disabled),
        options: @js($items),
        selected: @entangle($wireModel).live,
        normalize(value) {
            return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        },
        get filtered() {
            const term = this.normalize(this.search.trim());
            return term ? this.options.filter(option => this.normalize(option.label).includes(term)) : this.options;
        },
        get selectedOption() {
            return this.options.find(option => option.id === String(this.selected ?? ''));
        },
        get selectedText() {
            return this.selectedOption?.label || '';
        },
        show() {
            if (this.disabled || this.open) return;
            this.open = true;
            this.search = '';
            this.active = -1;
        },
        close() {
            this.open = false;
            this.search = '';
            this.active = -1;
        },
        move(direction) {
            if (!this.open) this.show();
            if (!this.filtered.length) return;
            this.active = (this.active + direction + this.filtered.length) % this.filtered.length;
            this.$nextTick(() => this.$refs.list?.querySelectorAll('[role=option]')[this.active]?.scrollIntoView({ block: 'nearest' }));
        },
        choose(value) {
            this.selected = value || null;
            this.close();
        },
    }"
    x-on:click.outside="close()" x-on:keydown.escape.stop.prevent="close()"
    x-on:focusout="if (!$el.contains($event.relatedTarget)) close()">
    <div class="relative">
        <input x-ref="search" type="text" autocomplete="off" :disabled="disabled"
            x-bind:value="open ? search : selectedText"
            x-on:focus="show()" x-on:click="show()"
            x-on:input="search = $event.target.value; open = true; active = -1"
            placeholder="{{ $placeholder ?? 'Buscar o seleccionar '.mb_strtolower($label).'...' }}"
            aria-label="Buscar: {{ $label }}" role="combobox" aria-autocomplete="list" aria-haspopup="listbox"
            x-bind:aria-expanded="open" x-bind:aria-controls="$id('opciones')"
            x-bind:aria-activedescendant="active >= 0 ? $id('selector') + '-' + active : null"
            x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
            x-on:keydown.enter.prevent="if (open && active >= 0 && filtered[active]) choose(filtered[active].id)"
            class="w-full rounded-lg border border-gray-300 bg-white py-3 pl-3 pr-14 text-sm text-gray-900 placeholder-gray-400 shadow-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        <button x-cloak x-show="selected !== null && selected !== ''" type="button" :disabled="disabled"
            x-on:click="choose(null)" aria-label="Quitar selección: {{ $label }}"
            class="absolute inset-y-0 right-7 px-1 text-gray-400 hover:text-gray-700 focus:text-blue-600 disabled:hidden dark:hover:text-gray-200">&times;</button>
        <svg class="pointer-events-none absolute right-3 top-1/2 h-3 w-3 -translate-y-1/2 text-gray-400" x-bind:class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6 8l4 4 4-4H6z"/></svg>
    </div>
    <div x-cloak x-show="open" class="absolute z-[70] mt-1 w-full overflow-hidden rounded-lg border border-gray-100 bg-white py-1 shadow-lg dark:border-gray-600 dark:bg-gray-800">
        <div role="listbox" x-bind:id="$id('opciones')" aria-label="{{ $label }}" class="max-h-64 overflow-y-auto">
            <div x-ref="list">
                <template x-for="(option, index) in filtered" :key="option.id">
                    <button type="button" tabindex="-1" role="option" x-bind:id="$id('selector') + '-' + index" x-bind:aria-selected="String(selected ?? '') === option.id"
                        x-on:mousedown.prevent x-on:click="choose(option.id)" x-on:mouseenter="active = index"
                        class="block w-full px-3 py-3 text-left text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        x-bind:class="active === index || String(selected ?? '') === option.id ? 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200' : 'text-gray-900 dark:text-gray-100'"
                        x-text="option.label"></button>
                </template>
            </div>
            <p x-show="filtered.length === 0" role="status" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ $emptyText }}</p>
        </div>
    </div>
</div>
