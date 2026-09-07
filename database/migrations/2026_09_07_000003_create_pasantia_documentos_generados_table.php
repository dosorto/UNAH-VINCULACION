<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { if (Schema::hasTable('pasantia_documentos_generados')) return; Schema::create('pasantia_documentos_generados', function (Blueprint $t) { $t->id(); $t->foreignId('pasantia_id')->constrained('pasantias')->cascadeOnDelete(); $t->string('tipo', 40)->default('formulario'); $t->string('archivo'); $t->string('nombre_original'); $t->unsignedInteger('version')->default(1); $t->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('generado_en')->nullable(); $t->timestamps(); $t->index(['pasantia_id','tipo']); }); }
    public function down(): void { Schema::dropIfExists('pasantia_documentos_generados'); }
};
