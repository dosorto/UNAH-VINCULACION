<div>
    <div class="bg-white dark:bg-gray-900 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Fichas de Actualización por Firmar
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                        Fichas de actualización de proyectos que requieren su firma
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 px-3 py-1 rounded-full text-sm font-medium">
                        Actualizaciones
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</div>
