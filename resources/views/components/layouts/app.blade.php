<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <title>{{ config('app.name') }}</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @filamentStyles
    @vite('resources/css/app.css')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <title>Admin Panel</title>
    <style>
        .active {
            width: 100% !important;
            margin-left: 0px !important;
        }
    </style>

    <script>
        // recupera el modo desde localStorage cuando la pagina carga la primera vez
        if (localStorage.theme === 'dark' || (!'theme' in localStorage && window.matchMedia('(prefers-color-scheme: dark)')
                .matches)) {
            document.querySelector('html').classList.add('dark')
        } else if (localStorage.theme === 'dark') {
            document.querySelector('html').classList.add('dark')
        }

        // Evento click de los botones.
        // Agreag la clase 'dark' al elemento html
        // guarda o elimina el modo del localstorage 
        document.querySelectorAll(".setMode").forEach(item =>
            item.addEventListener("click", () => {
                let htmlClasses = document.querySelector('html').classList;
                if (localStorage.theme == 'dark') {
                    htmlClasses.remove('dark');
                    localStorage.theme = ''
                } else {
                    htmlClasses.add('dark');
                    localStorage.theme = 'dark';
                }
            })
        )
    </script>
</head>


<body class="text-gray-800 font-inter fi-body fi-panel-admin min-h-screen bg-gray-50 font-normal text-gray-950 antialiased dark:bg-gray-950 dark:text-white">
    <x-panel.navbar.navbar />

    <main class="dark:bg-gray-800 w-full md:w-[calc(100%-256px)] md:ml-64 bg-gray-200 min-h-screen transition-all main ">
        <!-- navbar -->
        <div class="py-2 px-6 bg-[#f8f4f3] h-[7vh] flex items-center shadow-md shadow-black/5 sticky top-0 left-0 z-30 dark:bg-gray-900"
        >
            <button type="button" class="text-lg text-gray-900 font-semibold sidebar-toggle">
                <i class="ri-menu-line"></i>
            </button>

            <ul class="ml-auto flex items-center">
                
                <div x-data="{
                    theme: 'system',
                    isDarkMode: window.matchMedia('(prefers-color-scheme: dark)'),
                    selected: 'system',
                    init() {
                        document.documentElement.setAttribute(
                            'data-theme',
                            this.updateTheme(),
                        )
                
                        new MutationObserver(([{ oldValue }]) => {
                            let newValue =
                                document.documentElement.getAttribute('data-theme')
                            if (newValue !== oldValue) {
                                try {
                                    window.localStorage.setItem('theme', newValue)
                                } catch {}
                                this.updateThemeWithoutTransitions(newValue)
                            }
                        }).observe(document.documentElement, {
                            attributeFilter: ['data-theme'],
                            attributeOldValue: true,
                        })
                
                        this.isDarkMode.addEventListener('change', () =>
                            this.updateThemeWithoutTransitions(),
                        )
                    },
                    updateTheme(theme) {
                        this.theme = theme ?? window.localStorage.theme ?? 'system'
                
                        if (
                            this.theme === 'dark' ||
                            (this.theme === 'system' &amp;&amp; this.isDarkMode.matches)
                        ) {
                            document.documentElement.classList.add('dark')
                        } else if (
                            this.theme === 'light' ||
                            (this.theme === 'system' &amp;&amp; !this.isDarkMode.matches)
                        ) {
                            document.documentElement.classList.remove('dark')
                        }
                
                        this.selected = this.theme
                        window.localStorage.theme = this.theme
                        document.documentElement.setAttribute('data-theme', this.theme)
                        return this.theme
                    },
                    updateThemeWithoutTransitions(theme) {
                        this.updateTheme(theme)
                        document.documentElement.classList.add('[&amp;_*]:!transition-none')
                        window.setTimeout(() => {
                            document.documentElement.classList.remove(
                                '[&amp;_*]:!transition-none',
                            )
                        }, 0)
                    },
                }">
                  <p class="sr-only">Theme</p>
                  <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full ring-1 ring-black/5 dark:bg-dolphin/10 dark:ring-inset dark:ring-white/5" aria-label="Open navigation" x-on:click="$refs.panel.toggle" aria-expanded="false" aria-controls="panel-WQLRr6P7">
                    <svg fill="currentColor" aria-hidden="true" data-slot="icon" viewBox="0 0 20 20" class="hidden h-6 w-6 fill-butter [[data-theme=light]_&amp;]:block" astro-icon="heroicons:sun"><path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2Zm0 13a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15Zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.657-1.596a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06Zm-9.193 9.192a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10Zm9.596 5.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.061 1.06l1.06 1.06Z"></path></svg>
                    <svg fill="currentColor" aria-hidden="true" data-slot="icon" viewBox="0 0 20 20" class="hidden h-6 w-6 fill-butter [[data-theme=dark]_&amp;]:block" astro-icon="heroicons:moon"><path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd"></path></svg>
                    <svg fill="currentColor" aria-hidden="true" data-slot="icon" viewBox="0 0 20 20" class="hidden h-6 w-6 fill-hurricane [:not(.dark)[data-theme=system]_&amp;]:block" astro-icon="heroicons:sun"><path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2Zm0 13a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15Zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.657-1.596a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06Zm-9.193 9.192a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10Zm9.596 5.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.061 1.06l1.06 1.06Z"></path></svg>
                    <svg fill="currentColor" aria-hidden="true" data-slot="icon" viewBox="0 0 20 20" class="hidden h-6 w-6 fill-hurricane [.dark[data-theme=system]_&amp;]:block" astro-icon="heroicons:moon"><path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd"></path></svg>
                  </button>
                  <div x-ref="panel" class="hidden absolute w-36 space-y-1 rounded-lg bg-white p-3 text-sm shadow-md shadow-black/5 ring-1 ring-black/5 dark:bg-[#1f1c36] dark:ring-white/5" x-float.shift.offset.trap="{shift: {padding: 12}}" aria-label="Theme options" id="panel-WQLRr6P7" aria-modal="true" role="dialog" style="display: none;">
                      <button x-bind:key="light" x-on:click="updateTheme('light'); $refs.panel.close();" class="w-full flex cursor-pointer select-none items-center rounded-[0.5rem] p-1 hover:bg-gray-100 hover:dark:bg-gray-900/40 transition duration-200 text-gray-700 dark:text-gray-300 hover:dark:text-white" x-bind:class="{
                              'text-butter hover:text-butter hover:dark:text-butter': selected === 'light',
                              'text-gray-700 dark:text-gray-300 hover:dark:text-white': selected !== 'light',
                            }">
                          <div class="rounded-md bg-white p-1.5 ring-1 ring-gray-900/5 dark:bg-dolphin/20 dark:ring-inset dark:ring-white/5 transition duration-200">
                            <svg fill="currentColor" aria-hidden="true" data-slot="icon" viewBox="0 0 20 20" x-bind:class="selected == 'light' ? 'fill-butter dark:fill-butter' : 'fill-hurricane'" class="h-4 w-4 fill-hurricane" astro-icon="heroicons:sun"><path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2Zm0 13a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15Zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.657-1.596a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06Zm-9.193 9.192a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10Zm9.596 5.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.061 1.06l1.06 1.06Z"></path></svg>
                          </div>
                          <div class="ml-3">Claro</div>
                        </button><button x-bind:key="dark" x-on:click="updateTheme('dark'); $refs.panel.close();" class="w-full flex cursor-pointer select-none items-center rounded-[0.5rem] p-1 hover:bg-gray-100 hover:dark:bg-gray-900/40 transition duration-200 text-butter hover:text-butter hover:dark:text-butter" x-bind:class="{
                              'text-butter hover:text-butter hover:dark:text-butter': selected === 'dark',
                              'text-gray-700 dark:text-gray-300 hover:dark:text-white': selected !== 'dark',
                            }">
                          <div class="rounded-md bg-white p-1.5 ring-1 ring-gray-900/5 dark:bg-dolphin/20 dark:ring-inset dark:ring-white/5 transition duration-200">
                            <svg fill="currentColor" aria-hidden="true" data-slot="icon" viewBox="0 0 20 20" x-bind:class="selected == 'dark' ? 'fill-butter dark:fill-butter' : 'fill-hurricane'" class="h-4 w-4 fill-butter dark:fill-butter" astro-icon="heroicons:moon"><path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd"></path></svg>
                          </div>
                          <div class="ml-3">Oscuro</div>
                        </button><button x-bind:key="system" x-on:click="updateTheme('system'); $refs.panel.close();" class="w-full flex cursor-pointer select-none items-center rounded-[0.5rem] p-1 hover:bg-gray-100 hover:dark:bg-gray-900/40 transition duration-200 text-gray-700 dark:text-gray-300 hover:dark:text-white" x-bind:class="{
                              'text-butter hover:text-butter hover:dark:text-butter': selected === 'system',
                              'text-gray-700 dark:text-gray-300 hover:dark:text-white': selected !== 'system',
                            }">
                          <div class="rounded-md bg-white p-1.5 ring-1 ring-gray-900/5 dark:bg-dolphin/20 dark:ring-inset dark:ring-white/5 transition duration-200">
                            <svg fill="currentColor" aria-hidden="true" data-slot="icon" viewBox="0 0 20 20" x-bind:class="selected == 'system' ? 'fill-butter dark:fill-butter' : 'fill-hurricane'" class="h-4 w-4 fill-hurricane" astro-icon="heroicons:computer-desktop"><path fill-rule="evenodd" d="M2 4.25A2.25 2.25 0 0 1 4.25 2h11.5A2.25 2.25 0 0 1 18 4.25v8.5A2.25 2.25 0 0 1 15.75 15h-3.105a3.501 3.501 0 0 0 1.1 1.677A.75.75 0 0 1 13.26 18H6.74a.75.75 0 0 1-.484-1.323A3.501 3.501 0 0 0 7.355 15H4.25A2.25 2.25 0 0 1 2 12.75v-8.5Zm1.5 0a.75.75 0 0 1 .75-.75h11.5a.75.75 0 0 1 .75.75v7.5a.75.75 0 0 1-.75.75H4.25a.75.75 0 0 1-.75-.75v-7.5Z" clip-rule="evenodd"></path></svg>
                          </div>
                          <div class="ml-3">Sistema</div>
                        </button>
                  </div>
                </div>

                <button id="fullscreen-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        class="hover:bg-gray-100 rounded-full" viewBox="0 0 24 24"
                        style="fill: gray;transform: ;msFilter:;">
                        <path d="M5 5h5V3H3v7h2zm5 14H5v-5H3v7h7zm11-5h-2v5h-5v2h7zm-2-4h2V3h-7v2h5z"></path>
                    </svg>
                </button>
                <script>
                    const fullscreenButton = document.getElementById('fullscreen-button');

                    fullscreenButton.addEventListener('click', toggleFullscreen);

                    function toggleFullscreen() {
                        if (document.fullscreenElement) {
                            // If already in fullscreen, exit fullscreen
                            document.exitFullscreen();
                        } else {
                            // If not in fullscreen, request fullscreen
                            document.documentElement.requestFullscreen();
                        }
                    }
                </script>

                <li class="dropdown ml-3">
                    <button type="button" class="dropdown-toggle flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 relative">
                            <div class="p-1 bg-white rounded-full focus:outline-none focus:ring">
                                <img class="w-8 h-8 rounded-full"
                                    src="https://laravelui.spruko.com/tailwind/ynex/build/assets/images/faces/9.jpg"
                                    alt="">
                                <div
                                    class="top-0 left-7 absolute w-3 h-3 bg-lime-400 border-2 border-white rounded-full animate-ping">
                                </div>
                                <div
                                    class="top-0 left-7 absolute w-3 h-3 bg-lime-500 border-2 border-white rounded-full">
                                </div>
                            </div>
                        </div>
                        <div class="p-2 md:block text-left">
                            <h2 class="text-sm font-semibold text-gray-800">John Doe</h2>
                            <p class="text-xs text-gray-500">Administrator</p>
                        </div>
                    </button>
                    <ul class="dropdown-menu shadow-md shadow-black/5 z-30 py-1.5 rounded-md bg-white border border-gray-100 w-full max-w-[140px] hidden"
                        data-popper-id="popper-2"
                        style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate(-24px, 68px);"
                        data-popper-placement="bottom-end">
                        <li>
                            <a href="#"
                                class="flex items-center text-[13px] py-1.5 px-4 text-gray-600 hover:text-[#f84525] hover:bg-gray-50">Profile</a>
                        </li>
                        <li>
                            <a href="#"
                                class="flex items-center text-[13px] py-1.5 px-4 text-gray-600 hover:text-[#f84525] hover:bg-gray-50">Settings</a>
                        </li>
                        <li>
                            <form method="POST" action="">
                                <a role="menuitem"
                                    class="flex items-center text-[13px] py-1.5 px-4 text-gray-600 hover:text-[#f84525] hover:bg-gray-50 cursor-pointer"
                                    onclick="event.preventDefault();
                    this.closest('form').submit();">
                                    Log Out
                                </a>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <!-- end navbar -->

        <!-- Content -->
        <div class="p-6 w-full flex justify-center ">
            <main class="w-full w-3/4 flex flex-col ">
                {{ $slot }}
            </main>

        </div>
        <!-- End Content -->
    </main>

    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="{{ asset('js/app/panelScripts.js') }}"></script>
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')

</body>


</html>
