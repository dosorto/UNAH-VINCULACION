<div>
    <div class="bg-white dark:bg-gray-900 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Fichas de Actualización
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                        Fichas de actualización pendientes de revisión por DVUS
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-300 px-3 py-1 rounded-full text-sm font-medium">
                        Revisión DVUS
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</div>
