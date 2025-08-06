<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('presupuesto_servicio', function (Blueprint $table) {
        $table->id();
        $table->text('descripcion_excedente')->nullable(); // Breve descripción del excedente
        $table->decimal('total_ingresos', 12, 2)->default(0);
        $table->decimal('total_egresos', 12, 2)->default(0);
        $table->decimal('excedente', 12, 2)->default(0);
        $table->decimal('total_aporte_unah', 12, 2)->default(0);
        $table->timestamps();
        });

        Schema::create('ingresos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('presupuesto_id')->constrained('presupuesto_servicio')->onDelete('cascade');
        $table->string('descripcion');
        $table->string('unidad');
        $table->integer('cantidad');
        $table->decimal('costo_unitario', 12, 2);
        $table->decimal('costo_total', 12, 2);
        $table->timestamps();
        });

        Schema::create('egresos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('presupuesto_id')->constrained('presupuesto_servicio')->onDelete('cascade');
        $table->string('descripcion');
        $table->string('unidad');
        $table->integer('cantidad');
        $table->decimal('costo_unitario', 12, 2);
        $table->decimal('costo_total', 12, 2);
        $table->timestamps();
        });

        Schema::create('aportes_unah', function (Blueprint $table) {
        $table->id();
        $table->foreignId('presupuesto_id')->constrained('presupuesto_servicio')->onDelete('cascade');
        $table->string('descripcion');
        $table->string('unidad');
        $table->integer('cantidad');
        $table->decimal('costo_unitario', 12, 2);
        $table->decimal('costo_total', 12, 2);
        $table->timestamps();
        });
   
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuestos_servicios');
    }
};
