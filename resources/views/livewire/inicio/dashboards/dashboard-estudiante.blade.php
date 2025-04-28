
    <div class="w-full mb-6 lg:mb-0">
        <!-- Sección de Bienvenida -->
        <div class="p-6 bg-yellow-500 dark:bg-yellow-500 rounded-xl">
            <div class="container px-4 mx-auto">
                <h3 class="text-3xl font-bold text-white">Bienvenido a tu Dashboard</h3>
                <p class="font-medium text-blue-200">Aquí podrás ver los proyectos en los que participas y descargar tus constancias.</p>
            </div>
        </div>
        
        <!-- Tabla con los proyectos -->
        <div class="mt-6">
        <div>
    {{ $this->table }}
        </div>
        </div>
    </div>
