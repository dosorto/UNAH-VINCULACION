<div class="flex h-[20px] w-full">
    <div class="w-[33.3333%]" style="background-color: #235383;"></div>
    <div class="w-[33.3333%]" style="background-color: #FFFFFF;"></div>
    <div class="w-[33.3333%] bg-yellow-400" ></div>
  </div>
  
<footer class="bg-[#235383] text-white border-t border-[#1d4769] dark:bg-gray-800 dark:border-gray-700 dark:text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <div>
                <h3 class="text-xl font-bold text-yellow-400 mb-4">UNAH-VINCULACIÓN</h3>
                <p class="text-white text-opacity-90 dark:text-opacity-80 mb-6">
                    La vinculación académica con la sociedad, junto a la investigación científica y la docencia son funciones esenciales de una universidad. Los vínculos académicos que la Universidad establece con el Estado, los sectores productivos y la sociedad civil posibilitan que los conocimientos científicos, técnicos y humanistas de la Universidad sean útiles para orientar y resolver problemas en la nación.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-lg font-semibold text-yellow-400 mb-4">Contacto</h4>
                    <ul class="space-y-3 text-white text-opacity-90 dark:text-opacity-80">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-white mt-1 mr-3"></i>
                            <span>Ciudad Universitaria, Edificio Alma Máter, 5to piso.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone text-white mt-1 mr-3"></i>
                            <span>(504) 2216-7070 Ext. 110576</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-globe text-white mt-1 mr-3"></i>
                            <a href="https://vinculacion.unah.edu.hn" class="hover:underline">vinculacion.unah.edu.hn</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope text-white mt-1 mr-3"></i>
                            <a href="mailto:vinculacion.sociedad@unah.edu.hn" class="hover:underline">vinculacion.sociedad@unah.edu.hn</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-yellow-400 mb-4">Enlaces rápidos</h4>
                    <ul class="space-y-2 text-white text-opacity-90 dark:text-opacity-80">
                        <li><a href="{{ route('home') }}" class="hover:underline">Inicio</a></li>
                        <li><a href="{{ route('home') }}#mision" class="hover:underline">Misión y Visión</a></li>
                        <li><a href="{{ route('home') }}#desarrolladores" class="hover:underline">Desarrolladores</a></li>
                        <li><a href="{{ route('home') }}#contacto" class="hover:underline">Contacto</a></li>
                        <li><a href="{{ route('verificacion_constancia') }}" class="hover:underline">Validar Constancia</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t border-[#1d4769] dark:border-[#142135] pt-8 flex flex-col md:flex-row justify-between items-center">
            <p class="text-white text-opacity-70 dark:text-opacity-60 mb-4 md:mb-0">© {{ date('Y') }} UNAH-VINCULACIÓN. Todos los derechos reservados.</p>
            <div class="flex space-x-4">
                <a href="#" class="text-white text-opacity-70 dark:text-opacity-60 hover:text-white"><i class="fab fa-facebook text-xl"></i></a>
                <a href="#" class="text-white text-opacity-70 dark:text-opacity-60 hover:text-white"><i class="fab fa-twitter text-xl"></i></a>
                <a href="#" class="text-white text-opacity-70 dark:text-opacity-60 hover:text-white"><i class="fab fa-instagram text-xl"></i></a>
                <a href="#" class="text-white text-opacity-70 dark:text-opacity-60 hover:text-white"><i class="fab fa-youtube text-xl"></i></a>
            </div>
        </div>
    </div>
</footer>
