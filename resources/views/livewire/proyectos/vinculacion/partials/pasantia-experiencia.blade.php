@php
    $gruposExperiencia = [
        ['01', 'Descripción del cargo', 'Describa el trabajo que realizará y los resultados que espera alcanzar.', [
            ['descripcion_experiencia', 'Descripción de la experiencia y resultados de la pasantía', 'textarea', '¿Qué espera aprender y aportar durante la pasantía?'],
            ['descripcion_cargo', 'Descripción del cargo', 'textarea', 'Describa la función que desempeñará.'],
            ['resumen_responsabilidades', 'Resumen de las responsabilidades y tareas que realizará', 'textarea', 'Detalle las principales actividades que realizará.'],
            ['area_departamento', 'Nombre del departamento o área donde se realizará', 'text', 'Ej. Departamento de tecnología'],
            ['area_conocimiento', 'Área de conocimiento que se aplicará', 'text', 'Ej. Desarrollo de software'],
        ]],
        ['02', 'Área de conocimiento que se aplicará', 'Relacione la experiencia con su formación y las habilidades que desarrollará.', [
            ['descripcion_conocimientos_teoricos', 'Descripción de los conocimientos teóricos que se aplicarán', 'textarea', '¿Qué conocimientos de su carrera aplicará?'],
            ['habilidades_desarrollar', 'Habilidades que se desarrollarán', 'textarea', 'Ej. Trabajo en equipo, análisis y comunicación.'],
        ]],
    ];
@endphp
<div class="space-y-6 md:col-span-2">
    @foreach($gruposExperiencia as [$numero, $titulo, $descripcion, $campos])
        <section class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700" aria-labelledby="experiencia-grupo-{{ $numero }}">
            <div class="flex items-start gap-3 border-b border-gray-200 bg-slate-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-200">{{ $numero }}</span>
                <div>
                    <h3 id="experiencia-grupo-{{ $numero }}" class="font-semibold text-gray-900 dark:text-white">{{ $titulo }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $descripcion }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                @if($numero === '02')
                    @include('livewire.proyectos.vinculacion.partials.pasantia-asignaturas')
                @endif
                @foreach($campos as [$campo, $etiqueta, $tipo, $ejemplo])
                    <label wire:key="experiencia-{{ $campo }}" class="block min-w-0 {{ $campo === 'descripcion_experiencia' ? 'md:col-span-2' : '' }}">
                        <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $etiqueta }}
                            @if($this->campoObligatorio($campo, 3))<span class="text-red-600" aria-label="obligatorio">*</span>@else<span class="ml-1 text-xs font-normal text-gray-400">Opcional</span>@endif
                        </span>
                        @if($tipo === 'textarea')
                            <textarea wire:model.blur="form.{{ $campo }}" rows="4" placeholder="{{ $ejemplo }}" class="{{ $inputClass }} resize-y" aria-invalid="{{ $errors->has('form.'.$campo) ? 'true' : 'false' }}"></textarea>
                        @else
                            <input type="text" wire:model.blur="form.{{ $campo }}" placeholder="{{ $ejemplo }}" class="{{ $inputClass }}" aria-invalid="{{ $errors->has('form.'.$campo) ? 'true' : 'false' }}">
                        @endif
                        @error('form.'.$campo)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700" aria-labelledby="experiencia-compensacion" x-data="{ remunerada: $wire.entangle('form.pasantia_remunerada').live }">
        <div class="flex items-start gap-3 border-b border-gray-200 bg-slate-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-200">03</span>
            <div>
                <h3 id="experiencia-compensacion" class="font-semibold text-gray-900 dark:text-white">Compensación</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Indique si recibirá una remuneración económica por la pasantía.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-2">
            <fieldset>
                <legend class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-200">Pasantía remunerada <span class="text-red-600" aria-label="obligatorio">*</span></legend>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['Sí' => 'Con remuneración', 'No' => 'Sin remuneración'] as $valor => $detalle)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition focus-within:ring-2 focus-within:ring-blue-500" :class="remunerada === @js($valor) ? 'border-blue-500 bg-blue-50 dark:bg-blue-950' : 'border-gray-200 dark:border-gray-600'">
                            <input type="radio" name="pasantia_remunerada" value="{{ $valor }}" x-model="remunerada" class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span><span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $valor }}</span><span class="block text-xs text-gray-500 dark:text-gray-400">{{ $detalle }}</span></span>
                        </label>
                    @endforeach
                </div>
                @error('form.pasantia_remunerada')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </fieldset>
            <div>
                <label for="monto-remuneracion" class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-200">Monto de la remuneración <span x-show="remunerada === 'Sí'" class="text-red-600" aria-label="obligatorio">*</span></label>
                <input id="monto-remuneracion" type="number" min="0" step="0.01" wire:model.blur="form.monto_remuneracion" x-bind:disabled="remunerada !== 'Sí'" @disabled(($form['pasantia_remunerada'] ?? null) !== 'Sí') placeholder="0.00" aria-describedby="monto-remuneracion-ayuda" class="{{ $inputClass }} disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-gray-800">
                <p id="monto-remuneracion-ayuda" class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="remunerada === 'Sí' ? 'Ingrese el monto acordado con la institución.' : 'El monto no aplica si la pasantía no es remunerada.'"></p>
                @error('form.monto_remuneracion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>
</div>
