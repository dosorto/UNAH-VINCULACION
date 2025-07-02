<div>
    <form wire:submit="create" class="md:h-[250px]">
        @if (config('app.env') == 'local')
            {{ $this->form }}
            <br>
            <button type="submit"
                class="w-full text-white bg-blue-900 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                Iniciar
            </button>
            <br>
            <p class="text-sm font-light text-gray-500 dark:text-gray-400 mt-2">
            </p>
        @endif

        @if (config('app.env') == 'production')
            <a href="{{ route('auth.microsoft') }}" class="flex items-center justify-center w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-black dark:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                <svg class="w-6 h-6 me-2 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                    height="100" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                        d="m3.5 5.5 7.893 6.036a1 1 0 0 0 1.214 0L20.5 5.5M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z" />
                </svg>

                Correo Institucional
            </a>

        @endif

    </form>

    <x-filament-actions::modals />
</div>
