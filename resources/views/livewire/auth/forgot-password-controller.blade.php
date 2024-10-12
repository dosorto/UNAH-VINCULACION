<div>
    <form wire:submit.prevent="submit">
        {{ $this->form }}

        <div style="text-align: center; margin-top: 2rem;  margin-bottom: 2rem;">
            <!-- Spinner de carga centrado y visible solo durante la carga -->
            <div wire:loading>
                <div class="loader"></div>
            </div>
        </div>

        <button type="submit"
                class="w-full text-black bg-yellow-400 hover:bg-yellow-600 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
            Verificar
        </button>
    </form>

    <x-filament-actions::modals />

    <!-- Estilos del spinner -->
    <style>
        /* Estilo del spinner (círculo de carga) */
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</div>
