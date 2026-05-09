<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flujos_aprobacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->string('nombre', 180);
            $table->string('proceso', 80);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flujo_aprobacion_id')
                ->constrained('flujos_aprobacion')
                ->cascadeOnDelete();
            $table->unsignedInteger('orden');
            $table->string('codigo', 80);
            $table->string('nombre', 180);
            $table->foreignId('cargo_firma_id')->constrained('cargo_firma');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['flujo_aprobacion_id', 'codigo'], 'uq_flujo_aprobacion_etapa_codigo');
            $table->unique(['flujo_aprobacion_id', 'orden'], 'uq_flujo_aprobacion_etapa_orden');
        });

        Schema::table('proyecto', function (Blueprint $table) {
            $table->foreignId('flujo_aprobacion_id')
                ->nullable()
                ->after('numero_dictamen')
                ->constrained('flujos_aprobacion')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->dropConstrainedForeignId('flujo_aprobacion_id');
        });

        Schema::dropIfExists('flujos_aprobacion_etapas');
        Schema::dropIfExists('flujos_aprobacion');
    }
};
