@props([
    // [['etiqueta' => ..., 'valor' => ...], ...] — los mismos datos del gráfico
    'items' => [],
    'columnaEtiqueta' => 'Categoría',
    'columnaValor' => 'Valor',
    'resumen' => 'Ver los datos en tabla',
    'decimales' => 0,
])

@if (! empty($items))
    {{--
        Equivalente accesible de cada gráfico. Sin esto, los valores que solo
        aparecen al pasar el ratón quedan fuera del alcance de quien navega con
        teclado o lector de pantalla, y el color sería el único canal.

        Cerrado por defecto: acompaña al gráfico, no compite con él.
    --}}
    <details {{ $attributes->merge(['class' => 'group mt-4 border-t border-slate-100 pt-3 dark:border-slate-800']) }}>
        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-[11px] font-semibold text-slate-500 hover:text-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:text-slate-400 dark:hover:text-primary-300">
            @svg('heroicon-o-table-cells', ['class' => 'h-3.5 w-3.5'])
            {{ $resumen }}
        </summary>

        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th scope="col" class="py-1.5 pr-3 font-semibold text-slate-600 dark:text-slate-300">{{ $columnaEtiqueta }}</th>
                        <th scope="col" class="py-1.5 text-right font-semibold text-slate-600 dark:text-slate-300">{{ $columnaValor }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($items as $item)
                        <tr>
                            <td class="py-1.5 pr-3 text-slate-600 dark:text-slate-300">{{ $item['etiqueta'] }}</td>
                            <td class="py-1.5 text-right tabular-nums font-medium text-slate-900 dark:text-white">
                                {{ $decimales > 0 ? number_format((float) $item['valor'], $decimales) : number_format((int) $item['valor']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif
