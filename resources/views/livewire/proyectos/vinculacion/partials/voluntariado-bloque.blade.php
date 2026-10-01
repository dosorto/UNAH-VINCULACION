{{-- Voluntariado del paso 2 (ítems 13 y 14 del FORM-DVUS-001; 15 y 16 del FORM-DVUS-015).
     $bloque: titulo, subtitulo, columnas y automatico (el personal de la UNAH se calcula con el equipo). --}}
<div wire:key="voluntariado-{{ \Illuminate\Support\Str::slug($bloque['titulo']) }}">
    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $bloque['titulo'] }} @if($esVoluntariado && !$bloque['automatico'])<span class="text-red-500">*</span>@endif</h4>
    <p class="text-xs text-gray-500 mt-0.5 mb-3">{{ $bloque['subtitulo'] }}.
        @if($bloque['automatico'])
            Se calcula con los empleados del equipo marcados como voluntarios, según la categoría y el sexo de su perfil.
        @else
            {{ $esVoluntariado ? 'Registre 0 si no aplica.' : 'Deje en blanco o en 0 si no aplica.' }}
        @endif
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 {{ count($bloque['columnas']) === 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-3">
        @foreach($bloque['columnas'] as $prefijo => $etiqueta)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $etiqueta }}</p>
            <div class="grid grid-cols-2 gap-2">
                @foreach(['hombres' => 'Hombres', 'mujeres' => 'Mujeres'] as $sufijo => $sexo)
                <div>
                    <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-1">{{ $sexo }}</label>
                    @if($bloque['automatico'])
                    <input type="number" readonly tabindex="-1" value="{{ (int) ($voluntariado_participacion[$prefijo.'_'.$sufijo] ?? 0) }}"
                        class="w-full rounded-md border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 px-2 py-1.5 text-sm text-gray-600 dark:text-gray-400" />
                    @else
                    <input type="number" min="0" step="1" inputmode="numeric" @unless($esVoluntariado) placeholder="0" @endunless
                        wire:model.blur="voluntariado_participacion.{{ $prefijo }}_{{ $sufijo }}"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:border-blue-500" />
                    @endif
                    @error("voluntariado_participacion.{$prefijo}_{$sufijo}") <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
