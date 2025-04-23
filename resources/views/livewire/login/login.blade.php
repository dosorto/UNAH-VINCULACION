<div>
    <form wire:submit="create">
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
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23">
                <path fill="#f3f3f3" d="M0 0h23v23H0z"/>
                <path fill="#f35325" d="M1 1h10v10H1z"/>
                <path fill="#81bc06" d="M12 1h10v10H12z"/>
                <path fill="#05a6f0" d="M1 12h10v10H1z"/>
                <path fill="#ffba08" d="M12 12h10v10H12z"/>
            </svg>
            Iniciar sesión con Microsoft
        </a>
        
                @endif

    </form>

    <x-filament-actions::modals />
</div>
