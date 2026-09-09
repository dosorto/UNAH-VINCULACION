<div class="text-gray-900">
    <div class="mb-6 flex flex-wrap items-center gap-2 border-b border-gray-200 pb-3 text-sm font-semibold text-slate-600">
        <a href="{{ route('selectorTipoAccion') }}" class="rounded-md px-4 py-2 transition hover:bg-gray-100 hover:text-blue-700">Registrar Acción</a>
        <a href="{{ route('proyectosDocente') }}" class="rounded-md px-4 py-2 transition hover:bg-gray-100 hover:text-blue-700">Mi Historial Vinculación</a>
        <a href="{{ route('fichasActualizacionVinculacion') }}" class="rounded-md px-4 py-2 transition hover:bg-gray-100 hover:text-blue-700">Mis Fichas de Actualización</a>
        <a href="{{ route('proyectosAntesDelSistema') }}" class="rounded-md px-4 py-2 transition hover:bg-gray-100 hover:text-blue-700">Vinculaciones Antes del Sistema</a>
    </div>
    <div class="mb-5"><p class="text-xs font-semibold uppercase tracking-wide text-blue-700">FORM-DVUS-013</p><h1 class="mt-1 text-2xl font-bold text-gray-950">Registro de Pasantías</h1><p class="mt-2 text-sm text-gray-500">Complete la información del formulario y guárdela como borrador.</p></div>
    <div class="mb-6 rounded-lg bg-white p-4 shadow">
        <div class="flex items-center gap-0.5 overflow-x-auto">

        @foreach($pasos as $numero => $nombre)
            <button type="button" wire:click="irAPaso({{ $numero }})" class="group flex min-w-[82px] flex-1 shrink-0 flex-col items-center rounded-md p-1 transition hover:bg-gray-50"><span class="mb-1 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $pasoActual === $numero ? 'bg-blue-600 text-white ring-2 ring-blue-300' : 'bg-gray-200 text-gray-600' }}">{{ $numero }}</span><span class="text-center text-[10px] leading-tight {{ $pasoActual === $numero ? 'font-semibold text-blue-600' : 'text-gray-500' }}">{{ $nombre }}</span></button>
            @if($numero < count($pasos))<div class="h-0.5 w-3 shrink-0 bg-gray-200"></div>@endif
        @endforeach
        </div>
    </div>

    <div class="border border-blue-200 bg-blue-50 p-4 text-sm text-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="font-semibold">Resumen del registro</span>
            <span>{{ $registroId ? 'Borrador guardado' : 'Aún no guardado' }}</span>
        </div>
        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <span><b>Estudiante:</b> {{ $form['nombre_estudiante'] ?: 'Pendiente' }}</span>
            <span><b>Institución:</b> {{ $form['nombre_institucion'] ?: 'Pendiente' }}</span>
            <span><b>Modalidad:</b> {{ $form['modalidad_ejecucion'] ?: 'Pendiente' }}</span>
        </div>
    </div>

    <div class="rounded-lg bg-white p-6 shadow">
        @php
            $secciones = [
                1 => [['fecha_registro','Fecha de registro','date'],['facultad_centro','Facultad / centro','text'],['escuela_departamento','Escuela / departamento académico','text'],['carrera','Carrera','text'],['numero_cuenta','Número de cuenta','text'],['nombre_estudiante','Nombre completo del estudiante','text'],['celular_estudiante','Número de celular','text'],['correo_institucional','Correo institucional','email'],['correo_personal','Correo personal','email']],
                2 => [['tipo_pasantia','Tipo de pasantía','text'],['fecha_inicio','Fecha de inicio','date'],['fecha_finalizacion','Fecha de finalización','date'],['duracion_semanas','Duración en semanas','number'],['total_horas','Total de horas programadas','number'],['horas_semanales','Promedio de horas semanales','number'],['cantidad_creditos','Cantidad de créditos académicos','number'],['modalidad_ejecucion','Modalidad de ejecución','text'],['pasantia_obligatoria','Pasantía obligatoria (Sí/No)','text'],['otorga_creditos','Otorgamiento de créditos (Sí/No)','text']],
                3 => [['descripcion_experiencia','Descripción de la experiencia y resultados','textarea'],['descripcion_cargo','Descripción del cargo','textarea'],['resumen_responsabilidades','Responsabilidades y tareas','textarea'],['area_departamento','Departamento o área','text'],['area_conocimiento','Área de conocimiento','text'],['codigo_asignatura','Código de asignatura','text'],['nombre_asignatura','Nombre de asignatura','text'],['descripcion_conocimientos_teoricos','Conocimientos teóricos','textarea'],['habilidades_desarrollar','Habilidades a desarrollar','textarea'],['pasantia_remunerada','Pasantía remunerada (Sí/No)','text'],['monto_remuneracion','Monto de remuneración','number']],
                4 => [['nombre_institucion','Nombre de la institución / organización','text'],['direccion_institucion','Dirección de la sede principal','textarea'],['ciudad_institucion','Ciudad','text'],['pais_institucion','País','text'],['representante_legal','Representante legal','text'],['telefono_representante','Teléfono','text'],['correo_rrhh','Correo de recursos humanos','email'],['tipo_institucion','Tipo de institución','text'],['sector_institucion','Sector','text'],['compromisos_institucion','Compromisos institucionales','textarea']],
                5 => [['nombre_contacto_directo','Nombre del contacto directo','text'],['celular_contacto_directo','Celular del contacto','text'],['correo_contacto_directo','Correo del contacto','email'],['cargo_contacto_directo','Cargo','text'],['grado_academico_contacto_directo','Grado académico','text'],['tipo_instrumento','Instrumento de formalización','text']],
                6 => [['nombre_docente_supervisor','Nombre del docente supervisor','text'],['numero_empleado_docente','Número de empleado','text'],['celular_docente','Celular','text'],['correo_docente','Correo electrónico','email'],['categoria_docente','Categoría','text'],['departamento_docente','Departamento','text'],['jornada_laboral_docente','Jornada laboral','text'],['ubicacion_cubiculo_docente','Ubicación del cubículo','text']],
                7 => [['nombre_firma_coordinador','Nombre del coordinador','text'],['firma_coordinador','Ruta de firma del coordinador','text'],['nombre_firma_supervisor','Nombre del supervisor','text'],['firma_supervisor','Ruta de firma del supervisor','text'],['nombre_firma_estudiante','Nombre del estudiante firmante','text'],['firma_estudiante','Ruta de firma del estudiante','text']],
                8 => [['adjunta_carta_formalizacion','Adjunta carta de formalización (Sí/No)','text'],['archivo_carta_formalizacion','Archivo de carta','text'],['adjunta_convenio_marco','Adjunta convenio marco (Sí/No)','text'],['archivo_convenio_marco','Archivo de convenio','text']],
            ];
        @endphp
        <h2 class="mb-5 text-lg font-semibold text-gray-900">Paso {{ $pasoActual }}: {{ $pasos[$pasoActual] }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($secciones[$pasoActual] as $indice => [$campo, $etiqueta, $tipo])
                <label class="block {{ $tipo === 'textarea' ? 'md:col-span-2' : '' }}">
                    <span class="mb-1 block text-sm font-medium text-gray-700">{{ $etiqueta }}</span>
                    <span class="block">
                    @if($tipo === 'textarea')
                        <textarea wire:model.blur="form.{{ $campo }}" rows="3" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                    @else
                        <input type="{{ $tipo }}" wire:model.blur="form.{{ $campo }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    @endif
                    </span>
                    @error('form.'.$campo)<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                </label>
            @endforeach
        </div>
        <div class="flex justify-between gap-3 border-t border-gray-300 bg-gray-50 p-4">
            <button type="button" wire:click="anterior" class="border border-blue-950 px-4 py-2 text-sm text-blue-950" @disabled($pasoActual === 1)>Anterior</button>
            <div class="flex gap-3"><button type="button" wire:click="guardarBorrador" class="border border-blue-950 bg-white px-4 py-2 text-sm font-semibold text-blue-950">Guardar borrador</button><button type="button" wire:click="siguiente" class="bg-blue-950 px-4 py-2 text-sm font-semibold text-white">{{ $pasoActual === 8 ? 'Finalizar' : 'Siguiente' }} →</button></div>
        </div>
    </div>
</div>
