<div>
    @if ($profileCompletionRequired)
        <div class="border-l-4 border-red-500 pl-2 mb-4">
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
                Completa tu perfil
            </p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                Asegúrate de completar tu perfil con información actualizada ya que luego NO podrás modificarla.
            </p>
        </div>
    @endif

    @if ($typeUser == 'Empleado')
        @livewire('personal.perfil.edit-perfil-docente')
    @elseif ($typeUser == 'Estudiante')
        @livewire('personal.perfil.edit-perfil-estudiante')
    @else
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            No existe un perfil de empleado o estudiante asociado a esta cuenta. Contacta al administrador del sistema.
        </div>
    @endif
</div>
