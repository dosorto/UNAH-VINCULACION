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
                ¿Olvidaste tu contraseña? <a href="{{ route('password.request') }}"
                    class="font-medium text-primary-600 hover:underline dark:text-primary-500">Recuperar</a>
            </p>
        @endif

        @if (config('app.env') == 'production')
            <div
                class="w-full max-w-md p-6 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-md flex flex-col items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">
                    Usa tu cuenta Institucional
                </h2>

                <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
                    Para acceder a todas las funciones de {{ config('app.name') }}, inicia sesión con tu cuenta
                    Institucional y completa el registro si aun no lo has hecho.
                </p>

                <!-- Icono de Microsoft -->
                <a href="{{ route('auth.microsoft') }}"
                    class="flex items-center justify-center w-full px-4 py-2 bg-white dark:bg-gray-800 text-black dark:text-white border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                    <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21">
                        <rect x="1" y="1" width="9" height="9" fill="#f25022" />
                        <rect x="1" y="11" width="9" height="9" fill="#00a4ef" />
                        <rect x="11" y="1" width="9" height="9" fill="#7fba00" />
                        <rect x="11" y="11" width="9" height="9" fill="#ffb900" />
                    </svg>
                    Iniciar con Microsoft
                </a>
            </div>
        @endif

    </form>

    <x-filament-actions::modals />
</div>
