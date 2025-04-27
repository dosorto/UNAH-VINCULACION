{{-- filepath: c:\Users\znico\Desktop\Desarrollo\vinculacion\UNAH-VINCULACION\resources\views\livewire\inicio\dashboards\dashboard-estudiante.blade.php --}}
<div class="w-full mb-6 lg:mb-0">
    <div class="p-6 bg-yellow-500 dark:bg-yellow-500 rounded-xl">
        <div class="container px-4 mx-auto">
            <h3 class="text-3xl font-bold text-white">Bienvenido a tu Dashboard</h3>
            <p class="font-medium text-blue-200">Aquí podrás ver los proyectos en los que participas.</p>
        </div>
    </div>

    <!-- Tabla de proyectos -->
    <div class="w-full bg-white border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm dark:bg-gray-800 p-4 mt-6">
        <h4 class="text-lg text-gray-900 dark:text-white font-semibold mb-4">Mis Proyectos</h4>
        <table class="table-auto w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-2">Nombre</th>
                    <th class="px-4 py-2">Estado</th>
                    <th class="px-4 py-2">Fecha Inicio</th>
                    <th class="px-4 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($proyectos as $proyecto)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-4 py-2">{{ $proyecto->nombre }}</td>
                        <td class="px-4 py-2">{{ $proyecto->estado->nombre ?? 'Sin estado' }}</td>
                        <td class="px-4 py-2">{{ $proyecto->fecha_inicio }}</td>
                        <td class="px-4 py-2">
                            <a href="#" class="text-blue-600 hover:underline">Ver Detalles</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-500 dark:text-gray-400">No tienes proyectos asignados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>