@extends('layouts.aplicacion.app')

@section('title', 'Validar Constancia')

@section('content')
<div class="min-h-screen pt-32 pb-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-black dark:text-white mb-2">Validación de Constancias</h1>
            <p class="text-gray-700 dark:text-gray-400 max-w-2xl mx-auto">
                Verifica la autenticidad de las constancias emitidas por UNAH-VINCULACIÓN ingresando el código único o subiendo el documento para su validación.
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="p-6 sm:p-8">
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-black dark:text-white mb-4">Buscar por código</h2>
                    <form action="" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="certificate_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código de constancia</label>
                            <input 
                                type="text" 
                                id="certificate_code" 
                                name="certificate_code" 
                                placeholder="Ej. UNAH-VIN-2025-12345" 
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 dark:focus:ring-yellow-400 focus:border-blue-500 dark:focus:border-yellow-400 bg-white dark:bg-gray-800 text-black dark:text-white"
                                required
                            >
                        </div>
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-yellow-400 dark:hover:bg-yellow-500 text-white dark:text-black rounded-lg font-medium"
                        >
                            Verificar
                        </button>
                    </form>
                </div>
                
                <div class="border-t border-gray-200 dark:border-gray-800 pt-8">
                    <h2 class="text-xl font-semibold text-black dark:text-white mb-4">Subir documento para validación</h2>
                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6 text-center">
                            <div class="space-y-2">
                                <div class="mx-auto flex justify-center">
                                    <i class="fas fa-file-pdf text-4xl text-gray-400 dark:text-gray-600"></i>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <label for="file-upload" class="relative cursor-pointer rounded-md font-medium text-blue-600 dark:text-yellow-400 hover:text-blue-500 dark:hover:text-yellow-300">
                                        <span>Sube un archivo</span>
                                        <input id="file-upload" name="certificate_file" type="file" class="sr-only" accept=".pdf">
                                    </label>
                                    <p>o arrastra y suelta</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-500">PDF hasta 10MB</p>
                            </div>
                            <div class="mt-4 hidden" id="file-preview">
                                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 p-2 rounded">
                                    <div class="flex items-center">
                                        <i class="fas fa-file-pdf text-blue-600 dark:text-yellow-400 mr-2"></i>
                                        <span class="text-sm text-gray-700 dark:text-gray-300" id="file-name"></span>
                                    </div>
                                    <button type="button" id="remove-file" class="text-gray-500 hover:text-red-500">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-yellow-400 dark:hover:bg-yellow-500 text-white dark:text-black rounded-lg font-medium"
                        >
                            Validar documento
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Resultados de validación (mostrar condicionalmente) -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden" id="validation-results" style="display: none;">
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
                            <p class="mt-1 text-black dark:text-white">UNAH-VIN-2025-12345</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de emisión</p>
                            <p class="mt-1 text-black dark:text-white">15 de marzo de 2025</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre del participante</p>
                            <p class="mt-1 text-black dark:text-white">Juan Antonio Pérez Rodríguez</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de constancia</p>
                            <p class="mt-1 text-black dark:text-white">Participación en proyecto de vinculación</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Proyecto</p>
                            <p class="mt-1 text-black dark:text-white">Desarrollo de sistemas para comunidades rurales</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Horas acreditadas</p>
                            <p class="mt-1 text-black dark:text-white">120 horas</p>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 dark:bg-yellow-400 dark:text-black dark:hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-yellow-400">
                            <i class="fas fa-download mr-2"></i> Descargar certificado digital
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileUpload = document.getElementById('file-upload');
        const filePreview = document.getElementById('file-preview');
        const fileName = document.getElementById('file-name');
        const removeFile = document.getElementById('remove-file');
        const dropZone = document.querySelector('.border-dashed');
        
        // Mostrar nombre del archivo seleccionado
        fileUpload.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                fileName.textContent = this.files[0].name;
                filePreview.classList.remove('hidden');
            }
        });
        
        // Eliminar archivo
        removeFile.addEventListener('click', function() {
            fileUpload.value = '';
            filePreview.classList.add('hidden');
        });
        
        // Drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropZone.classList.add('border-blue-500', 'dark:border-yellow-400', 'bg-blue-50', 'dark:bg-gray-800');
        }
        
        function unhighlight() {
            dropZone.classList.remove('border-blue-500', 'dark:border-yellow-400', 'bg-blue-50', 'dark:bg-gray-800');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files && files[0]) {
                fileUpload.files = files;
                fileName.textContent = files[0].name;
                filePreview.classList.remove('hidden');
            }
        }
        
        // Para propósitos de demostración, mostrar resultados al enviar el formulario
        const forms = document.querySelectorAll('form');
        const validationResults = document.getElementById('validation-results');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                validationResults.style.display = 'block';
                window.scrollTo({
                    top: validationResults.offsetTop - 100,
                    behavior: 'smooth'
                });
            });
        });
    });
</script>
@endsection