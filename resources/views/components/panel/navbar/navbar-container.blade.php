<div id="mobile-menu"
    class="fixed inset-0 bg-white shadow-lg barra dark:barra transition-transform transform -translate-x-full sm:translate-x-0 sm:shadow-none  sm:flex h-screen flex-col justify-between py-4 pr-2 pl-4 w-3/4 sm:w-1/4 h-[100vh] sm:sticky top-0 sm:bg-gray-100 dark:bg-gray-950"
    style="z-index: 40; height: 100dvh;"> 
    <div id="fondoimagen" class="flex justify-between  flex-col h-full       flex-direction: column;">
        <div>

            <div class="flex items-center  justify-between ">
                <div class="flex items-center ">
                    <div class="w-36 h-8 rounded-lg flex  ">
                        <x-logo size="sm" color="rojo" displayText="true" displayIsotipo="true"/>
                    </div>

                </div>
                <div>
                    <button id="theme-toggle"
                        class="mr-2 p-2 rounded-full bg-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-gray-800 dark:text-gray-200 hidden"
                            fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                        <svg id="theme-toggle-light-icon" class="w-5 h-5 text-gray-800 dark:text-gray-200 hidden"
                            fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
                                fill-rule="evenodd" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <div class="relative inline-block text-left">
                        @php
                            $roles = Auth::user()->roles; // Obtiene la colección de roles con ID y nombre
                        @endphp

                        <button id="toggleButton"
                            class="toggle-button inline-flex justify-center items-center px-2 py-2 border border-gray-300  text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                                    dark:bg-gray-800 
                                        dark:text-gray-200 
                                        dark:border-gray-700 
                                        dark:hover:bg-white/5 
                                        dark:focus:ring-indigo-500 
                                        dark:focus-visible:bg-white/10
                                        dark:focus-visible:text-gray-200 
                                        dark:focus-visible:border-gray-700
                                
                                ">
                            <svg id="chevronIcon" xmlns="http://www.w3.org/2000/svg" class="chevron-icon h-5 w-5"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div id="popup"
                            class="hidden popup origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black 
                                ring-opacity-5  focus:outline-none
                                      dark:border-gray-700
                                            dark:bg-gray-900 dark:ring-gray-700 dark:divide-gray-700
                                ">
                            @forelse ($roles as $rol)
                                <div class="py-1">
                                    <a href="{{ route('setrole', $rol->id) }}"
                                        class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 hover:text-gray-900 dark:hover:bg-gray-800 dark:text-gray-200 dark:focus-visible:bg-gray-800 dark:hover:bg-white/5 dark:focus-visible:bg-white/10 dark:border-gray-700 hover:text-gray-500">
                                       
                                        Cambiar a {{ $rol->name }}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-5 w-5 text-gray-400"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                            <path
                                                d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                        </svg>
                                    </a>
                                </div>
                            @empty
                            @endforelse

                        </div>
                    </div>
                </div>
            </div>        
        </div>
        <div style="  flex-grow: 1;" class="overflow-auto scrollbar-hidden pb-4"  wire:scroll>
            {{ $slot }}
        </div>    

        <div>
            <div class=" ">
                <div
                    class="bg-white rounded-lg p-2 border border-gray-300 dark:bg-white/5 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-full bg-blue-700 w-10 h-10 p-2 text-gray-100">{{ Auth::user()->getInitials() }}</div>
                            <div>
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200
                                    ">
                                    {{ Auth::user()->name }}
                                </p>
                                <p class="text-xs text-gray-500">

                                    {{ Auth::user()->email }}
                                </p>
                            </div>
                        </div>
                        <div class="relative inline-block text-left">
                            <div id="popup"
                                class="hidden popup 
                                    origin-bottom-right absolute 
                                    bottom-full 
                                    right-0 
                                    mb-2 
                                    w-56 
                                    rounded-md s
                                    hadow-lg 
                                    bg-white 
                                    border border-gray-300
                                
                                         focus:outline-none
                                         dark:border-gray-700
                                        dark:bg-gray-900 dark:ring-gray-700 dark:divide-gray-700">
                                <div class="">
                                    <a href="{{ route('mi_perfil') }}"
                                        class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 hover:text-gray-900 dark:hover:bg-gray-800 dark:text-gray-200 dark:focus-visible:bg-gray-800 dark:hover:bg-white/5 dark:focus-visible:bg-white/10 dark:border-gray-700 hover:text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:bg-white/5"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Ver perfil
                                    </a>
                                </div>

                                <div class="py-1">
                                    <a href="{{ route('logout') }}"
                                        class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 hover:text-gray-900 dark:hover:bg-gray-800 dark:text-gray-200 dark:focus-visible:bg-gray-800 dark:hover:bg-white/5 dark:focus-visible:bg-white/10 dark:border-gray-700 hover:text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Cerrar sesión
                                    </a>
                                </div>
                            </div>
                            <button id="toggleButton"
                                class="toggle-button inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 
                                    bg-white 
                                    hover:bg-gray-50 focus:outline-none focus:ring-2 
                                    focus:ring-offset-2 
                                    focus:ring-indigo-500 
                                    dark:bg-gray-800 
                                    dark:text-gray-200 
                                    dark:border-gray-700 
                                    dark:hover:bg-white/5 
                                    dark:focus:ring-indigo-500 
                                    dark:focus-visible:bg-white/10
                                    dark:focus-visible:text-gray-200 
                                    dark:focus-visible:border-gray-700">
                                <svg id="chevronIcon" xmlns="http://www.w3.org/2000/svg" class=" chevron-icon h-5 w-5"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
