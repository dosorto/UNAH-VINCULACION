<div class="md:min-h-[250px]">
    @if (config('services.microsoft.enabled'))
        <div class="mb-4">
            <a
                href="{{ route('login.microsoft.redirect') }}"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-800 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-900"
            >
                <span class="grid h-4 w-4 grid-cols-2 gap-0.5" aria-hidden="true">
                    <span class="bg-[#f25022]"></span>
                    <span class="bg-[#7fba00]"></span>
                    <span class="bg-[#00a4ef]"></span>
                    <span class="bg-[#ffb900]"></span>
                </span>
                Continuar con tu correo institucional
            </a>
        </div>
    @endif

    @if ($this->localPasswordLoginEnabled())
        @if (config('services.microsoft.enabled'))
            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-white px-2 text-gray-500 dark:bg-gray-800 dark:text-gray-400">Acceso de desarrollo</span>
                </div>
            </div>
        @endif

        <form wire:submit="create">
            @if ($errors->any())
                <div class="mb-3 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-600 dark:bg-red-900/30 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mb-3">
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Correo institucional
                </label>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    autocomplete="email"
                    class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
            </div>

            <div class="mb-4">
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Contraseña
                </label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    autocomplete="current-password"
                    class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    required
                />
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-blue-900 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-yellow-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
            >
                <span wire:loading.remove>Iniciar sesión</span>
                <span wire:loading>Verificando...</span>
            </button>
        </form>
    @endif
</div>
