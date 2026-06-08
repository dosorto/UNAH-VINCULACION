<div class="mb-4 mt-4 flex justify-between items-center">
    <div class="w-full rounded-2xl p-10 mb-12">
        <div class="flex items-start sm:items-center p-4 mb-4 text-sm text-yellow-700 rounded-base bg-yellow-100 border border-yellow-300 rounded-sm" role="alert">
            <svg class="w-4 h-4 me-2 shrink-0 mt-0.5 sm:mt-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11h2v5m-2 0h4m-2.592-8.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <p><span class="font-medium me-1">¡IMPORTANTE!</span> Para registrar un proyecto de Vinculación, todos los integrantes deben estar registrados en NEXO.</p>
        </div>

        @if ($mostrarFormulariosPps)
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-yellow-700 dark:text-yellow-300">Práctica Profesional Supervisada / Servicio Social / Voluntariado</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-blue-900 dark:text-yellow-500 tracking-wide">
                        Seleccione el formulario a registrar
                    </h2>
                </div>
                <a href="{{ route('selectorTipoAccion') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Volver
                </a>
            </div>

            <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 3v4a1 1 0 0 1-1 1H5m4 10v-2m3 2v-6m3 6v-3m4-11v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">FORM-013 - Registro de Pasantías</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registro documental para pasantías universitarias.</p>
                    </div>
                </div>

                <a href="{{ route('crearPpsServicioSocial') }}"
                   class="group relative block bg-white dark:bg-gray-800 border border-yellow-200 dark:border-yellow-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300">
                    <span class="absolute top-4 right-4 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800 transition">
                        Disponible
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-yellow-100 dark:bg-yellow-900 rounded-full p-4 mb-5 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800 transition">
                            <svg class="w-10 h-10 text-yellow-700 dark:text-yellow-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.6144 7.19994c.3479.48981.5999 1.15357.5999 1.80006 0 1.6569-1.3432 3-3 3-1.6569 0-3.00004-1.3431-3.00004-3 0-.67539.22319-1.29865.59983-1.80006M6.21426 6v4m0-4 6.00004-3 6 3-6 2-2.40021-.80006M6.21426 6l3.59983 1.19994M6.21426 19.8013v-2.1525c0-1.6825 1.27251-3.3075 2.95093-3.6488l3.04911 2.9345 3-2.9441c1.7026.3193 3 1.9596 3 3.6584v2.1525c0 .6312-.5373 1.1429-1.2 1.1429H7.41426c-.66274 0-1.2-.5117-1.2-1.1429Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-yellow-900 dark:text-yellow-200 mb-2 text-center">FORM-014 - Registro PPS y Servicio Social</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra práctica profesional supervisada o servicio social con el formulario PPS/SS actual.</p>
                    </div>
                </a>

                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.5 12.5 8 16m.5-8.5L12 11m0 0 3.5-3.5M12 11l3.5 3.5M5 21V5a2 2 0 0 1 2-2h4.5L17 8.5V21H5Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">FORM-015 - Registro Proyecto de Voluntariado</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registro documental para proyectos de voluntariado universitario.</p>
                    </div>
                </div>
            </div>
        @elseif ($mostrarFormulariosDesarrolloLocal)
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Proyectos de desarrollo local y regional</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-blue-900 dark:text-yellow-500 tracking-wide">
                        Seleccione el formulario a registrar
                    </h2>
                </div>
                <a href="{{ route('selectorTipoAccion') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Volver
                </a>
            </div>

            <div class="grid grid-cols-1 gap-10 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('crearProyectoVinculacion', ['tipo_accion_id' => $tipoAccionDesarrolloLocalId]) }}"
                   class="group relative block bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300">
                    <span class="absolute top-4 right-4 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition">
                        Disponible
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-4 mb-5 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition">
                            <svg class="w-10 h-10 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 3v4a1 1 0 0 1-1 1H5m4 10v-2m3 2v-6m3 6v-3m4-11v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-blue-900 dark:text-blue-200 mb-2 text-center">FORM-001 - Registro de Proyectos de Vinculación Desarrollo Local y Regional</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Abre el formulario actual para registrar proyectos de vinculación con enfoque territorial.</p>
                    </div>
                </a>

                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7141 15h4.268c.4043 0 .732-.3838.732-.8571V3.85714c0-.47338-.3277-.85714-.732-.85714H6.71411c-.55228 0-1 .44772-1 1v4m10.99999 7v-3h3v3h-3Zm-3 6H6.71411c-.55228 0-1-.4477-1-1 0-1.6569 1.34315-3 3-3h2.99999c1.6569 0 3 1.3431 3 3 0 .5523-.4477 1-1 1Zm-1-9.5c0 1.3807-1.1193 2.5-2.5 2.5s-2.49999-1.1193-2.49999-2.5S8.8334 9 10.2141 9s2.5 1.1193 2.5 2.5Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">FORM-002 - Registro de Asesoramiento y Consultorías</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Formulario documental para acciones de asesoramiento y consultoría.</p>
                    </div>
                </div>

                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4 6 4v14M9 9h1m4 0h1M9 13h1m4 0h1M9 17h1m4 0h1"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">FORM-003 - Registro de Servicios de Infraestructura Académicos</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Formulario documental para servicios de infraestructura académica.</p>
                    </div>
                </div>

                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3 3 8l9 5 9-5-9-5Zm0 10v8m-6-5 6 3 6-3"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">FORM-004 - Registro de Prácticas Educativas</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Formulario documental para prácticas educativas.</p>
                    </div>
                </div>
            </div>
        @elseif ($mostrarFormulariosEducacionNoFormal)
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">Educación no formal</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-blue-900 dark:text-yellow-500 tracking-wide">
                        Seleccione el formulario a registrar
                    </h2>
                </div>
                <a href="{{ route('selectorTipoAccion') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Volver
                </a>
            </div>

            <div class="grid grid-cols-1 gap-10 md:grid-cols-2 xl:grid-cols-5">
                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 0 1 2-2Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">FORM-016 - Registro de Certificados Universitarios</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Formulario documental para certificados universitarios.</p>
                    </div>
                </div>

                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">FORM-017 - Registro Educación No Formal - Actividades</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Formulario documental para actividades de educación no formal.</p>
                    </div>
                </div>

                <a href="{{ route('enf.acciones.create', ['tipo_accion_enf_id' => $tipoAccionEnfId, 'nuevo' => 1]) }}"
                   class="group relative block bg-white dark:bg-gray-800 border border-orange-200 dark:border-orange-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300">
                    <span class="absolute top-4 right-4 bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-orange-200 dark:group-hover:bg-orange-800 transition">
                        Disponible
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-orange-100 dark:bg-orange-900 rounded-full p-4 mb-5 group-hover:bg-orange-200 dark:group-hover:bg-orange-800 transition">
                            <svg class="w-10 h-10 text-orange-700 dark:text-orange-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-orange-900 dark:text-orange-200 mb-2 text-center">FORM-018 - Registro Educación No Formal - Proyectos</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Abre el formulario actual de Educación no formal.</p>
                    </div>
                </a>

                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a4 4 0 0 1 8 0v2m-9 0h10a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">FORM-019 - Registro de Certificados de Posgrados</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Formulario documental para certificados de posgrados.</p>
                    </div>
                </div>

                <div class="group relative block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl p-8 transition-all duration-300 opacity-40">
                    <span class="absolute top-4 right-4 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm7 0v5h5M9 13h6M9 17h6"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">Formato de Informe Final ENF</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Formato documental para informe final de educación no formal.</p>
                    </div>
                </div>
            </div>
        @else
            <h2 class="text-3xl font-extrabold text-blue-900 dark:text-yellow-500 mb-8 text-center tracking-wide">
                ¿Qué tipo de acción desea registrar?
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <a href="{{ route('selectorTipoAccion', ['grupo' => 'desarrollo-local']) }}"
                   class="group relative block bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300">
                    <span class="absolute top-4 right-4 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition">
                        Vinculación Territorial
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-4 mb-5 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition">
                            <svg class="w-10 h-10 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 3v4a1 1 0 0 1-1 1H5m4 10v-2m3 2v-6m3 6v-3m4-11v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-blue-900 dark:text-blue-200 mb-2 text-center">Proyectos de desarrollo local y regional</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos de vinculación con contraparte y enfoque territorial.</p>
                    </div>
                </a>

                <a href="{{ route('selectorTipoAccion', ['grupo' => 'pps']) }}"
                   class="group relative block bg-white dark:bg-gray-800 border border-yellow-200 dark:border-yellow-700 shadow-lg rounded-xl p-8 text-left hover:scale-105 hover:shadow-2xl transition-all duration-300">
                    <span class="absolute top-4 right-4 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800 transition">
                        Disponible
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-yellow-100 dark:bg-yellow-900 rounded-full p-4 mb-5 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800 transition">
                            <svg class="w-10 h-10 text-yellow-700 dark:text-yellow-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.6144 7.19994c.3479.48981.5999 1.15357.5999 1.80006 0 1.6569-1.3432 3-3 3-1.6569 0-3.00004-1.3431-3.00004-3 0-.67539.22319-1.29865.59983-1.80006M6.21426 6v4m0-4 6.00004-3 6 3-6 2-2.40021-.80006M6.21426 6l3.59983 1.19994M6.21426 19.8013v-2.1525c0-1.6825 1.27251-3.3075 2.95093-3.6488l3.04911 2.9345 3-2.9441c1.7026.3193 3 1.9596 3 3.6584v2.1525c0 .6312-.5373 1.1429-1.2 1.1429H7.41426c-.66274 0-1.2-.5117-1.2-1.1429Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-yellow-900 dark:text-yellow-200 mb-2 text-center">Práctica Profesional Supervisada / Servicio Social / Voluntariado</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra acciones relacionadas con pasantías, práctica profesional supervisada, servicio social y voluntariado universitario.</p>
                    </div>
                </a>

                <a href="{{ route('selectorTipoAccion', ['grupo' => 'educacion-no-formal']) }}"
                   class="group relative block bg-white dark:bg-gray-800 border border-orange-200 dark:border-orange-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300">
                    <span class="absolute top-4 right-4 bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-orange-200 dark:group-hover:bg-orange-800 transition">
                        Disponible
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-orange-100 dark:bg-orange-900 rounded-full p-4 mb-5 group-hover:bg-orange-200 dark:group-hover:bg-orange-800 transition">
                            <svg class="w-10 h-10 text-orange-700 dark:text-orange-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 9H5a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h6m0-6v6m0-6 5.419-3.87A1 1 0 0 1 18 5.942v12.114a1 1 0 0 1-1.581.814L11 15m7 0a3 3 0 0 0 0-6M6 15h3v5H6v-5Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-orange-900 dark:text-orange-200 mb-2 text-center">Educación no formal</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra cursos, talleres, diplomados, congresos, seminarios y educación continua.</p>
                    </div>
                </a>

                <div class="group relative block bg-white dark:bg-gray-800 border border-green-200 dark:border-green-700 shadow-lg rounded-xl p-8 transition-all duration-300 pointer-events-none opacity-20">
                    <span class="absolute top-4 right-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-green-100 dark:bg-green-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-green-700 dark:text-green-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7141 15h4.268c.4043 0 .732-.3838.732-.8571V3.85714c0-.47338-.3277-.85714-.732-.85714H6.71411c-.55228 0-1 .44772-1 1v4m10.99999 7v-3h3v3h-3Zm-3 6H6.71411c-.55228 0-1-.4477-1-1 0-1.6569 1.34315-3 3-3h2.99999c1.6569 0 3 1.3431 3 3 0 .5523-.4477 1-1 1Zm-1-9.5c0 1.3807-1.1193 2.5-2.5 2.5s-2.49999-1.1193-2.49999-2.5S8.8334 9 10.2141 9s2.5 1.1193 2.5 2.5Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-green-900 dark:text-green-200 mb-2 text-center">Seguimiento a egresados</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos que promueven actividades de seguimiento a egresados.</p>
                    </div>
                </div>

                <div class="group relative block bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-700 shadow-lg rounded-xl p-8 transition-all duration-300 pointer-events-none opacity-20">
                    <span class="absolute top-4 right-4 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-purple-100 dark:bg-purple-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-purple-700 dark:text-purple-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m7.53316 11.8623.00957-.0029m5.58157 7.1424c-.5.515-.9195.8473-1.0611.8903-.4784.1454-5.42881-1.2797-6.23759-3.3305-.80878-2.0507-1.83058-5.8152-1.88967-6.2192-.0591-.40404 1.5599-1.72424 3.59722-2.61073m1.98839 8.05513c-.22637.262-.38955.5599-.55552.8474M13.4999 12c.5.5 1 1.049 2 1.049s1.5-.549 2-1.049m-4-4h.01m3.99 0h.01m-7.01-2.5c0-.28929 2.5-1.5 5-1.5s5 1.13645 5 1.5V12c0 1.9655-4.291 5-5 5-.7432 0-5-3.0345-5-5V5.5Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-purple-900 dark:text-purple-200 mb-2 text-center">Vínculos académicos</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos de actividades académicas y relaciones institucionales.</p>
                    </div>
                </div>

                <div class="group relative block bg-white dark:bg-gray-800 border border-pink-200 dark:border-pink-700 shadow-lg rounded-xl p-8 transition-all duration-300 pointer-events-none opacity-20">
                    <span class="absolute top-4 right-4 bg-pink-100 dark:bg-pink-900 text-pink-700 dark:text-pink-300 px-3 py-1 rounded-full text-xs font-semibold">
                        Próximamente
                    </span>
                    <br>
                    <div class="flex flex-col items-center">
                        <div class="bg-pink-100 dark:bg-pink-900 rounded-full p-4 mb-5">
                            <svg class="w-10 h-10 text-pink-700 dark:text-pink-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.6144 7.19994c.3479.48981.5999 1.15357.5999 1.80006 0 1.6569-1.3432 3-3 3-1.6569 0-3.00004-1.3431-3.00004-3 0-.67539.22319-1.29865.59983-1.80006M6.21426 6v4m0-4 6.00004-3 6 3-6 2-2.40021-.80006M6.21426 6l3.59983 1.19994M6.21426 19.8013v-2.1525c0-1.6825 1.27251-3.3075 2.95093-3.6488l3.04911 2.9345 3-2.9441c1.7026.3193 3 1.9596 3 3.6584v2.1525c0 .6312-.5373 1.1429-1.2 1.1429H7.41426c-.66274 0-1.2-.5117-1.2-1.1429Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-pink-900 dark:text-pink-200 mb-2 text-center">Cultura y comunicación</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos de actividades artísticas, culturales y de comunicación.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
