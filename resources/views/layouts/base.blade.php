<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <script>
        // Evitar parpadeo al detectar y aplicar modo oscuro
        if (
            localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
        ) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        // Verificar preferencia guardada
        if (localStorage.getItem('theme') === 'dark' || 
           (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
        }

        // Alternar tema
        themeToggle.addEventListener('click', () => {
            htmlElement.classList.toggle('dark');
            // Guardar preferencia
            if (htmlElement.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });

        // Menú móvil (ya lo tenías, lo dejo aquí junto)
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuButton.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    });
</script>

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('images/Image/logo_nexo.png') }}" type="image/png">

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
    @yield('styles')
</head>
<body class="min-h-screen bg-white dark:bg-black overflow-x-hidden">
    <!-- Navigation -->
    @include('partials.navbar')

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('partials.footer')

    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
    
</body>

<script>
  (function() {
    const devtools = {
      isOpen: false,
      orientation: null
    };

    const threshold = 160;

    const emitEvent = (isOpen, orientation) => {
      window.dispatchEvent(new CustomEvent('devtoolschange', {
        detail: {
          isOpen,
          orientation
        }
      }));
    };

    setInterval(() => {
      const widthThreshold = window.outerWidth - window.innerWidth > threshold;
      const heightThreshold = window.outerHeight - window.innerHeight > threshold;
      const orientation = widthThreshold ? 'vertical' : 'horizontal';

      if (!(heightThreshold && widthThreshold) &&
          ((window.Firebug && window.Firebug.chrome && window.Firebug.chrome.isInitialized) ||
           widthThreshold || heightThreshold)) {
        if (!devtools.isOpen || devtools.orientation !== orientation) {
          emitEvent(true, orientation);
        }
        devtools.isOpen = true;
        devtools.orientation = orientation;
      } else {
        if (devtools.isOpen) {
          emitEvent(false, null);
        }
        devtools.isOpen = false;
        devtools.orientation = null;
      }
    }, 500);

    window.addEventListener('devtoolschange', event => {
      if (event.detail.isOpen) {
        alert("¡DevTools detectado! Cerralo, por favor.");
        window.location.href = "https://google.com";
      }
    });
  })();
</script>

@yield('scripts')
</html>


