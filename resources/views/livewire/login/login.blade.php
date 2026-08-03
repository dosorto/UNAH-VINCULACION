<div class="md:min-h-[250px]">
    @if (config('services.microsoft.enabled'))
        <div class="mb-4">
            <a
                href="{{ route('login.microsoft.redirect') }}"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-800 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-900"
            >
                <span class="grid h-4 w-4 grid-cols-2 gap-0.5" aria-hidden="true">
                    <span class="bg-[#f25022]"></span>
                    <span class="bg-[#7fba00]"></span>
                    <span class="bg-[#00a4ef]"></span>
                    <span class="bg-[#ffb900]"></span>
                </span>
                Continuar con tu correo institucional
            </a>
        </div>
    @endif

</div>
