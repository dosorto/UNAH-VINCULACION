@extends('layouts.aplicacion.app')

@section('title', 'Validar Constancia')

@section('content')


<!-- Contact Section -->
<x-aplicacion.hero-section
title="Validación de Constancias"
description="Verifica la autenticidad de las constancias emitidas por UNAH-VINCULACIÓN ingresando el código único para su validación."
theme="azul"
text-position="top-right"
contentPosition="opposide"
:backgroundImage="true"
:fullpage="true"
>
<div class=" w-full md:w-xl  max-w-xl bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden mb-8">
    <div class="p-6 sm:p-8">
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-black text-[#235383] dark:text-white mb-4">Buscar por código</h2>
            @livewire('constancia.buscar-constancia')
        </div>
        
        
    </div>
</div>

</x-aplicacion.hero-section>

     
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