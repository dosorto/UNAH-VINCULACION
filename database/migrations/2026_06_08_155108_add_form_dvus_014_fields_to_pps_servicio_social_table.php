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
        $existingColumns = Schema::getColumnListing('pps_servicio_social');

        Schema::table('pps_servicio_social', function (Blueprint $table) use ($existingColumns) {
            if (!in_array('region', $existingColumns, true)) {
                $table->string('region')->nullable();
            }

            if (!in_array('pais', $existingColumns, true)) {
                $table->string('pais')->nullable();
            }

            if (!in_array('departamento_provincia', $existingColumns, true)) {
                $table->string('departamento_provincia')->nullable();
            }

            if (!in_array('pais_sede_principal', $existingColumns, true)) {
                $table->string('pais_sede_principal')->nullable();
            }

            if (!in_array('departamento_provincia_sede_principal', $existingColumns, true)) {
                $table->string('departamento_provincia_sede_principal')->nullable();
            }

            if (!in_array('municipio_sede_principal', $existingColumns, true)) {
                $table->string('municipio_sede_principal')->nullable();
            }

            if (!in_array('aldea_ciudad_sede_principal', $existingColumns, true)) {
                $table->string('aldea_ciudad_sede_principal')->nullable();
            }

            if (!in_array('horas_presenciales', $existingColumns, true)) {
                $table->unsignedInteger('horas_presenciales')->nullable();
            }

            if (!in_array('horas_teletrabajo', $existingColumns, true)) {
                $table->unsignedInteger('horas_teletrabajo')->nullable();
            }

            if (!in_array('descripcion_horas_tipo_pps_ss', $existingColumns, true)) {
                $table->text('descripcion_horas_tipo_pps_ss')->nullable();
            }

            if (!in_array('institucion_nacionalidad', $existingColumns, true)) {
                $table->string('institucion_nacionalidad')->nullable();
            }

            if (!in_array('institucion_pais', $existingColumns, true)) {
                $table->string('institucion_pais')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToDrop = [
            'region',
            'pais',
            'departamento_provincia',
            'pais_sede_principal',
            'departamento_provincia_sede_principal',
            'municipio_sede_principal',
            'aldea_ciudad_sede_principal',
            'horas_presenciales',
            'horas_teletrabajo',
            'descripcion_horas_tipo_pps_ss',
            'institucion_nacionalidad',
            'institucion_pais',
        ];

        $existingColumns = Schema::getColumnListing('pps_servicio_social');
        $existingColumnsToDrop = array_values(array_intersect($columnsToDrop, $existingColumns));

        if ($existingColumnsToDrop === []) {
            return;
        }

        Schema::table('pps_servicio_social', function (Blueprint $table) use ($existingColumnsToDrop) {
            $table->dropColumn($existingColumnsToDrop);
        });
    }
};
