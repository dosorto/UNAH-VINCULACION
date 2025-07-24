<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aporte_institucional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto')->onDelete('cascade');
            $table->enum('concepto', [
                'horas_trabajo_docentes',
                'horas_trabajo_estudiantes', 
                'gastos_movilizacion',
                'utiles_materiales_oficina',
                'gastos_impresion',
                'costos_indirectos_infraestructura',
                'costos_indirectos_servicios'
            ]);
            $table->enum('unidad', ['hra_profes', 'hra_estud', 'global', 'porcentaje']);
            $table->decimal('cantidad', 10, 2);
            $table->decimal('costo_unitario', 10, 2);
            $table->decimal('costo_total', 10, 2);
            $table->softDeletes();
            $table->timestamps();
        });

        // Agregar campo para el total del aporte institucional en la tabla proyecto
        Schema::table('proyecto', function (Blueprint $table) {
            $table->decimal('total_aporte_institucional', 12, 2)->nullable()->after('bibliografia');
        });
    }

    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->dropColumn('total_aporte_institucional');
        });
        
        Schema::dropIfExists('aporte_institucional');
    }
};
