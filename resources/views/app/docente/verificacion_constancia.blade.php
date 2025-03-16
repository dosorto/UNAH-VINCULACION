<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constancia de Verificación</title>

    <!-- Estilos de Tailwind CSS -->
    @vite('resources/css/app.css')


</head>

<body class="flex justify-center items-center min-h-screen p-4 bg-gray-100 dark:bg-gray-950">
    <div class="max-w-3xl w-full bg-white p-8 border-1 border-gray-100 shadow-md" 
    style="border: 1px solid #e2e8f0; border-radius: 0.375rem;">
        <!-- Header -->
        <div class="flex justify-between items-center mb-12 flex-wrap sm:flex-nowrap">
            <div class="flex items-center mb-4 sm:mb-0">
                <img class="h-20 mr-3" src="{{ asset('images/imagenvinculacion.jpg') }}" alt="Logo UNAH">
            </div>
            <div class="text-right text-blue-900 sm:text-left">
                <p>vinculacion.sociedad@unah.edu.hn</p>
                <p>Tel. 2216-7070 Ext. 110576</p>
            </div>
        </div>
        

        <!-- Title -->
        <h1 class="text-center text-2xl font-bold my-10">
            Validación de Constancia de Participación en Proyecto
        </h1>

        <!-- Content -->
        <div class="leading-relaxed mb-10">
            <p class="mb-4">El Area Dirección Vinculación Universidad Sociedad, hace constar que:</p>

            <p class="mb-6"><span class="font-bold">{{ $data['nombre_empleado'] }}</span> con número de empleado:
                <strong>{{ $data['numero_empleado'] }}</strong> participó en el proyecto según los siguientes detalles:
            </p>

            <div class="my-8 overflow-hidden rounded-lg ">
                <table class="w-full border-collapse bg-white">
                    <tbody>
                        <tr>
                            <td class="w-1/2 px-4 py-2 font-medium text-gray-700 bg-gray-50 border-b border-gray-200">
                                Nombre del Proyecto
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200">
                                {{ $data['nombre_proyecto'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/4 px-4 py-2 font-medium text-gray-700 bg-gray-50 border-b border-gray-200">
                                Nombre del Empleado
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200">
                                {{ $data['nombre_empleado'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/4 px-4 py-2 font-medium text-gray-700 bg-gray-50 border-b border-gray-200">
                                Número de Empleado
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200">
                                {{ $data['numero_empleado'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/4 px-4 py-2 font-medium text-gray-700 bg-gray-50 border-b border-gray-200">
                                Fecha de Inicio
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200">
                                {{ \Carbon\Carbon::parse($data['fecha_inicio'])->format('d/m/Y') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/4 px-4 py-2 font-medium text-gray-700 bg-gray-50 border-b border-gray-200">
                                Fecha de Fin
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200">
                                {{ \Carbon\Carbon::parse($data['fecha_fin'])->format('d/m/Y') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/4 px-4 py-2 font-medium text-gray-700 bg-gray-50 border-b border-gray-200">
                                Horas Trabajadas
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200">
                                {{ $data['horas'] }} horas
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/4 px-4 py-2 font-medium text-gray-700 bg-gray-50">
                                Número de Dictamen
                            </td>
                            <td class="px-4 py-2">
                                {{ $data['numero_dictamen'] }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            

            <p>Y para los fines que al interesado(a) convenga, se le Valida la presente Constancia en la Ciudad
                Universitaria, José Trinidad Reyes, a los {{ now()->day }} días del mes de {{ now()->monthName }}
                del año {{ now()->year }}</p>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm mt-16 text-blue-900">
            <p>Universidad Nacional Autónoma de Honduras | CIUDAD UNIVERSITARIA | Tegucigalpa, M.C.D Honduras C.A |
                www.unah.edu.hn</p>
        </div>
    </div>
</body>

</html>
