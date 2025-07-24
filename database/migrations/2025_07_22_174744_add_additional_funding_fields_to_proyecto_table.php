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
        Schema::table('proyecto', function (Blueprint $table) {
            // Campos para aportes adicionales de financiamiento
            $table->decimal('aporte_contraparte', 10, 2)->nullable();
            $table->decimal('aporte_fondos_internacionales', 10, 2)->nullable();
            $table->decimal('aporte_otras_universidades', 10, 2)->nullable();
            $table->decimal('aporte_beneficiarios', 10, 2)->nullable();
            $table->decimal('otros_aportes', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->dropColumn([
                'aporte_contraparte',
                'aporte_fondos_internacionales',
                'aporte_otras_universidades',
                'aporte_beneficiarios',
                'otros_aportes'
            ]);
        });
    }
};
