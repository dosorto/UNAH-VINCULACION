@extends('layouts.aplicacion.app')
@section('styles')
<style>
.fi-modal-window {
    max-width: 90vw;
}
.fi-modal-content {
    overflow-x: scroll;
}

/* Estilos para formato tamaño carta */
.constancia-container {
    width: 8.5in;
    height: 11in;
    margin: 0 auto;
    background: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 1in;
    overflow: hidden;
    position: relative;
}

.constancia-container h1, .constancia-container p {
    margin: 0;
    padding: 0;
    text-align: center;
}

/* Ajustes para el QR */
.constancia-container img.qr-code {
    width: 150px;
    height: 150px;
    border: 1px solid #ddd;
    margin: 20px auto;
}

/* Ajustes para las imágenes */
.constancia-container img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto;
}
</style>
@endsection
@section('title', 'Mensaje')

@section('content')
    <div class="relative pt-24 pb-16">
        <!-- Enhanced Gradient Background -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-blue-50 via-white dark:from-black dark:via-gray-900 dark:to-black"></div>
        </div>

        <div class="relative max-w-3xl mx-auto px-4 sm:px-6">
            <!-- Navigation Header -->
            <div class="flex justify-between items-center mb-4">
             
            </div>
            
            <!-- Message Box -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden" id="validation-results" style="">
                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-semibold text-black dark:text-white mb-4">Resultado de la validación</h2>
                    
                    <!-- Ejemplo de constancia válida -->
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-green-800 dark:text-green-300">Constancia válida</h3>
                                <div class="mt-2 text-green-700 dark:text-green-400">
                                    <p>La constancia ha sido verificada correctamente en nuestra base de datos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 dark:border-gray-800 pt-6">
                        <h3 class="text-lg font-medium text-black dark:text-white mb-4">Detalles de la constancia</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Código de constancia</p>
                                <p class="mt-1 text-black dark:text-white"> {{ $codigoVerificacion ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Numero de Empleado</p>
                                <p class="mt-1 text-black dark:text-white">{{$numeroEmpleado}}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre del participante</p>
                                <p class="mt-1 text-black dark:text-white">{{$nombreEmpleado}}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de constancia</p>
                                <p class="mt-1 text-black dark:text-white">{{$tipo}}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Proyecto</p>
                                <p class="mt-1 text-black dark:text-white">{{$nombreProyecto}}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Horas acreditadas</p>
                                <p class="mt-1 text-black dark:text-white">{{$horas}}</p>
                            </div>
                        </div>
                    </div>

                        
                        
                        <div class="mt-6">
                            <div
    x-data="{
        scale: 1,
        originX: 0,
        originY: 0,
        translateX: 0,
        translateY: 0,
        isDragging: false,
        lastX: 0,
        lastY: 0,

        wheelZoom(e) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            this.scale = Math.min(Math.max(this.scale + delta, 0.5), 3);
        },

        startDrag(e) {
            this.isDragging = true;
            this.lastX = e.clientX;
            this.lastY = e.clientY;
        },

        onDrag(e) {
            if (!this.isDragging) return;
            this.translateX += e.clientX - this.lastX;
            this.translateY += e.clientY - this.lastY;
            this.lastX = e.clientX;
            this.lastY = e.clientY;
        },

        endDrag() {
            this.isDragging = false;
        },

        pinchStartDistance: null,

        onTouchStart(e) {
            if (e.touches.length === 2) {
                const dx = e.touches[0].clientX - e.touches[1].clientX;
                const dy = e.touches[0].clientY - e.touches[1].clientY;
                this.pinchStartDistance = Math.sqrt(dx * dx + dy * dy);
            }
        },

        onTouchMove(e) {
            if (e.touches.length === 2 && this.pinchStartDistance !== null) {
                const dx = e.touches[0].clientX - e.touches[1].clientX;
                const dy = e.touches[0].clientY - e.touches[1].clientY;
                const newDistance = Math.sqrt(dx * dx + dy * dy);
                const scaleChange = newDistance / this.pinchStartDistance;
                this.scale = Math.min(Math.max(this.scale * scaleChange, 0.5), 3);
                this.pinchStartDistance = newDistance;
            }
        },

        onTouchEnd() {
            this.pinchStartDistance = null;
        }
    }"
>

                                <x-filament::modal width="5xl" sticky-header >
                                    <x-slot name="trigger">
                                        <x-filament::button>
                                            Ver Constancia
                                        </x-filament::button>
                                    </x-slot>
                            
                                    <x-slot name="heading">
                                        
                                        
                                            <x-filament::button
                                            x-on:click="scale = Math.min(scale + 0.1, 2)"
                                            icon="heroicon-m-magnifying-glass-plus"
                                            color="primary"
                                        >
                                            <span class="text-white">Acercar</span>
                                        </x-filament::button>
                                        
                                        <x-filament::button
                                            x-on:click="scale = Math.max(scale - 0.1, 0.5)"
                                            icon="heroicon-m-magnifying-glass-minus"
                                            color="primary"
                                        >
                                            <span class="text-white">Alejar</span>
                                        </x-filament::button>
                                        
                                        <x-filament::button
                                            x-on:click="scale = 1"
                                            icon="heroicon-m-arrow-path"
                                            
                                        >
                                            <span >Reset</span>
                                        </x-filament::button>          
                                    </x-slot>
                            
                                    {{-- Contenido escalable --}}
                                   
                                    <div
    x-bind:style="'transform: scale(' + scale + ') translate(' + translateX + 'px,' + translateY + 'px); transform-origin: center;'"
    class="constancia-container"
   
>
                                        @include('app.docente.constancias.constancia_registro')
                                    </div>
                            
                                    {{-- Footer con botones que usan Alpine --}}
                                    
                                </x-filament::modal>
                            </div>
                            
                           
                            
                        </div>
                    </div>
                </div>
            </div>
        </div> 
        </div>
    </div> 
@endsection
@section('scripts')

@endsection

