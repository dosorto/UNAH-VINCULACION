<div>
    @can('docente-cambiar-datos-personales')
        <div class="border-l-4 border-red-500 pl-2 mb-4">
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
                Completa tu perfil
            </p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                Asegúrate de completar tu perfil con información actualizada ya que luego NO podrás modificarla.
            </p>
        </div>
    @endcan
    <form wire:submit="save">
        {{ $this->form }}
        <br>
        <x-filament::button type="submit" icon="heroicon-o-arrow-up-circle"
            class=" text-black  hover:bg-yellow-600 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
            Actualizar Perfil
        </x-filament::button>
    </form>
    <x-filament-actions::modals />
</div>
