<div data-enf-send-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="relative max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Enviar proyecto para revisión</h2>
                <p data-enf-send-subtitle class="mt-1 text-sm text-slate-500 dark:text-slate-400">Seleccione el destinatario para cada etapa del flujo.</p>
            </div>
            <button type="button" data-enf-send-close class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-700">
                &times;
            </button>
        </div>

        <div data-enf-send-steps class="mt-5 flex flex-wrap items-center gap-2"></div>
        <div data-enf-send-body></div>

        <div class="mt-6 flex items-center justify-between">
            <button type="button" data-enf-send-cancel class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                Cancelar
            </button>
            <div class="flex items-center gap-2">
                <button type="button" data-enf-send-prev class="hidden inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                    &larr; Anterior
                </button>
                <button type="button" data-enf-send-next class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900">
                    Siguiente &rarr;
                </button>
                <button type="button" data-enf-send-confirm class="hidden inline-flex items-center rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600">
                    Confirmar envío
                </button>
            </div>
        </div>
    </div>
</div>
