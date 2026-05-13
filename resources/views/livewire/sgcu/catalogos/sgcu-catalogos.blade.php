<div class="space-y-8">
    <section class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-emerald-700 dark:text-emerald-400">Fase 1</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Catalogos nucleo SGCU</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Administra la base institucional requerida para construir y revisar programas.</p>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">Campus</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $metricas['campus'] }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">Centros / facultades</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $metricas['centros'] }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">Asignaturas</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $metricas['asignaturas'] }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">Periodos</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $metricas['periodos'] }}</p>
        </article>
    </section>

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.2fr)_minmax(360px,0.8fr)]">
        <section class="space-y-6">
            <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Catalogos institucionales</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Gestiona las bases de campus y centros para SGCU.</p>
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <a href="{{ route('campus') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-800/60">
                        Campus
                    </a>
                    <a href="{{ route('facultad-centro') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-800/60">
                        Centros y facultades
                    </a>
                </div>
            </article>

            <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Catalogos academicos</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Carga de asignaturas y periodos academicos.</p>
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-400 dark:border-slate-800 dark:text-slate-500">
                        Asignaturas (pendiente)
                    </div>
                    <div class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-400 dark:border-slate-800 dark:text-slate-500">
                        Periodos (pendiente)
                    </div>
                </div>
            </article>
        </section>

        <section class="space-y-6">
            <article class="rounded-3xl bg-gradient-to-br from-emerald-200 to-emerald-100 p-6 text-slate-900 shadow-lg dark:from-emerald-950 dark:to-slate-900 dark:text-slate-100 dark:ring-1 dark:ring-emerald-900/60">
                <h2 class="text-lg font-semibold">Catalogos de programas</h2>
                <p class="mt-3 text-sm text-slate-700 dark:text-slate-300">Con estas bases listas puedes construir programas, agregar asignaturas, definir centros y ejecutar flujos de revision.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('sgcu.programas') }}" class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-emerald-700 dark:bg-slate-100">Ir a programas</a>
                    <a href="{{ route('configuracion.flujos.proyectos') }}" class="rounded-full border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-700 dark:border-emerald-800 dark:text-emerald-300">Ir a flujos</a>
                </div>
            </article>
        </section>
    </div>
</div>
