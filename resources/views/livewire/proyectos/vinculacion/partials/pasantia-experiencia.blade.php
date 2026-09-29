@php
    $gruposExperiencia = [
        ['a)', 'Descripción del cargo', 'Describa el trabajo que realizará y los resultados que espera alcanzar.', [
            ['resumen_responsabilidades', 'Resumen de las responsabilidades y tareas que realizará', 'textarea', 'Detalle las principales actividades que realizará.'],
            ['area_departamento', 'Nombre del departamento o área donde se realizará', 'text', 'Ej. Departamento de tecnología'],
        ]],
        ['b)', 'Área de conocimiento que se aplicará', 'Relacione la experiencia con su formación y las habilidades que desarrollará.', [
            ['descripcion_conocimientos_teoricos', 'Descripción de los conocimientos teóricos que se aplicarán', 'textarea', '¿Qué conocimientos de su carrera aplicará?'],
        ]],
        ['c)', 'Habilidades que se desarrollarán', 'Describa las habilidades que desarrollará durante la pasantía.', [
            ['habilidades_desarrollar', 'Habilidades que se desarrollarán', 'textarea', 'Ej. Trabajo en equipo, análisis y comunicación.'],
        ]],
    ];
@endphp
<div class="space-y-6 md:col-span-2">
    <h2 class="text-lg font-semibold">Descripción de la experiencia y resultados de la pasantía</h2>
    @foreach($gruposExperiencia as [$numero, $titulo, $descripcion, $campos])
        <section class="space-y-4" aria-labelledby="experiencia-grupo-{{ $numero }}">
            <div class="border-b border-gray-200 pb-2 dark:border-gray-700">
                <div>
                    <h3 id="experiencia-grupo-{{ $numero }}" class="font-semibold text-gray-900 dark:text-white">{{ $numero }} {{ $titulo }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $descripcion }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @if($numero === 'b)')
                    @include('livewire.proyectos.vinculacion.partials.pasantia-asignaturas')
                @endif
                @foreach($campos as [$campo, $etiqueta, $tipo, $ejemplo])
                    <label wire:key="experiencia-{{ $campo }}" class="block min-w-0 {{ $tipo === 'textarea' ? 'md:col-span-2' : '' }}">
                        <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $etiqueta }}
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

    <section class="space-y-4" aria-labelledby="experiencia-compensacion" x-data="{ remunerada: $wire.entangle('form.pasantia_remunerada').live }">
        <div class="border-b border-gray-200 pb-2 dark:border-gray-700">
            <div>
                <h3 id="experiencia-compensacion" class="font-semibold text-gray-900 dark:text-white">d) Compensación</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Indique si recibirá una remuneración económica por la pasantía.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Pasantía remunerada <span class="text-red-600" aria-label="obligatorio">*</span></label>
                <x-forms.searchable-select model="form.pasantia_remunerada" :options="['Sí' => 'Sí', 'No' => 'No']" :selected="$form['pasantia_remunerada'] ?? null" label="Pasantía remunerada" />
                @error('form.pasantia_remunerada')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="monto-remuneracion" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Monto de la remuneración <span x-show="remunerada === 'Sí'" class="text-red-600" aria-label="obligatorio">*</span></label>
                <input id="monto-remuneracion" type="number" min="0" step="0.01" wire:model.blur="form.monto_remuneracion" x-bind:disabled="remunerada !== 'Sí'" @disabled(($form['pasantia_remunerada'] ?? null) !== 'Sí') placeholder="0.00" aria-describedby="monto-remuneracion-ayuda" class="{{ $inputClass }} disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-gray-800">
                <p id="monto-remuneracion-ayuda" class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="remunerada === 'Sí' ? 'Ingrese el monto acordado con la institución.' : 'El monto no aplica si la pasantía no es remunerada.'"></p>
                @error('form.monto_remuneracion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>
</div>
