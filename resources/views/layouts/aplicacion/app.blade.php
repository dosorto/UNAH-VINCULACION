<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UNAH - Vinculación')</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 text-gray-900">
    @include('components.aplicacion.navbar')

    <main class="p-6">
        @yield('content')
    </main>
</body>
</html>
