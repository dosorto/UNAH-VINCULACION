<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
   
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/Logo_Nexo.png') }}" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js"></script>

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
    @stack('styles')
    <style>
        #fondoimagen::before {
            content: "";
            /* Necesario para que el pseudo-elemento aparezca */
            position: absolute;
            /* Lo posicionamos de manera absoluta */
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            /* Lo hacemos llenar todo el contenedor */
            background-image: url('{{ asset('images/Sol.svg') }}');
            background-size: cover;
            background-position: center;
            opacity: 0.3;
            /* Opacidad del pseudo-elemento */
            z-index: -1;
            /* Aseguramos que quede detrás del contenido */
        }

      
    </style>
</head>
<body  >
    @yield('contenido')   
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
</body>


<script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    darkModeToggle.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
        if (document.documentElement.classList.contains('dark')) {
            localStorage.setItem('color-theme', 'dark');
        } else {
            localStorage.setItem('color-theme', 'light');
        }
    });

    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window
            .matchMedia(
                '(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
</script>


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
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.4.7/flowbite.min.js"></script>

</html>
