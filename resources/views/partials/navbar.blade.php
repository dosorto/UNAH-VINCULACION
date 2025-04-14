
<nav class="fixed top-0 left-0 right-0 z-40 bg-white/30 dark:bg-black/30 backdrop-blur-md dark:border-b dark:border-none">

      
    <div class="md:max-w-5xl bg-white max-w-screen-xl mx-auto px-4 border border-gray-300 dark:border-gray-800 rounded-xl dark:bg-gray-900">
        <div class="flex items-center justify-between h-16 my-2">
            <div class="flex items-center gap-8 md:gap-16">
                <a href="{{ route('home') }}" class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                        <div class="w-3 h-3 rounded-full bg-white"></div>
                    </div>
                    <span class="ml-2 text-black dark:text-white font-bold">UNAH-VINCULACIÓN</span>
                </a>
                <!-- Desktop Links -->
                <div class="hidden md:flex items-center space-x-8">
                  
                    <a href="{{route('verificacion_constancia')}}" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">Validar Constancia</a>
                    <a href="{{route('login')}}" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">Iniciar Sesion</a>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">
                    <x-heroicon-m-moon class="w-5 h-5 dark:hidden"/>
                    <x-heroicon-m-sun class="w-5 h-5 hidden dark:block"/>
                </button>

             

                <!-- Hamburger button -->
                <button class="md:hidden text-black dark:text-white focus:outline-none" id="mobile-menu-button">
                    <x-heroicon-m-bars-3 class="w-6 h-6"/>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden pb-4">
            <div class="flex flex-col space-y-4">
                <a href="{{route('verificacion_constancia')}}" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">Validar Constancia</a>
                <a href="{{route('login')}}" class="text-black dark:text-white hover:text-blue-600 dark:hover:text-yellow-400">Iniciar Sesion</a>
            </div>
        </div>
    </div>
</nav>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton?.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    });
</script>

@endsection