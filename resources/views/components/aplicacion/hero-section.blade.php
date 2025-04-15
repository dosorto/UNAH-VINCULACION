@props([
    'title' => null,
    'description' => null,
    'theme' => 'azul',
    'textPosition' => 'center',
    'contentPosition' => 'opposite',
    'backgroundImage' => false,
    'backgroundPosition' => 'izquierda',
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
                background-position: 75% center; /* Mueve el sol a la derecha (evita la izquierda fea) */
                opacity: 0.15;
                z-index: 0;
            }

            .with-bg-image.bg-right-flip::before {
                background-position: 25% center; /* Para espejo: mueve el sol al otro lado bonito */
                transform: scaleX(-1);
            }

            .with-bg-image > * {
                position: relative;
                z-index: 10;
            }
        </style>
    @endpush
@endonce


<div {{ $attributes->merge([
    'class' => "relative py-20 px-6 sm:px-12 rounded-xl overflow-hidden mb-20 mt-20 $themeClasses $backgroundClass $bgPositionClass dark:bg-gray-900 dark:text-white dark:border-none"
]) }}>
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row gap-10 {{ $contentPosition === 'beside' ? 'md:flex-row' : 'md:flex-col' }}">
        
        {{-- Texto --}}
        <div class="flex flex-1 flex-col {{ $textClasses }}">
            @if($title)
                <h2 class="text-4xl sm:text-5xl font-bold tracking-tight mb-4">{{ $title }}</h2>
            @endif

            @if($description)
                <p class="text-lg opacity-90">{{ $description }}</p>
            @endif
        </div>

        {{-- Slot para contenido adicional --}}
        <div class="flex-1">
            {{ $slot }}
        </div>
    </div>
</div>
