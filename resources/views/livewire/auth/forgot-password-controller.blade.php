<div>
    <form wire:submit.prevent="submit">

        @if ($errors->has('email'))
            <div class="mb-3 rounded-md bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-600 p-3 text-sm text-red-700 dark:text-red-300">
                {{ $errors->first('email') }}
            </div>
        @endif

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Correo electrónico
            </label>
            <input
                type="email"
                id="email"
                wire:model="email"
                autocomplete="email"
                class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required
            />
        </div>

        <div wire:loading class="flex justify-center my-4">
            <div class="loader"></div>
        </div>

        <button
            type="submit"
            class="w-full text-black bg-yellow-400 hover:bg-yellow-600 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
        >
            <span wire:loading.remove>Verificar</span>
            <span wire:loading>Enviando...</span>
        </button>
    </form>

    <style>
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</div>
