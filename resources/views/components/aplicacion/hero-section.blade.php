@props([
    'title' => null,
    'description' => null,
    'theme' => 'azul',
    'textPosition' => 'center',
    'contentPosition' => 'opposite', // 'beside' o 'opposite'
    'backgroundImage' => false,
    'backgroundPosition' => 'izquierda',
    'fullpage' => false,
])

@php
    $themes = [
        'azul' => 'bg-[#235383] text-white',
        'blanco' => 'bg-white text-black border border-gray-300',
        'amarillo' => 'bg-yellow-400 text-black',
    ];

    $textAlignments = [
        'top-left' => 'justify-start items-start text-left',
        'top-right' => 'justify-end items-start text-right',
        'bottom-left' => 'justify-start items-end text-left',
        'bottom-right' => 'justify-end items-end text-right',
        'center' => 'justify-center items-center text-center',
    ];

    $themeClasses = $themes[$theme] ?? $themes['azul'];
    $textClasses = $textAlignments[$textPosition] ?? $textAlignments['center'];
    $backgroundClass = $backgroundImage ? 'with-bg-image' : '';
    $bgPositionClass = $backgroundImage ? ($backgroundPosition === 'derecha' ? 'bg-right-flip' : 'bg-left') : '';
    $layoutClass = $fullpage 
        ? 'min-h-screen w-full py-20 px-6 sm:px-12  ' 
        : 'py-20 px-6 sm:px-12 rounded-xl mb-20 mt-20';
@endphp

@once
    @push('styles')
        <style>
            .with-bg-image::before {
                content: "";
                position: absolute;
                inset: 0;
                background-image: url('/images/Image/Sol.png');
                background-repeat: no-repeat;
                background-size: cover;
                background-position: 75% center;
                z-index: 0;
                transition: opacity 0.3s ease;
            }

            .with-bg-image.bg-right-flip::before {
                background-position: 25% center;
                transform: scaleX(-1);
            }

            .with-bg-image > * {
                position: relative;
                z-index: 10;
            }

            /* Opacidad por defecto */
            .with-bg-image::before {
                opacity: 0.15;
            }

            /* Más opacidad si el tema es azul o amarillo */
            .bg-[#235383].with-bg-image::before,
            .bg-yellow-400.with-bg-image::before {
                opacity: 0.9;
            }

            /* Si está en modo oscuro, la opacidad vuelve a 0.15 sin importar el tema */
            .dark .with-bg-image::before {
                opacity: 0.15 !important;
            }
        </style>
    @endpush
@endonce


<div {{ $attributes->merge([
    'class' => "flex relative overflow-hidden $layoutClass $themeClasses $backgroundClass $bgPositionClass dark:bg-gray-900 dark:text-white dark:border-none"
]) }}>
    <div class="max-w-7xl mx-auto w-full flex flex-col {{ $contentPosition === 'beside' ? 'md:flex-row' : '' }} gap-6 items-center justify-center">
        
        {{-- Bloque de texto --}}
        <div class="flex-1 flex flex-col {{ $textClasses }} {{$fullpage ? 'mt-8': '' }}">
            @if($title)
                <h2 class="text-4xl sm:text-5xl font-bold tracking-tight mb-2">{{ $title }}</h2>
            @endif

            @if($description)
                <p class="text-lg opacity-90">{{ $description }}</p>
            @endif
        </div>

        {{-- Contenido adicional (slot) --}}
        <div class="flex-1 p-2 w-full flex justify-center items-center">
            {{ $slot }}
        </div>

    </div>
</div>



