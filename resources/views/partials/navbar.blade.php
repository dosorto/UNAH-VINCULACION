<nav class="fixed top-0 left-0 right-0 z-50 bg-white/30 dark:bg-black/30 backdrop-blur-md dark:border-b dark:border-gray-700 ">
    <div class="max-w-screen-xl mx-auto px-4">
        <div class="flex items-center justify-between h-16 my-2">
            <div class="flex items-center gap-8 md:gap-16">
                <a href="{{ route('home') }}" class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                        <div class="w-3 h-3 rounded-full bg-white"></div>
                    </div>
                    <span class="ml-2 text-black dark:text-white font-bold">UNAH-VINCULACIÓN</span>
                </a>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{route('login')}}" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">Login</a>
                    <a href="{{route('verificacion_constancia')}}" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">Validar Constancias</a>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">
                    <x-heroicon-m-moon class="w-5 h-5 dark:hidden"/>
                    <x-heroicon-m-sun class="w-5 h-5 hidden dark:block"/>
                </button>
                <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl">
                    Iniciar Sesión
                </a>
                <button class="md:hidden text-black dark:text-white" id="mobile-menu-button">
                    <x-heroicon-m-bars-3 class="w-6 h-6"/>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden pb-4">
            <div class="flex flex-col space-y-4">
                <a href="{{route('login')}}" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">Login</a>
                <a href="{{route('verificacion_constancia')}}" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">Validar Constancia</a>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuButton.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    });
</script>
