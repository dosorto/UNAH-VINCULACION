<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de la SOLICITUD DE PRÁCTICA que se genera a mitad del FORM-DVUS-014: a quién va
 * dirigida en la institución (no es el jefe directo, que la empresa asigna después), el lugar
 * de emisión y el cargo con el que firma quien llena el formulario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table): void {
            $table->string('destinatario_tratamiento', 50)->nullable()->after('nombre_institucion');
            $table->string('destinatario_nombre')->nullable()->after('destinatario_tratamiento');
            $table->string('destinatario_cargo')->nullable()->after('destinatario_nombre');
            $table->string('solicitud_lugar')->nullable()->after('destinatario_cargo');
            $table->string('solicitud_firmante_cargo')->nullable()->after('solicitud_lugar');
        });
    }

    public function down(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table): void {
            $table->dropColumn([
                'destinatario_tratamiento',
                'destinatario_nombre',
                'destinatario_cargo',
                'solicitud_lugar',
                'solicitud_firmante_cargo',
            ]);
        });
    }
};
