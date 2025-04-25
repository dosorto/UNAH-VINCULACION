<!-- resources/views/components/navbar.blade.php -->
<nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <a href="{{ url('/') }}" class="text-2xl font-bold text-blue-600">UNAH - Vinculación</a>
        <div class="space-x-4">
            <a href="{{ url('/') }}" class="text-gray-700 hover:text-blue-600">Inicio</a>
            <a href="{{ route('validar') }}" class="text-gray-700 hover:text-blue-600">Validar Constancias</a>
            <a href="#acerca" class="text-gray-700 hover:text-blue-600">Acerca de</a>
            <a href="#desarrolladores" class="text-gray-700 hover:text-blue-600">Desarrolladores</a>
            <a href="{{ route('login') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Iniciar Sesión</a>
        </div>
    </div>
</nav>
