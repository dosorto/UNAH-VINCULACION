<div>
    <div class="bg-white dark:bg-gray-900 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Mis Fichas de Actualización
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                        Fichas de actualización que has creado para tus proyectos de vinculación
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 px-3 py-1 rounded-full text-sm font-medium">
                        Mis Actualizaciones
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</div>
