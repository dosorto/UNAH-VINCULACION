@extends('layouts.aplicacion.app')
@section('styles')
<style>
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
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden" id="validation-results">
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
                                    open: false,
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
                                {{-- Botón disparador --}}
                                <button
                                    type="button"
                                    @click="open = true"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition"
                                >
                                    Ver Constancia
                                </button>

                                {{-- Modal nativo con Alpine --}}
                                <div
                                    x-show="open"
                                    x-cloak
                                    class="fixed inset-0 z-50 overflow-y-auto"
                                    role="dialog"
                                    aria-modal="true"
                                >
                                    {{-- Fondo oscuro --}}
                                    <div
                                        class="fixed inset-0 bg-black/60"
                                        @click="open = false"
                                    ></div>

                                    {{-- Ventana del modal --}}
                                    <div class="relative flex min-h-full items-start justify-center p-4">
                                        <div class="relative w-full max-w-5xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl my-4">

                                            {{-- Cabecera sticky con controles de zoom --}}
                                            <div class="sticky top-0 z-10 flex flex-wrap items-center gap-2 rounded-t-xl border-b border-gray-200 bg-white px-6 py-3 dark:border-gray-700 dark:bg-gray-900">
                                                <button
                                                    type="button"
                                                    @click="scale = Math.min(scale + 0.1, 2)"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-500 transition"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                                        <path d="M9 6a.75.75 0 0 1 .75.75v1.5h1.5a.75.75 0 0 1 0 1.5h-1.5v1.5a.75.75 0 0 1-1.5 0v-1.5h-1.5a.75.75 0 0 1 0-1.5h1.5v-1.5A.75.75 0 0 1 9 6Z"/>
                                                        <path fill-rule="evenodd" d="M2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Zm7-5.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11Z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Acercar
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="scale = Math.max(scale - 0.1, 0.5)"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-500 transition"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                                        <path d="M6.75 8.25a.75.75 0 0 0 0 1.5h4.5a.75.75 0 0 0 0-1.5h-4.5Z"/>
                                                        <path fill-rule="evenodd" d="M2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Zm7-5.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11Z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Alejar
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="scale = 1; translateX = 0; translateY = 0"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.46-.35Zm-10.624-3.85a5.5 5.5 0 0 1 9.2-2.466l.312.311h-2.433a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .75-.75V2.027a.75.75 0 0 0-1.5 0v2.43l-.31-.31A7 7 0 0 0 3.239 7.286a.75.75 0 0 0 1.45.288Z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Reset
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="open = false"
                                                    class="ml-auto inline-flex items-center justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                                    aria-label="Cerrar"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            {{-- Contenido escalable --}}
                                            <div class="overflow-x-auto p-6">
                                                <div
                                                    x-bind:style="'transform: scale(' + scale + ') translate(' + translateX + 'px,' + translateY + 'px); transform-origin: center;'"
                                                    class="constancia-container"
                                                    @wheel.prevent="wheelZoom($event)"
                                                    @mousedown="startDrag($event)"
                                                    @mousemove="onDrag($event)"
                                                    @mouseup="endDrag()"
                                                    @touchstart="onTouchStart($event)"
                                                    @touchmove="onTouchMove($event)"
                                                    @touchend="onTouchEnd()"
                                                >
                                                    @include('app.docente.constancias.constancia_registro')
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
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

