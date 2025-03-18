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
    <div class="max-w-3xl w-full bg-white p-8 border border-green-500 shadow-lg shadow-green-200 rounded-md">
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

        <!-- Icono de Check -->
        <div class="flex items-center justify-center mb-4 space-x-2">
            <x-heroicon-o-check-circle class="w-12 h-12 text-green-500" />
            <h3 class="text-lg font-bold text-green-500">
                Verificación Exitosa
            </h3>
        </div>

        <!-- Title -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold mb-1">CONSTANCIA DE ENTREGA DE INFORME FINAL DEL</h1>
            <h1 class="text-2xl font-bold">PROYECTO DE VINCULACIÓN</h1>
        </div>

        <!-- Content -->
        <div class="leading-relaxed mb-10">
            <div class="text-justify mb-8 leading-relaxed">
                <p>La Suscrita Directora de la Dirección de Vinculación Universidad-Sociedad por este medio hace constar
                    que el Profesor <span class="font-bold">  {{ $data['nombre_empleado'] }}</span> con número de empleado
                    <span class="font-bold"> #{{ $data['numero_empleado'] }}</span> perteneciente al <span class="font-bold">CENTRO UNIVERSITARIO
                        REGIONAL LITORAL PACÍFICO (CURLP)</span>, ha presentado el informe de finalización del proyecto
                    de Vinculación denominado: <span class="font-bold">"FORTALECIMIENTO DE LAS CAPACIDADES TECNOLÓGICAS
                        EN INSTALACIONES Y PERSONAL DOCENTE DEL INSTITUTO BTP MARÍA R. DONATE, UBICADO EN LA ALDEA DE
                        CERRO VERDE DE LINACA - CHOLUTECA"</span> mismo que se ejecutó desde el 1 febrero de 2022 hasta
                    el 30 noviembre de 2022.</p>
            </div>

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
