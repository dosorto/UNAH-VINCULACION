<div
    aria-live="assertive"
    class="fixed inset-0 flex flex-col items-end justify-start gap-2 p-4 pointer-events-none z-50"
    style="top: 1rem; right: 1rem; left: auto; bottom: auto;"
>
    @foreach ($notifications as $notification)
        @php
            $colors = match($notification['type']) {
                'success' => 'bg-green-50 border-green-400 text-green-800 dark:bg-green-900/30 dark:border-green-600 dark:text-green-200',
                'danger'  => 'bg-red-50 border-red-400 text-red-800 dark:bg-red-900/30 dark:border-red-600 dark:text-red-200',
                'warning' => 'bg-yellow-50 border-yellow-400 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-600 dark:text-yellow-200',
                default   => 'bg-blue-50 border-blue-400 text-blue-800 dark:bg-blue-900/30 dark:border-blue-600 dark:text-blue-200',
            };
            $icon = match($notification['type']) {
                'success' => '✓',
                'danger'  => '✕',
                'warning' => '⚠',
                default   => 'ℹ',
            };
        @endphp
        <div
            class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border shadow-lg {{ $colors }}"
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
        >
            <div class="flex items-start p-4">
                <span class="mr-3 text-lg font-bold">{{ $icon }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold">{{ $notification['title'] }}</p>
                    @if($notification['body'])
                        <p class="mt-1 text-xs opacity-80">{{ $notification['body'] }}</p>
                    @endif
                </div>
                <button
                    wire:click="remove('{{ $notification['id'] }}')"
                    class="ml-3 shrink-0 opacity-60 hover:opacity-100 text-lg leading-none"
                    aria-label="Cerrar"
                >&times;</button>
            </div>
        </div>
    @endforeach
</div>
