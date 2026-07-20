<div x-data="{ tab: 'pendientes' }" @daft-review-assigned.window="tab = 'proceso'" class="space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <section class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-700 dark:text-cyan-400">Revision institucional</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Bandeja de revision</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Seguimiento de programas donde ya participaste en el flujo de revision.</p>
        </div>
        <a href="{{ route('daft.programas') }}" class="rounded-full border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Volver a programas</a>
    </section>

    @if (session('programas_status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('programas_status') }}
        </div>
    @endif

    @if (session('programas_warning'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
            {{ session('programas_warning') }}
        </div>
    @endif

    @if ($pendingNotice)
        <div class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-800 dark:border-cyan-900/60 dark:bg-cyan-950/40 dark:text-cyan-200">
            {{ $pendingNotice }}
        </div>
    @endif

    <section class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
            <div class="flex flex-wrap items-center gap-3">
                <button @click="tab = 'pendientes'" :class="tab === 'pendientes' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="rounded-full px-4 py-2 text-sm font-medium transition">
                    Pendientes
                    <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ $programasPendientes->count() }}</span>
                </button>
                <button @click="tab = 'proceso'" :class="tab === 'proceso' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="rounded-full px-4 py-2 text-sm font-medium transition">
                    Revisados en proceso
                    <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ $programasEnProceso->count() }}</span>
                </button>
                <button @click="tab = 'aprobados'" :class="tab === 'aprobados' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="rounded-full px-4 py-2 text-sm font-medium transition">
                    Aprobados
                    <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ $programasAprobados->count() }}</span>
                </button>
            </div>
        </div>

        <div x-show="tab === 'pendientes'">
            @include('livewire.daft.programas.partials.bandeja-table', ['records' => $programasPendientes, 'emptyMessage' => 'No tienes programas pendientes en tu bandeja de revision.'])
        </div>

        <div x-show="tab === 'proceso'">
            @include('livewire.daft.programas.partials.bandeja-table', ['records' => $programasEnProceso, 'emptyMessage' => 'No tienes programas revisados que sigan en proceso.'])
        </div>

        <div x-show="tab === 'aprobados'">
            @include('livewire.daft.programas.partials.bandeja-table', ['records' => $programasAprobados, 'emptyMessage' => 'No tienes programas aprobados en los que hayas participado.'])
        </div>
    </section>
</div>
