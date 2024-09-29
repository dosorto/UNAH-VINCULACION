<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @filamentStyles
    @vite('resources/css/app.css')

    <style>
        .contenedor {
            width: 100%;
            height: auto;
            min-height: 100vh;
            background-color: red;
        }

        

        .space {
            --size: 2px;
            --space-layer:
                4vw 50vh 0 #fff,
                18vw 88vh 0 #fff,
                73vw 14vh 0 #fff;

            width: var(--size);
            height: var(--size);
            background: white;
            box-shadow: var(--space-layer);
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0.75;
        }

        .space {
            animation: move var(--duration) linear infinite;
        }

        @keyframes move {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-100vh);
            }
        }
    </style>

</head>

<body>
    <div class="contenedor bg-gradient-to-r from-blue-800 to-indigo-900"">
            <div class=" space space-1"></div>
    <div class="space space-2"></div>
    <div class="space space-3"></div>
    <div class="w-full h-full flex flex-col items-center">
        <div class="w-full h-[10vh]">
            <header class=" w-full flex items-center justify-between px-8 py-02">
                <!-- logo -->
                <h1 class="w-3/12">
                    <a href>
                        <h2 class="text-2xl font-bold text-white">Arti140

                        </h2>
                    </a>
                </h1>

                <!-- navigation -->
                <nav class="nav font-semibold text-lg">
                    <ul class="flex items-center">
                        <li
                            class="p-4 text-yellow-400 border-b-2 border-green-500 border-opacity-0 hover:border-opacity-100 hover:text-white duration-200 cursor-pointer ">
                            <a href>Admin</a>
                        </li>
                        <li
                            class="p-4 text-yellow-400 border-b-2 border-green-500 border-opacity-0 hover:border-opacity-100 hover:text-white duration-200 cursor-pointer active">
                            <a href>Registrarme</a>
                        </li>

                    </ul>
                </nav>

                <!-- buttons --->

            </header>
        </div>
        <div class="w-full  min-h-[80vh] flex items-center justify-center ">
            <div
                class="w-full xl:w-3/4 rounded-full flex flex-col-reverse md:flex-row  items-center justify-center md:space-y-0">
                <div class=" p-4 w-full xl:w-1/2 flex items-center justify-center">
                    <div class=" md:max-w-sm w-full lg:max-w-full lg:flex">

                        <div class=" triangulo mt-1 lg:mt-[100px] p-8
                                   w-4/3 
                                   rounded-lgl border-gray-400  bg-white r p-4 flex flex-col justify-between leading-normal
                                        rounded-xl
                                    ">
                            <div>
                                <p class="text-sm text-gray-600 flex items-center">

                                    Serie: publicaciones de la Reforma
                                    universitaria n°. 6 Mayo, 2015
                                </p>
                                <div class="text-gray-900 font-bold text-xl mb-2">
                                    Normas Academicas UNAH
                                </div>
                                <p class="text-gray-600 text-base">
                                    <b>Articulo 140</b><br>
                                    Para los diferentes grados
                                    académicos los requisitos generales
                                    de graduación son los que se
                                    describen a continuación:
                                    <br>
                                    <b>a)</b> Haber completado los
                                    créditos académicos de la carrera,
                                    con un índice de graduación no
                                    inferior a setenta por ciento (70%).
                                    <br>
                                    <b>b)</b> Haber participado, de
                                    manera obligatoria, en su proceso
                                    educativo en una iniciativa de
                                    índole social, cultural, artística,
                                    deportiva, las cuales deberán ser
                                    certificadas por la VOAE o sus
                                    referentes en los centros regionales
                                    o instituto tecnológico
                                    superior.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 w-full xl:w-1/2 flex items-center justify-center ">
                    <div
                        class="w-full bg-white rounded-lg  dark:border md:mt-0 md:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                        <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                            <h1
                                class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                                Arti140
                            </h1>
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="w-full h-[10vh] flex items-center justify-center">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900
                            flex items-center justify-center text-white
                          ">Arti140</h3>
                    <p class="mt-1 text-sm text-gray-600 text-white">
                        Desarrollado por IS UNAH-CHOLUTECA 2021
                    </p>
                </div>
            </div>
        </div>

    </div>
    </div>
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
</body>
<script>
    const COLORS = ["#fff2", "#fff4", "#fff7", "#fffc"];

    const generateSpaceLayer = (size, selector, totalStars, duration) => {
        const layer = [];
        for (let i = 0; i < totalStars; i++) {
            const color = COLORS[~~(Math.random() * COLORS.length)];
            const x = Math.floor(Math.random() * 100);
            const y = Math.floor(Math.random() * 100);
            layer.push(`${x}vw ${y}vh 0 ${color}, ${x}vw ${y + 100}vh 0 ${color}`);
        }
        const container = document.querySelector(selector);
        container.style.setProperty("--size", size);
        container.style.setProperty("--duration", duration);
        container.style.setProperty("--space-layer", layer.join(","));
    }

    generateSpaceLayer("2px", ".space-1", 250, "25s");
    generateSpaceLayer("3px", ".space-2", 100, "20s");
    generateSpaceLayer("6px", ".space-3", 25, "15s");
</script>

</html>