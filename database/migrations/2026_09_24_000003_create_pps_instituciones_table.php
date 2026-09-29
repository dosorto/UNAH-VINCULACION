<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de instituciones / empresas del FORM-DVUS-014 (ítems 20, 21 y 23 a 28). Cada
 * registro de PPS/SS elige una del catálogo y conserva una copia de sus datos para el PDF;
 * compromisos, instrumento y jefe directo se capturan por registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pps_instituciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->index();
            $table->string('nacionalidad', 20);
            $table->string('pais')->nullable();
            $table->string('tipo', 50);
            $table->string('sector', 80);
            $table->text('direccion');
            $table->string('representante_legal');
            $table->string('telefono', 30);
            $table->string('correo_rrhh');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('pps_servicio_social', function (Blueprint $table) {
            $table->foreignId('pps_institucion_id')->nullable()->after('nombre_institucion')
                ->constrained('pps_instituciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pps_institucion_id');
        });

        Schema::dropIfExists('pps_instituciones');
    }
};
