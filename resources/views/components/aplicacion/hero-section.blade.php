@props([
    'title' => null,
    'description' => null,
    'theme' => 'azul', // azul | blanco | amarillo
    'textPosition' => 'center', // top-left, top-right, bottom-left, bottom-right, center
    'contentPosition' => 'opposite', // beside, opposite
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
@endphp

<div {{ $attributes->merge([
    'class' => "relative py-20 px-6 sm:px-12 rounded-xl overflow-hidden mb-20 mt-20 $themeClasses dark:bg-gray-900 dark:text-white dark:border-none"
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
