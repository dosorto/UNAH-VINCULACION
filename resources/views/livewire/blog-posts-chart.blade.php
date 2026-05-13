<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Usuarios registrados</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalUsuarios }}</p>
        <p class="text-xs text-green-600 dark:text-green-400 mt-1">↑ Usuarios registrados</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Proyectos</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalProyectos }}</p>
        <p class="text-xs text-green-600 dark:text-green-400 mt-1">↑ Proyectos registrados</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Centros y Facultades</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalFacultades }}</p>
        <p class="text-xs text-green-600 dark:text-green-400 mt-1">↑ Centros y Facultades registrados</p>
    </div>

</div>
