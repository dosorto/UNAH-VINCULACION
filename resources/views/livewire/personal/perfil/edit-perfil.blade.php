<div>
    @can('cambiar-datos-personales')
        <div class="border-l-4 border-red-500 pl-2 mb-4">
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
                Completa tu perfil
            </p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                Asegúrate de completar tu perfil con información actualizada ya que luego NO podrás modificarla.
            </p>
        </div>
    @endcan

    @if ($typeUser == 'Empleado')
        @livewire('personal.perfil.edit-perfil-docente')
    @elseif ($typeUser == 'Estudiante')
        @livewire('personal.perfil.edit-perfil-estudiante')
    @endif
</div>
