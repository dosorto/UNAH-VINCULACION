<div>
    <form wire:submit="create">
        {{ $this->form }}
        <br>
        <button type="submit"
                class="w-full text-black bg-yellow-400 hover:bg-yellow-600 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
            Iniciar
        </button>
        <br>
        <br>
        <p class="text-sm font-light text-gray-500 dark:text-gray-400">
            ¿Olvidaste tu contraseña? <a href="{{ route('password.request') }}"
                class="font-medium text-primary-600 hover:underline dark:text-primary-500">Recuperar</a>
        </p>
    </form>

    <x-filament-actions::modals />
</div>
