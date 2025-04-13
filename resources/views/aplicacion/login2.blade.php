@extends('layouts.aplicacion.app')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="min-h-screen pt-32 pb-12">
    <div class="max-w-md mx-auto bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden md:max-w-2xl m-4">
        <div class="p-8">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-black dark:text-white mb-2">Iniciar Sesión</h2>
                <p class="text-gray-700 dark:text-gray-400">Accede a la plataforma UNAH-VINCULACIÓN</p>
            </div>
            
            <div class="space-y-6">
                <a href="
                " class="flex items-center justify-center w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-black dark:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23">
                        <path fill="#f3f3f3" d="M0 0h23v23H0z"/>
                        <path fill="#f35325" d="M1 1h10v10H1z"/>
                        <path fill="#81bc06" d="M12 1h10v10H12z"/>
                        <path fill="#05a6f0" d="M1 12h10v10H1z"/>
                        <path fill="#ffba08" d="M12 12h10v10H12z"/>
                    </svg>
                    Iniciar sesión con Microsoft
                </a>
                
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">O continúa con</span>
                    </div>
                </div>
                {{$slot}}
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo electrónico</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 dark:focus:ring-yellow-400 focus:border-blue-500 dark:focus:border-yellow-400 sm:text-sm bg-white dark:bg-gray-800 text-black dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="current-password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 dark:focus:ring-yellow-400 focus:border-blue-500 dark:focus:border-yellow-400 sm:text-sm bg-white dark:bg-gray-800 text-black dark:text-white">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 dark:text-yellow-400 focus:ring-blue-500 dark:focus:ring-yellow-400 border-gray-300 dark:border-gray-700 rounded">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Recordarme
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="" class="font-medium text-blue-600 dark:text-yellow-400 hover:text-blue-500 dark:hover:text-yellow-300">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-yellow-400 dark:text-black dark:hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-yellow-400">
                            Iniciar sesión
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        ¿No tienes una cuenta? 
                        <a href="" class="font-medium text-blue-600 dark:text-yellow-400 hover:text-blue-500 dark:hover:text-yellow-300">
                            Regístrate
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection