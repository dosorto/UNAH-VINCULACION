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
        Schema::table('presupuesto', function (Blueprint $table) {
            // Campos para horas de trabajo docentes
            $table->decimal('cantidad_horas_docentes', 10, 2)->nullable();
            $table->decimal('costo_unitario_docentes', 10, 2)->nullable();
            $table->decimal('costo_total_docentes', 10, 2)->nullable();
            
            // Campos para horas de trabajo estudiantes
            $table->decimal('cantidad_horas_estudiantes', 10, 2)->nullable();
            $table->decimal('costo_unitario_estudiantes', 10, 2)->nullable();
            $table->decimal('costo_total_estudiantes', 10, 2)->nullable();
            
            // Campos para gastos de movilización
            $table->decimal('cantidad_movilizacion', 10, 2)->nullable();
            $table->decimal('costo_unitario_movilizacion', 10, 2)->nullable();
            $table->decimal('costo_total_movilizacion', 10, 2)->nullable();
            
            // Campos para útiles y materiales de oficina
            $table->decimal('cantidad_utiles', 10, 2)->nullable();
            $table->decimal('costo_unitario_utiles', 10, 2)->nullable();
            $table->decimal('costo_total_utiles', 10, 2)->nullable();
            
            // Campos para gastos de impresión
            $table->decimal('cantidad_impresion', 10, 2)->nullable();
            $table->decimal('costo_unitario_impresion', 10, 2)->nullable();
            $table->decimal('costo_total_impresion', 10, 2)->nullable();
            
            // Campos para costos indirectos por infraestructura
            $table->decimal('cantidad_infraestructura', 10, 2)->nullable();
            $table->decimal('costo_unitario_infraestructura', 10, 2)->nullable();
            $table->decimal('costo_total_infraestructura', 10, 2)->nullable();
            
            // Campos para costos indirectos por servicios públicos
            $table->decimal('cantidad_servicios', 10, 2)->nullable();
            $table->decimal('costo_unitario_servicios', 10, 2)->nullable();
            $table->decimal('costo_total_servicios', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuesto', function (Blueprint $table) {
            $table->dropColumn([
                'cantidad_horas_docentes',
                'costo_unitario_docentes', 
                'costo_total_docentes',
                'cantidad_horas_estudiantes',
                'costo_unitario_estudiantes',
                'costo_total_estudiantes',
                'cantidad_movilizacion',
                'costo_unitario_movilizacion',
                'costo_total_movilizacion',
                'cantidad_utiles',
                'costo_unitario_utiles',
                'costo_total_utiles',
                'cantidad_impresion',
                'costo_unitario_impresion',
                'costo_total_impresion',
                'cantidad_infraestructura',
                'costo_unitario_infraestructura',
                'costo_total_infraestructura',
                'cantidad_servicios',
                'costo_unitario_servicios',
                'costo_total_servicios'
            ]);
        });
    }
};
