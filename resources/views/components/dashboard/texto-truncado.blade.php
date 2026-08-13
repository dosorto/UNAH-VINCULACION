@props(['texto'])

<span
    class="group relative block min-w-0 max-w-full"
    tabindex="0"
    title="{{ $texto }}"
>
    <span class="block truncate">{{ $texto }}</span>
    <span
        role="tooltip"
        class="pointer-events-none invisible absolute left-0 top-full z-50 mt-2 w-max max-w-xs rounded-lg bg-slate-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100 dark:bg-slate-100 dark:text-slate-900"
    >
        {{ $texto }}
    </span>
</span>
