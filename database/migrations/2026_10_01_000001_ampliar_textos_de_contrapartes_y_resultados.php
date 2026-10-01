<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los compromisos de la contraparte, el indicador y el medio de
     * verificación se capturan como texto libre, pero sus columnas eran
     * VARCHAR(255): un texto más largo hacía fallar el autoguardado del
     * FORM-DVUS-001 y se perdía lo capturado. Se amplían a TEXT conservando
     * la nulabilidad de cada columna.
     */
    public function up(): void
    {
        Schema::table('entidad_contraparte_proyecto', function (Blueprint $table): void {
            $table->text('descripcion_acuerdos')->nullable()->change();
        });

        Schema::table('resultado_esperado', function (Blueprint $table): void {
            $table->text('nombre_indicador')->change();
            $table->text('nombre_medio_verificacion')->change();
        });
    }

    /**
     * Con MySQL en modo estricto, revertir falla si ya se guardaron textos de
     * más de 255 caracteres; habría que recortarlos antes.
     */
    public function down(): void
    {
        Schema::table('resultado_esperado', function (Blueprint $table): void {
            $table->string('nombre_indicador')->change();
            $table->string('nombre_medio_verificacion')->change();
        });

        Schema::table('entidad_contraparte_proyecto', function (Blueprint $table): void {
            $table->string('descripcion_acuerdos')->nullable()->change();
        });
    }
};
