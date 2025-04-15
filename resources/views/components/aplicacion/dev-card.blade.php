@props([
    'name',
    'role',
    'image',
    'description' => '',
    'github' => null,
    'linkedin' => null,
    'instagram' => null,
])

<div class="flex items-center gap-4 p-4 bg-[#235383] dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl mx-auto">
    <!-- Imagen -->
    <img src="{{ $image }}" alt="{{ $name }}" class="w-16 h-16 rounded-full object-cover border-b border-gray-300 dark:border-gray-600">

    <!-- Info -->
    <div class="flex flex-col">
        <h3 class="text-lg font-semibold text-white dark:text-gray-100">{{ $name }}</h3>
        <p class="text-sm text-gray-100 dark:text-gray-400 mb-1">{{ $role }}</p>
        
        <!-- Redes -->
        <div class="flex space-x-3 mt-2">
            @if ($github)
                <a href="{{ $github }}" target="_blank" class="transition">
                    <img src="{{ asset('images/github.svg') }}" alt="GitHub" class="text-white w-5 h-5 invert" />
                </a>
            @endif
            @if ($linkedin)
                <a href="{{ $linkedin }}" target="_blank" class="transition">
                    <img src="{{ asset('images/linkedin.svg') }}" alt="LinkedIn" class="w-5 h-5 invert" />
                </a>
            @endif
            @if ($instagram)
                <a href="{{ $instagram }}" target="_blank" class="transition">
                    <img src="{{ asset('images/instagram.svg') }}" alt="Instagram" class="w-5 h-5 invert" />
                </a>
            @endif
        </div>
    </div>
</div>
