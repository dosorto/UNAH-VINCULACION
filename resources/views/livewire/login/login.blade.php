<div>
    <form wire:submit="create" class="md:h-[250px]">

        @if ($errors->any())
            <div class="mb-3 rounded-md bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-600 p-3 text-sm text-red-700 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-3">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Correo institucional
            </label>
            <input
                type="email"
                id="email"
                wire:model="email"
                autocomplete="email"
                class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required
            />
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Contraseña
            </label>
            <input
                type="password"
                id="password"
                wire:model="password"
                autocomplete="current-password"
                class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required
            />
        </div>

        <button
            type="submit"
            class="w-full text-white bg-blue-900 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
            <span wire:loading.remove>Iniciar sesión</span>
            <span wire:loading>Verificando...</span>
        </button>

    </form>
</div>
