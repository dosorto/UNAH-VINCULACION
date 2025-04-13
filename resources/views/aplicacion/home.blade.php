@extends('layouts.aplicacion.app')

@section('title', 'Inicio')

@section('content')
    <!-- Hero Section -->
    <div class="relative pt-32 pb-0 sm:pt-32 ">
        <!-- Enhanced Gradient Background -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 via-white to-yellow-50 dark:from-black dark:via-gray-900 dark:to-black"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div
                class="inline-flex items-center rounded-full px-4 py-1 mb-8 
                bg-blue-100 border border-blue-200 dark:bg-blue-900/50 dark:border-blue-700/50 
                shadow-[0_0_30px_-5px_rgba(37,99,235,0.3)] dark:shadow-[0_0_30px_-5px_rgba(59,130,246,0.5)]"
            >
                <div class="w-2 h-2 rounded-full bg-blue-600 dark:bg-yellow-400 mr-2"></div>
                <span class="text-sm text-blue-700 dark:text-yellow-300">Plataforma de Vinculación Universitaria</span>
            </div>

            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-bold tracking-tight text-black dark:text-white mb-6">
                UNAH-<span class="text-blue-600 dark:text-yellow-400">VINCULACIÓN</span>
            </h1>

            <p class="max-w-2xl mx-auto text-lg sm:text-xl text-gray-700 dark:text-gray-300 mb-8">
                Conectando la academia con la sociedad para el desarrollo integral de Honduras a través de proyectos de impacto social, investigación aplicada y transferencia de conocimiento.
            </p>

          
           <!-- Image Carousel -->
            <div class="relative max-w-5xl mx-auto mt-20 mb-0">
                <div class="relative rounded-lg overflow-hidden dark:shadow-[0_0_40px_-5px_rgba(234,179,8,0.4)]">
                    <!-- Carousel Container -->
                    <div class="carousel-container relative">
                        <div class="carousel-slides flex transition-transform duration-500">
                            @forelse ($slides as $slide)
                                <div class="carousel-slide min-w-full">
                                    <img src="{{ asset('storage/' . $slide->image_url) }}"
                                        alt="Slide dinámico"
                                        class="w-[1200px] h-[675px] object-fill object-center rounded-lg border border-gray-200 dark:border-gray-700 shadow-xl">
                                </div>
                            @empty
                                @php
                                    $defaultImages = [
                                        'images/Slide/1.jpeg',
                                        'images/Slide/2.jpg',
                                        'images/Slide/3.jpg',
                                        'images/Slide/4.jpg',
                                    ];
                                @endphp
                                @foreach ($defaultImages as $img)
                                    <div class="carousel-slide min-w-full">
                                        <img src="{{ asset($img) }}"
                                            alt="Slide por defecto"
                                            class="w-[1200px] h-[675px] object-fill object-center rounded-lg border border-gray-200 dark:border-gray-700 shadow-xl">
                                    </div>
                                @endforeach
                            @endforelse
                        </div>

                        <!-- Carousel Controls -->
                        <button class="carousel-prev absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 text-white w-10 h-10 rounded-full flex items-center justify-center">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="carousel-next absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 text-white w-10 h-10 rounded-full flex items-center justify-center">
                            <i class="fas fa-chevron-right"></i>
                        </button>

                        <!-- Carousel Indicators -->
                        <div class="carousel-indicators flex justify-center gap-2 mt-4">
                            @php
                                $count = count($slides) > 0 ? count($slides) : count($defaultImages);
                            @endphp
                            @for ($i = 0; $i < $count; $i++)
                                <button class="w-3 h-3 rounded-full {{ $i === 0 ? 'bg-blue-600 dark:bg-yellow-400' : 'bg-gray-300 dark:bg-gray-600' }} carousel-indicator"></button>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>


            <!-- Mission, Vision, Goals Section -->
            <div id="mision" class="relative mt-20 py-20 bg-blue-50 dark:bg-transparent">
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-8 md:grid-cols-3">
                        <!-- Misión -->
                        <div class="relative group">
                            <div class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-lg dark:shadow-[0_0_25px_-5px_rgba(59,130,246,0.4)] h-full transition-all duration-300 hover:shadow-xl">
                                <div class="flex flex-col h-full">
                                    <div class="mb-6">
                                        <div class="w-16 h-16 rounded-2xl bg-blue-600 dark:bg-yellow-400 flex items-center justify-center">
                                            <i class="fas fa-bullseye text-2xl text-white dark:text-black"></i>
                                        </div>
                                    </div>
                                    <h3 class="text-2xl font-bold text-black dark:text-white mb-4">Misión</h3>
                                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-6 flex-1">
                                        "Fortalecer los vínculos entre la Universidad Nacional Autónoma de Honduras y la sociedad hondureña, mediante la implementación de proyectos y programas que contribuyan al desarrollo sostenible del país."
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Visión -->
                        <div id="vision" class="relative group">
                            <div class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-lg dark:shadow-[0_0_25px_-5px_rgba(234,179,8,0.4)] h-full transition-all duration-300 hover:shadow-xl">
                                <div class="flex flex-col h-full">
                                    <div class="mb-6">
                                        <div class="w-16 h-16 rounded-2xl bg-blue-600 dark:bg-yellow-400 flex items-center justify-center">
                                            <i class="fas fa-eye text-2xl text-white dark:text-black"></i>
                                        </div>
                                    </div>
                                    <h3 class="text-2xl font-bold text-black dark:text-white mb-4">Visión</h3>
                                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-6 flex-1">
                                        "Ser el principal referente de vinculación universitaria en Honduras, reconocido por su capacidad de generar soluciones innovadoras a los problemas nacionales y por su compromiso con la transformación social."
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Metas -->
                        <div class="relative group">
                            <div class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-lg dark:shadow-[0_0_25px_-5px_rgba(59,130,246,0.4)] h-full transition-all duration-300 hover:shadow-xl">
                                <div class="flex flex-col h-full">
                                    <div class="mb-6">
                                        <div class="w-16 h-16 rounded-2xl bg-blue-600 dark:bg-yellow-400 flex items-center justify-center">
                                            <i class="fas fa-flag-checkered text-2xl text-white dark:text-black"></i>
                                        </div>
                                    </div>
                                    <h3 class="text-2xl font-bold text-black dark:text-white mb-4">Metas</h3>
                                    <ul class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-6 flex-1 space-y-2">
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-blue-600 dark:text-yellow-400 mr-2"></i>
                                            Implementar 100+ proyectos de impacto social anualmente
                                        </li>
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-blue-600 dark:text-yellow-400 mr-2"></i>
                                            Establecer alianzas con 50+ organizaciones nacionales e internacionales
                                        </li>
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-blue-600 dark:text-yellow-400 mr-2"></i>
                                            Involucrar al 80% de las facultades en proyectos de vinculación
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About Section -->
            <div class="relative mt-0 bg-gradient-to-b from-blue-100 via-blue-50 to-transparent dark:from-blue-900/20 dark:via-blue-950/30 dark:to-transparent py-32">
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-4xl sm:text-5xl font-bold tracking-tight  dark:text-white mb-4">Acerca de</h2>
                    <p class="text-lg  dark:text-gray-400 mb-12 max-w-3xl mx-auto">
                        Conoce más sobre la plataforma UNAH-VINCULACIÓN y su impacto en la comunidad universitaria y la sociedad hondureña.
                    </p>

                    <div class="bg-white/80 backdrop-blur-sm dark:bg-black/40 dark:backdrop-blur-sm rounded-2xl p-8 border border-blue-100 dark:border-white/10 shadow-lg dark:shadow-[0_0_50px_-12px_rgba(37,99,235,0.4)] max-w-4xl mx-auto">
                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-2xl font-bold dark:text-white mb-4">La Plataforma</h3>
                                <p class="text-gray-700 dark:text-gray-300 mb-6">
                                    UNAH-VINCULACIÓN es una plataforma digital diseñada para facilitar la gestión, seguimiento y evaluación de proyectos de vinculación universitaria entre la UNAH y diferentes sectores de la sociedad hondureña.
                                </p>
                                <p class="text-gray-700 dark:text-gray-300">
                                    Permite la colaboración efectiva entre estudiantes, docentes, investigadores y organizaciones externas, maximizando el impacto social de las iniciativas universitarias.
                                </p>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold dark:text-white mb-4">Características</h3>
                                <ul class="text-gray-700 dark:text-gray-300 space-y-2">
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-1 mr-2"></i>
                                        <span>Gestión integral de proyectos de vinculación</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-1 mr-2"></i>
                                        <span>Seguimiento en tiempo real de actividades y resultados</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-1 mr-2"></i>
                                        <span>Generación automática de informes y estadísticas</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-1 mr-2"></i>
                                        <span>Comunicación directa entre participantes y beneficiarios</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 mt-1 mr-2"></i>
                                        <span>Repositorio de documentos y recursos compartidos</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

      

            <!-- Developers Section -->
            <div id="desarrolladores" class="relative mt-20 py-20  dark:bg-transparent">
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-4xl sm:text-5xl font-bold tracking-tight text-black dark:text-white mb-4">Desarrolladores</h2>
                    <p class="text-lg text-gray-700 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
                        Equipo de profesionales responsables del diseño e implementación de la plataforma UNAH-VINCULACIÓN.
                    </p>

                    <!-- Developers Cards -->
                    <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                        <!-- Francisco Paz -->
                        <div class="relative group">
                            <div class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-lg dark:shadow-[0_0_30px_-5px_rgba(234,179,8,0.4)] transition-all duration-300 hover:shadow-xl">
                                <div class="flex flex-col items-center">
                                    <div class="mb-6">
                                        <img 
                                            src="https://via.placeholder.com/150/2563eb/ffffff?text=FP" 
                                            alt="Francisco Paz" 
                                            class="w-32 h-32 rounded-full border-4 border-blue-600 dark:border-yellow-400"
                                        >
                                    </div>
                                    <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Francisco Paz</h3>
                                    <p class="text-gray-700 dark:text-gray-400 mb-4">Desarrollador Full Stack</p>
                                    
                                    <div class="flex space-x-4 mb-4">
                                        <a href="#" class="text-gray-700 dark:text-gray-400 hover:text-blue-600 dark:hover:text-yellow-400 transition-colors">
                                            <i class="fab fa-github text-xl"></i>
                                        </a>
                                        <a href="#" class="text-gray-700 dark:text-gray-400 hover:text-blue-600 dark:hover:text-yellow-400 transition-colors">
                                            <i class="fab fa-linkedin text-xl"></i>
                                        </a>
                                        <a href="#" class="text-gray-700 dark:text-gray-400 hover:text-blue-600 dark:hover:text-yellow-400 transition-colors">
                                            <i class="fab fa-instagram text-xl"></i>
                                        </a>
                                    </div>
                                    
                                    <p class="text-gray-700 dark:text-gray-300">
                                        Especialista en desarrollo web y arquitectura de sistemas, con enfoque en soluciones escalables y seguras.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Ernesto Moncada -->
                        <div class="relative group">
                            <div class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 shadow-lg dark:shadow-[0_0_30px_-5px_rgba(59,130,246,0.4)] transition-all duration-300 hover:shadow-xl">
                                <div class="flex flex-col items-center">
                                    <div class="mb-6">
                                        <img 
                                            src="https://via.placeholder.com/150/f59e0b/ffffff?text=EM" 
                                            alt="Ernesto Moncada" 
                                            class="w-32 h-32 rounded-full border-4 border-blue-600 dark:border-yellow-400"
                                        >
                                    </div>
                                    <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Ernesto Moncada</h3>
                                    <p class="text-gray-700 dark:text-gray-400 mb-4">Desarrollador Frontend</p>
                                    
                                    <div class="flex space-x-4 mb-4">
                                        <a href="#" class="text-gray-700 dark:text-gray-400 hover:text-blue-600 dark:hover:text-yellow-400 transition-colors">
                                            <i class="fab fa-github text-xl"></i>
                                        </a>
                                        <a href="#" class="text-gray-700 dark:text-gray-400 hover:text-blue-600 dark:hover:text-yellow-400 transition-colors">
                                            <i class="fab fa-linkedin text-xl"></i>
                                        </a>
                                        <a href="#" class="text-gray-700 dark:text-gray-400 hover:text-blue-600 dark:hover:text-yellow-400 transition-colors">
                                            <i class="fab fa-instagram text-xl"></i>
                                        </a>
                                    </div>
                                    
                                    <p class="text-gray-700 dark:text-gray-300">
                                        Experto en interfaces de usuario y experiencia de usuario, con habilidades en diseño responsivo y accesibilidad web.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="relative mt-20 py-20 bg-blue-50/70 dark:bg-transparent">
                <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-4xl sm:text-5xl font-bold tracking-tight text-black dark:text-white mb-4">Contáctanos</h2>
                    <p class="text-lg text-gray-700 dark:text-gray-400 mb-12">
                        ¿Tienes alguna pregunta o propuesta? No dudes en ponerte en contacto con nosotros.
                    </p>

                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 sm:p-8 border border-gray-200 dark:border-gray-800 shadow-lg dark:shadow-[0_0_35px_-5px_rgba(59,130,246,0.4)]">
                        <form class="grid gap-6" action="" method="POST">
                            @csrf
                            <div class="grid sm:grid-cols-2 gap-6">
                                <div class="grid gap-2">
                                    <label class="text-sm text-gray-700 dark:text-gray-400">Nombre</label>
                                    <input
                                        type="text"
                                        name="nombre"
                                        placeholder="Juan"
                                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-black dark:text-white placeholder:text-gray-500
                                focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-yellow-400"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <label class="text-sm text-gray-700 dark:text-gray-400">Apellido</label>
                                    <input
                                        type="text"
                                        name="apellido"
                                        placeholder="Pérez"
                                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-black dark:text-white placeholder:text-gray-500
                                focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-yellow-400"
                                    />
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-6">
                                <div class="grid gap-2">
                                    <label class="text-sm text-gray-700 dark:text-gray-400">Correo electrónico</label>
                                    <input
                                        type="email"
                                        name="email"
                                        placeholder="juan@ejemplo.com"
                                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-black dark:text-white placeholder:text-gray-500
                                focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-yellow-400"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <label class="text-sm text-gray-700 dark:text-gray-400">Institución</label>
                                    <input
                                        type="text"
                                        name="institucion"
                                        placeholder="UNAH"
                                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-black dark:text-white placeholder:text-gray-500
                                focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-yellow-400"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <label class="text-sm text-gray-700 dark:text-gray-400">¿Cómo podemos ayudarte?</label>
                                <textarea
                                    rows="4"
                                    name="mensaje"
                                    placeholder="Describe tu consulta o propuesta"
                                    class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-black dark:text-white placeholder:text-gray-500
                            focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-yellow-400 resize-none"
                                ></textarea>
                            </div>

                            <button type="submit" class="bg-blue-600 text-white hover:bg-blue-700 dark:bg-yellow-400 dark:text-black dark:hover:bg-yellow-500 w-full sm:w-auto sm:justify-self-center px-8 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                                Enviar mensaje
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Image carousel functionality
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.carousel-indicator');
        const prevBtn = document.querySelector('.carousel-prev');
        const nextBtn = document.querySelector('.carousel-next');
        const slidesContainer = document.querySelector('.carousel-slides');
        
        let currentSlide = 0;
        const slideCount = slides.length;
        
        // Initialize carousel
        updateCarousel();
        
        // Set up event listeners
        prevBtn.addEventListener('click', () => {
            currentSlide = (currentSlide - 1 + slideCount) % slideCount;
            updateCarousel();
        });
        
        nextBtn.addEventListener('click', () => {
            currentSlide = (currentSlide + 1) % slideCount;
            updateCarousel();
        });
        
        // Add click events to indicators
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                currentSlide = index;
                updateCarousel();
            });
        });
        
        // Auto-advance slides every 5 seconds
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slideCount;
            updateCarousel();
        }, 5000);
        
        // Update carousel display
        function updateCarousel() {
            // Update slides position
            slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            // Update indicators
            indicators.forEach((indicator, index) => {
                if (index === currentSlide) {
                    indicator.classList.add('bg-blue-600', 'dark:bg-yellow-400');
                    indicator.classList.remove('bg-gray-300', 'dark:bg-gray-600');
                } else {
                    indicator.classList.remove('bg-blue-600', 'dark:bg-yellow-400');
                    indicator.classList.add('bg-gray-300', 'dark:bg-gray-600');
                }
            });
        }
    });
</script>
@endsection