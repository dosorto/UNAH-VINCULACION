<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuarios registrados</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $totalUsuarios }}</p>
    </div>
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Proyectos registrados</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $totalProyectos }}</p>
    </div>
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Centros y Facultades</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $totalCentros }}</p>
    </div>
</div>
