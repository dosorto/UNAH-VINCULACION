@props(['titulo' => '', 'ruta' => 'login', 'permiso' => ''])

@if(auth()->user()->activeRole && auth()->user()->activeRole->hasAnyPermission($permiso))
    <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
        <a href="{{ route($ruta) }}"
            class="flex items-center font-semibold p-2  rounded hover:bg-gray-100 hover:border-gray-300 focus:bg-gray-200 focus:border-gray-300
      
dark:hover:bg-white/5 dark:focus-visible:bg-white/5 dark:text-gray-200
    dark:bg-white/5 dark:focus-visible:bg-white/10 dark:border-gray-700
     @if (request()->routeIs($ruta)) bg-gray-100 text-primary-600 dark:text-primary-400 @endif
"
            aria-current="true" wire:navigate>
            {{ $titulo }}
        </a>
    </li>
@endif  
