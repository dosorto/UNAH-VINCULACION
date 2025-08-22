<div class="mb-4 mt-4 flex justify-between items-center">
    <div
        class="rounded-2xl p-10 mb-12">
        <h2
            class="text-3xl font-extrabold text-blue-900 dark:text-yellow-500 mb-8 text-center tracking-wide">
            ¿Qué tipo de acción desea registrar?
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <!-- Tarjeta Proyecto de Desarrollo Local y Regional -->
            <a href="{{ route('crearProyectoVinculacion') }}"
                class="group relative block bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300">
                <span
                    class="absolute top-4 right-4 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition">
                    Vinculación Territorial
                </span>
                <br>
                <div class="flex flex-col items-center">
                    <div
                        class="bg-blue-100 dark:bg-blue-900 rounded-full p-4 mb-5 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition">
                        <svg class="w-10 h-10 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 3v4a1 1 0 0 1-1 1H5m4 10v-2m3 2v-6m3 6v-3m4-11v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1Z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-blue-900 dark:text-blue-200 mb-2 text-center">Proyecto Desarrollo
                        Local y Regional</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos de
                        vinculación con contraparte y enfoque territorial.</p>
                </div>
            </a>

            <!-- Tarjeta Proyecto de Educación No Formal -->
            <a href="javascript:void(0)"
                class="group relative block bg-white dark:bg-gray-800 border border-green-200 dark:border-green-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300 pointer-events-none opacity-20">
                <span
                    class="absolute top-4 right-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-green-200 dark:group-hover:bg-green-800 transition">
                    Proximamente
                </span>
                <br>
                <div class="flex flex-col items-center">
                    <div
                        class="bg-green-100 dark:bg-green-900 rounded-full p-4 mb-5 group-hover:bg-green-200 dark:group-hover:bg-green-800 transition">
                        <svg class="w-10 h-10 text-green-700 dark:text-green-300" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7141 15h4.268c.4043 0 .732-.3838.732-.8571V3.85714c0-.47338-.3277-.85714-.732-.85714H6.71411c-.55228 0-1 .44772-1 1v4m10.99999 7v-3h3v3h-3Zm-3 6H6.71411c-.55228 0-1-.4477-1-1 0-1.6569 1.34315-3 3-3h2.99999c1.6569 0 3 1.3431 3 3 0 .5523-.4477 1-1 1Zm-1-9.5c0 1.3807-1.1193 2.5-2.5 2.5s-2.49999-1.1193-2.49999-2.5S8.8334 9 10.2141 9s2.5 1.1193 2.5 2.5Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-green-900 dark:text-green-200 mb-2 text-center">Educación No
                        Formal</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos que promueven actividades educativas fuera del sistema formal.</p>
                </div>
            </a>
            <!-- Tarjeta Proyecto de Voluntariado -->
            <a href="javascript:void(0)"
                class="group relative block bg-white dark:bg-gray-800 border border-yellow-200 dark:border-yellow-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300 pointer-events-none opacity-20"">
                <span
                    class="absolute top-4 right-4 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800 transition">
                    Proximamente
                </span>
                <br>
                <div class="flex flex-col items-center">
                    <div
                        class="bg-yellow-100 dark:bg-yellow-900 rounded-full p-4 mb-5 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800 transition">
                        <svg class="w-10 h-10 text-yellow-700 dark:text-yellow-300" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-yellow-900 dark:text-yellow-200 mb-2 text-center">Proyecto
                        Voluntariado</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos de acciones solidarias y participación social universitaria.</p>
                </div>
            </a>
            <!-- Tarjeta Proyecto de Comunicación -->
            <a href="javascript:void(0)"
                class="group relative block bg-white dark:bg-gray-800 border border-orange-200 dark:border-orange-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300 pointer-events-none opacity-20"">
                <span
                    class="absolute top-4 right-4 bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-orange-200 dark:group-hover:bg-orange-800 transition">
                    Proximamente
                </span>
                <br>
                <div class="flex flex-col items-center">
                    <div
                        class="bg-orange-100 dark:bg-orange-900 rounded-full p-4 mb-5 group-hover:bg-orange-200 dark:group-hover:bg-orange-800 transition">
                        <svg class="w-10 h-10 text-orange-700 dark:text-orange-300" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 9H5a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h6m0-6v6m0-6 5.419-3.87A1 1 0 0 1 18 5.942v12.114a1 1 0 0 1-1.581.814L11 15m7 0a3 3 0 0 0 0-6M6 15h3v5H6v-5Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-orange-900 dark:text-orange-200 mb-2 text-center">Proyecto
                        Comunicación</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos de divulgación y estrategias comunicacionales institucionales.</p>
                </div>
            </a>
            <!-- Tarjeta Proyecto de Comunicación -->
            <a href="javascript:void(0)"
                class="group relative block bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300 pointer-events-none opacity-20">
                <span
                    class="absolute top-4 right-4 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-purple-200 dark:group-hover:bg-purple-800 transition">
                    Proximamente
                </span>
                <br>
                <div class="flex flex-col items-center">
                    <div
                        class="bg-purple-100 dark:bg-purple-900 rounded-full p-4 mb-5 group-hover:bg-purple-200 dark:group-hover:bg-purple-800 transition">
                        <svg class="w-10 h-10 text-purple-700 dark:text-purple-300" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                              <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m7.53316 11.8623.00957-.0029m5.58157 7.1424c-.5.515-.9195.8473-1.0611.8903-.4784.1454-5.42881-1.2797-6.23759-3.3305-.80878-2.0507-1.83058-5.8152-1.88967-6.2192-.0591-.40404 1.5599-1.72424 3.59722-2.61073m1.98839 8.05513c-.22637.262-.38955.5599-.55552.8474M13.4999 12c.5.5 1 1.049 2 1.049s1.5-.549 2-1.049m-4-4h.01m3.99 0h.01m-7.01-2.5c0-.28929 2.5-1.5 5-1.5s5 1.13645 5 1.5V12c0 1.9655-4.291 5-5 5-.7432 0-5-3.0345-5-5V5.5Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-purple-900 dark:text-purple-200 mb-2 text-center">Proyecto Cultura
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos de actividades artísticas y promoción de identidad cultural.</p>
                </div>
            </a>
            <!-- Tarjeta Proyecto de Egresados -->
            <a href="javascript:void(0)"
                class="group relative block bg-white dark:bg-gray-800 border border-pink-200 dark:border-pink-700 shadow-lg rounded-xl p-8 hover:scale-105 hover:shadow-2xl transition-all duration-300 pointer-events-none opacity-20">
                <span
                    class="absolute top-4 right-4 bg-pink-100 dark:bg-pink-900 text-pink-700 dark:text-pink-300 px-3 py-1 rounded-full text-xs font-semibold group-hover:bg-pink-200 dark:group-hover:bg-pink-800 transition">
                    Proximamente
                </span>
                <br>
                <div class="flex flex-col items-center">
                    <div
                        class="bg-pink-100 dark:bg-pink-900 rounded-full p-4 mb-5 group-hover:bg-pink-200 dark:group-hover:bg-pink-800 transition">
                        <svg class="w-10 h-10 text-pink-700 dark:text-pink-300" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                           <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.6144 7.19994c.3479.48981.5999 1.15357.5999 1.80006 0 1.6569-1.3432 3-3 3-1.6569 0-3.00004-1.3431-3.00004-3 0-.67539.22319-1.29865.59983-1.80006M6.21426 6v4m0-4 6.00004-3 6 3-6 2-2.40021-.80006M6.21426 6l3.59983 1.19994M6.21426 19.8013v-2.1525c0-1.6825 1.27251-3.3075 2.95093-3.6488l3.04911 2.9345 3-2.9441c1.7026.3193 3 1.9596 3 3.6584v2.1525c0 .6312-.5373 1.1429-1.2 1.1429H7.41426c-.66274 0-1.2-.5117-1.2-1.1429Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-pink-900 dark:text-pink-200 mb-2 text-center">Proyecto
                        Egresados</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center text-base">Registra proyectos de integración y seguimiento de egresados universitarios.</p>
                </div>
            </a>
        </div>
    </div>
</div>
