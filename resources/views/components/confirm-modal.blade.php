<div
    x-data
    x-show="$store.confirmDialog.show"
    x-cloak
    x-transition.opacity
    @keydown.escape.window="$store.confirmDialog.cancel()"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4"
>
    <div
        @click.outside="$store.confirmDialog.cancel()"
        class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900"
    >
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="$store.confirmDialog.title"></h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" x-text="$store.confirmDialog.message"></p>

        <div class="mt-6 flex justify-end gap-3">
            <button
                type="button"
                @click="$store.confirmDialog.cancel()"
                class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300"
            >
                <span x-text="$store.confirmDialog.cancelText"></span>
            </button>
            <button
                type="button"
                @click="$store.confirmDialog.confirm()"
                class="px-4 py-2 text-sm font-medium rounded-md text-white"
                :class="$store.confirmDialog.type === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
            >
                <span x-text="$store.confirmDialog.confirmText"></span>
            </button>
        </div>
    </div>
</div>
