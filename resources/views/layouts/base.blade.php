<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNAH-VINCULACIÓN - @yield('title', 'Inicio')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            400: '#3b82f6', // blue-400
                            600: '#2563eb', // blue-600
                            700: '#1d4ed8', // blue-700
                            900: '#1e3a8a', // blue-900
                        },
                        secondary: {
                            300: '#fcd34d', // yellow-300
                            400: '#fbbf24', // yellow-400
                            500: '#f59e0b', // yellow-500
                        }
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .text-shadow {
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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

    <!-- Scripts -->
    <script>
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        
        // Check for saved theme preference or use system preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
        
        // Toggle theme on button click
        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            
            // Save preference to localStorage
            if (html.classList.contains('dark')) {
                localStorage.theme = 'dark';
            } else {
                localStorage.theme = 'light';
            }
        });
    </script>
    @yield('scripts')
</body>
</html>