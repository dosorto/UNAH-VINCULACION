<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ejes_prioritarios_unah', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        // Tabla pivote para la relación muchos a muchos con proyecto
        Schema::create('eje_prioritario_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto')->onDelete('cascade');
            $table->foreignId('ejes_prioritarios_unah_id')->constrained('ejes_prioritarios_unah')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eje_prioritario_proyecto');
        Schema::dropIfExists('ejes_prioritarios_unah');
    }
};
