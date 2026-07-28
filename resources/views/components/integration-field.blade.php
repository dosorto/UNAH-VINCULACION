@props([
    'label',
    'model',
    'type' => 'text',
    'placeholder' => '',
])

<label class="block space-y-1.5">
    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</span>
    <input
        type="{{ $type }}"
        wire:model="{{ $model }}"
        placeholder="{{ $placeholder }}"
        @if ($type === 'number') min="0" @endif
        class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
    >
    @error($model)
        <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
    @enderror
</label>
