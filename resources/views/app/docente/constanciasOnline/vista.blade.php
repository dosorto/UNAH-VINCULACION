<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Código</title>
    @vite('resources/css/app.css')
</head>

<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="relative items-center justify-center w-full overflow-x-hidden lg:pt-40 lg:pb-40 xl:pt-40 xl:pb-64">
        <div
            class="container flex flex-col items-center justify-between h-full max-w-6xl px-8 mx-auto -mt-32 lg:flex-row xl:px-0">
            <div
                class="z-30 flex flex-col items-center w-full max-w-xl pt-48 text-center lg:items-start lg:w-1/2 lg:pt-20 xl:pt-40 lg:text-left">
                <h1 class="relative mb-4 text-3xl font-black leading-tight text-gray-900 sm:text-6xl xl:mb-8">
                    Validar Constancias de Proyectos
                </h1>
                <p class="pr-0 mb-8 text-base text-gray-600 sm:text-lg xl:text-xl lg:pr-20">
                    Ingrese el código de la constancia para verificar su autenticidad.
                </p>

                <input type="text" id="codigo" name="codigo"
                class="w-full px-4 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                required>
                <a href="#_"
                    class="relative self-start inline-block w-auto px-8 py-4 mx-auto mt-0 text-base font-bold text-white bg-indigo-600 border-t border-gray-200 rounded-md shadow-xl sm:mt-1 fold-bold lg:mx-0">
                    Validar
                </a>
                <!-- Integrates with section -->
                <div class="flex-col hidden mt-12 sm:flex lg:mt-24">
                    <p class="mb-4 text-sm font-medium tracking-widest text-gray-500 uppercase">UNAH-VINCULACION</p>
                    <div>
                        
                    </div>
                </div>
                <svg class="absolute left-0 max-w-md mt-24 -ml-64 left-svg" viewBox="0 0 423 423"
                    xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs>
                        <linearGradient x1="100%" y1="0%" x2="4.48%" y2="0%"
                            id="linearGradient-1">
                            <stop stop-color="#5C54DB" offset="0%"></stop>
                            <stop stop-color="#6A82E7" offset="100%"></stop>
                        </linearGradient>
                        <filter x="-9.3%" y="-6.7%" width="118.7%" height="118.7%" filterUnits="objectBoundingBox"
                            id="filter-3">
                            <feOffset dy="8" in="SourceAlpha" result="shadowOffsetOuter1"></feOffset>
                            <feGaussianBlur stdDeviation="8" in="shadowOffsetOuter1" result="shadowBlurOuter1">
                            </feGaussianBlur>
                            <feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.1 0" in="shadowBlurOuter1">
                            </feColorMatrix>
                        </filter>
                        <rect id="path-2" x="63" y="504" width="300" height="300" rx="40"></rect>
                    </defs>
                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" opacity=".9">
                        <g id="Desktop-HD" transform="translate(-39 -531)">
                            <g id="Hero" transform="translate(43 83)">
                                <g id="Rectangle-6" transform="rotate(45 213 654)">
                                    <use fill="#000" filter="url(#filter-3)" xlink:href="#path-2"></use>
                                    <use fill="url(#linearGradient-1)" xlink:href="#path-2"></use>
                                </g>
                            </g>
                        </g>
                    </g>
                </svg>
            </div>
            
        </div>
    </div>
</body>

</html>
