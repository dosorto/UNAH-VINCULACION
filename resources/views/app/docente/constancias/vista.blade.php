<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Código</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="flex w-full max-w-5xl h-[400px] relative">
  
      <!-- Rectángulo blanco -->
      <div class="flex-1 bg-white rounded-l-2xl shadow-lg"></div>
  
      <!-- Rectángulo con degradado azul -->
      <div class="flex-1 bg-gradient-to-r from-blue-500 to-blue-700 rounded-r-2xl shadow-lg"></div>
  
      <!-- Formulario en el centro -->
      <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white shadow-xl rounded-xl p-8 w-[90%] max-w-md z-10">
        <h2 class="text-xl font-semibold text-center text-gray-800 mb-4">Ingresa tu código</h2>
        <form>
          <input type="text" placeholder="Escribe aquí..." class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition" />
          <button type="submit" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition">Buscar</button>
        </form>
      </div>
  
    </div>
</body>

</html>
