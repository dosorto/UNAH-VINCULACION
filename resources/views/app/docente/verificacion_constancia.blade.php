<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constancia de Verificación</title>

    <!-- Estilos de Tailwind CSS -->
     @vite('resources/css/app.css')
   

</head>
<body class="  p-8">

    <!-- Contenedor principal de la constancia -->
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg border-2 border-gray-300">

        <!-- Header con el logo -->
        <header class="flex justify-center items-center mb-6">
            <img src="http://127.0.0.1:8000/images/Image/Imagen2.png" alt="UNAH Logo" class="max-w-full h-auto md:w-[65%]">
        </header>

        <!-- Título -->
        <h1 class="text-center text-3xl font-semibold text-gray-800 mb-4">Constancia de Verificación</h1>

        <!-- Mensaje de verificación -->
        <p class="text-center text-lg font-medium text-gray-700 mb-6">Se certifica que el siguiente empleado ha participado en el proyecto correspondiente.</p>

        <!-- Información de la constancia -->
        <div class="space-y-4 text-lg text-gray-700">

            <p><strong>Nombre del Proyecto:</strong> {{ $data['nombre_proyecto'] }}</p>
            <p><strong>Nombre del Empleado:</strong> {{ $data['nombre_empleado'] }}</p>
            <p><strong>Número de Empleado:</strong> {{ $data['numero_empleado'] }}</p>
            <p><strong>Fecha de Inicio:</strong> {{ \Carbon\Carbon::parse($data['fecha_inicio'])->format('d/m/Y') }}</p>
            <p><strong>Fecha de Fin:</strong> {{ \Carbon\Carbon::parse($data['fecha_fin'])->format('d/m/Y') }}</p>
            <p><strong>Horas Trabajadas:</strong> {{ $data['horas'] }} horas</p>
            <p><strong>Número de Dictamen:</strong> {{ $data['numero_dictamen'] }}</p>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center">
            <p class="text-gray-700 text-sm">Firma y Sello de la Institución</p>
        </div>

    </div>

</body>
</html>
