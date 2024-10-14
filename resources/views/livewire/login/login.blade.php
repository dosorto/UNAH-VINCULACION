<div>
    <form wire:submit="create">
        {{ $this->form }}
        <br>
        <button type="submit"
            class="w-full text-black bg-yellow-400 hover:bg-yellow-600 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
            Iniciar
        </button>
        <br>
        <div class="mt-4"><a href="{{ route('auth.microsoft') }}">Login</a>
            <a id="microsoft-auth-button" 
                class="w-full text-black bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                tabindex="0" type="button"><span class="css-1ti50tg"><img
                        src="https://id-frontend.prod-east.frontend.public.atl-paas.net/assets/microsoft-logo.c73d8dca.svg"
                        alt=""
                        href="{{ route('auth.microsoft') }}"
                        >
                </span><span class="css-178ag6o">Microsoft</span>
            </a>
        </div>
        <p class="text-sm font-light text-gray-500 dark:text-gray-400">
            ¿Olvidaste tu contraseña? <a href="{{ route('password.request') }}"
                class="font-medium text-primary-600 hover:underline dark:text-primary-500">Recuperar</a>
        </p>
    </form>

    <x-filament-actions::modals />
</div>
