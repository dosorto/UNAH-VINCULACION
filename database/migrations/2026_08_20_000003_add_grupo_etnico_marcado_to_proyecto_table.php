<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->boolean('indigenas_hombres_marcado')->default(false)->after('mestizos_mujeres');
            $table->boolean('indigenas_mujeres_marcado')->default(false)->after('indigenas_hombres_marcado');
            $table->boolean('afroamericanos_hombres_marcado')->default(false)->after('indigenas_mujeres_marcado');
            $table->boolean('afroamericanos_mujeres_marcado')->default(false)->after('afroamericanos_hombres_marcado');
            $table->boolean('mestizos_hombres_marcado')->default(false)->after('afroamericanos_mujeres_marcado');
            $table->boolean('mestizos_mujeres_marcado')->default(false)->after('mestizos_hombres_marcado');
        });
    }

    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->dropColumn([
                'indigenas_hombres_marcado',
                'indigenas_mujeres_marcado',
                'afroamericanos_hombres_marcado',
                'afroamericanos_mujeres_marcado',
                'mestizos_hombres_marcado',
                'mestizos_mujeres_marcado',
            ]);
        });
    }
};
