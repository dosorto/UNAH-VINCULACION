{{-- filepath: /Users/vanessa/Desktop/vinculacion/UNAH-VINCULACION/resources/views/livewire/proyectos/vinculacion/tipo-proyecto-selector.blade.php --}}
<div class="container mx-auto py-10">
    <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl p-8 mb-10">
        <h2 class="text-2xl font-bold text-blue-800 dark:text-yellow-400 mb-6 text-center">Seleccione el tipo de acción que desea registrar</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Tarjeta Proyecto de Desarrollo Local y Regional -->
            <a href="{{ route('crearProyectoVinculacion') }}" class="block bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 shadow rounded-lg p-6 hover:bg-blue-100 dark:hover:bg-blue-900 transition">
                <div class="flex flex-col items-center">
                    <svg class="w-12 h-12 text-blue-700 dark:text-blue-800 mb-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 6v6l4 2"></path>
                        <circle cx="12" cy="12" r="10"></circle>
                    </svg>
                    <h3 class="text-lg font-bold text-blue-900 dark:text-blue-500 mb-2">Proyecto de Desarrollo Local y Regional</h3>
                    <p class="text-gray-700 dark:text-gray-400 text-center">Registra un proyecto de vinculación con contraparte.</p>
                </div>
            </a>
        </div>
    </div>
</div>