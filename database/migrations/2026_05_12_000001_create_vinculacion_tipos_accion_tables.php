<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vinculacion_tipos_accion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->string('nombre', 180);
            $table->text('descripcion')->nullable();
            $table->string('badge', 120)->nullable();
            $table->string('icono', 80)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vinculacion_tipos_accion_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_accion_id')
                ->constrained('vinculacion_tipos_accion')
                ->cascadeOnDelete();
            $table->string('codigo', 80);
            $table->string('nombre', 180);
            $table->text('descripcion')->nullable();
            $table->foreignId('categoria_id')
                ->nullable()
                ->constrained('categorias')
                ->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tipo_accion_id', 'codigo'], 'uq_vinculacion_accion_opcion_codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vinculacion_tipos_accion_opciones');
        Schema::dropIfExists('vinculacion_tipos_accion');
    }
};
