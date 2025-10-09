<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PASO 1: Actualizar registros existentes con valores null ANTES de cambiar la estructura

        // Actualizar registros existentes con valores null en aporte_institucional
        DB::table('aporte_institucional')->whereNull('cantidad')->update(['cantidad' => 0]);
        DB::table('aporte_institucional')->whereNull('costo_unitario')->update(['costo_unitario' => 0]);
        DB::table('aporte_institucional')->whereNull('costo_total')->update(['costo_total' => 0]);

        // Actualizar registros existentes con valores null en presupuesto
        $presupuestoFields = [
            'cantidad_horas_docentes', 'costo_unitario_docentes', 'costo_total_docentes',
            'cantidad_horas_estudiantes', 'costo_unitario_estudiantes', 'costo_total_estudiantes',
            'cantidad_movilizacion', 'costo_unitario_movilizacion', 'costo_total_movilizacion',
            'cantidad_utiles', 'costo_unitario_utiles', 'costo_total_utiles',
            'cantidad_impresion', 'costo_unitario_impresion', 'costo_total_impresion',
            'cantidad_infraestructura', 'costo_unitario_infraestructura', 'costo_total_infraestructura',
            'cantidad_servicios', 'costo_unitario_servicios', 'costo_total_servicios'
        ];

        foreach ($presupuestoFields as $field) {
            DB::table('presupuesto')->whereNull($field)->update([$field => 0]);
        }

        // Actualizar registros existentes con valores null en tablas de servicios
        DB::table('ingresos')->whereNull('cantidad')->update(['cantidad' => 0]);
        DB::table('ingresos')->whereNull('costo_unitario')->update(['costo_unitario' => 0]);
        DB::table('ingresos')->whereNull('costo_total')->update(['costo_total' => 0]);

        DB::table('egresos')->whereNull('cantidad')->update(['cantidad' => 0]);
        DB::table('egresos')->whereNull('costo_unitario')->update(['costo_unitario' => 0]);
        DB::table('egresos')->whereNull('costo_total')->update(['costo_total' => 0]);

        DB::table('aportes_unah')->whereNull('cantidad')->update(['cantidad' => 0]);
        DB::table('aportes_unah')->whereNull('costo_unitario')->update(['costo_unitario' => 0]);
        DB::table('aportes_unah')->whereNull('costo_total')->update(['costo_total' => 0]);

        // PASO 2: Cambiar la estructura de la tabla para establecer default 0

        // Actualizar tabla aporte_institucional - establecer default 0
        Schema::table('aporte_institucional', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total', 10, 2)->default(0)->nullable(false)->change();
        });

        // Actualizar tabla presupuesto - campos detallados del presupuesto
        Schema::table('presupuesto', function (Blueprint $table) {
            // Horas de trabajo docentes
            $table->decimal('cantidad_horas_docentes', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario_docentes', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total_docentes', 10, 2)->default(0)->nullable(false)->change();
            
            // Horas de trabajo estudiantes
            $table->decimal('cantidad_horas_estudiantes', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario_estudiantes', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total_estudiantes', 10, 2)->default(0)->nullable(false)->change();
            
            // Gastos de movilización
            $table->decimal('cantidad_movilizacion', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario_movilizacion', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total_movilizacion', 10, 2)->default(0)->nullable(false)->change();
            
            // Útiles y materiales de oficina
            $table->decimal('cantidad_utiles', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario_utiles', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total_utiles', 10, 2)->default(0)->nullable(false)->change();
            
            // Gastos de impresión
            $table->decimal('cantidad_impresion', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario_impresion', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total_impresion', 10, 2)->default(0)->nullable(false)->change();
            
            // Costos indirectos por infraestructura
            $table->decimal('cantidad_infraestructura', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario_infraestructura', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total_infraestructura', 10, 2)->default(0)->nullable(false)->change();
            
            // Costos indirectos por servicios públicos
            $table->decimal('cantidad_servicios', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario_servicios', 10, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total_servicios', 10, 2)->default(0)->nullable(false)->change();
        });

        // Actualizar tablas de servicios tecnológicos
        Schema::table('ingresos', function (Blueprint $table) {
            $table->integer('cantidad')->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario', 12, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total', 12, 2)->default(0)->nullable(false)->change();
        });

        Schema::table('egresos', function (Blueprint $table) {
            $table->integer('cantidad')->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario', 12, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total', 12, 2)->default(0)->nullable(false)->change();
        });

        Schema::table('aportes_unah', function (Blueprint $table) {
            $table->integer('cantidad')->default(0)->nullable(false)->change();
            $table->decimal('costo_unitario', 12, 2)->default(0)->nullable(false)->change();
            $table->decimal('costo_total', 12, 2)->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios en tabla aporte_institucional
        Schema::table('aporte_institucional', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 2)->nullable()->change();
            $table->decimal('costo_unitario', 10, 2)->nullable()->change();
            $table->decimal('costo_total', 10, 2)->nullable()->change();
        });

        // Revertir cambios en tabla presupuesto
        Schema::table('presupuesto', function (Blueprint $table) {
            $table->decimal('cantidad_horas_docentes', 10, 2)->nullable()->change();
            $table->decimal('costo_unitario_docentes', 10, 2)->nullable()->change();
            $table->decimal('costo_total_docentes', 10, 2)->nullable()->change();
            $table->decimal('cantidad_horas_estudiantes', 10, 2)->nullable()->change();
            $table->decimal('costo_unitario_estudiantes', 10, 2)->nullable()->change();
            $table->decimal('costo_total_estudiantes', 10, 2)->nullable()->change();
            $table->decimal('cantidad_movilizacion', 10, 2)->nullable()->change();
            $table->decimal('costo_unitario_movilizacion', 10, 2)->nullable()->change();
            $table->decimal('costo_total_movilizacion', 10, 2)->nullable()->change();
            $table->decimal('cantidad_utiles', 10, 2)->nullable()->change();
            $table->decimal('costo_unitario_utiles', 10, 2)->nullable()->change();
            $table->decimal('costo_total_utiles', 10, 2)->nullable()->change();
            $table->decimal('cantidad_impresion', 10, 2)->nullable()->change();
            $table->decimal('costo_unitario_impresion', 10, 2)->nullable()->change();
            $table->decimal('costo_total_impresion', 10, 2)->nullable()->change();
            $table->decimal('cantidad_infraestructura', 10, 2)->nullable()->change();
            $table->decimal('costo_unitario_infraestructura', 10, 2)->nullable()->change();
            $table->decimal('costo_total_infraestructura', 10, 2)->nullable()->change();
            $table->decimal('cantidad_servicios', 10, 2)->nullable()->change();
            $table->decimal('costo_unitario_servicios', 10, 2)->nullable()->change();
            $table->decimal('costo_total_servicios', 10, 2)->nullable()->change();
        });

        // Revertir cambios en tablas de servicios tecnológicos
        Schema::table('ingresos', function (Blueprint $table) {
            $table->integer('cantidad')->nullable()->change();
            $table->decimal('costo_unitario', 12, 2)->nullable()->change();
            $table->decimal('costo_total', 12, 2)->nullable()->change();
        });

        Schema::table('egresos', function (Blueprint $table) {
            $table->integer('cantidad')->nullable()->change();
            $table->decimal('costo_unitario', 12, 2)->nullable()->change();
            $table->decimal('costo_total', 12, 2)->nullable()->change();
        });

        Schema::table('aportes_unah', function (Blueprint $table) {
            $table->integer('cantidad')->nullable()->change();
            $table->decimal('costo_unitario', 12, 2)->nullable()->change();
            $table->decimal('costo_total', 12, 2)->nullable()->change();
        });
    }
};
