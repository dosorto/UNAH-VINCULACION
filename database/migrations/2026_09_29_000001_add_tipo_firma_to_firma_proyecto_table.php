<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las firmas nuevas del FORM-DVUS-013 distinguen la firma inicial del
     * creador de las firmas por etapa. La tabla histórica no tenía esta
     * columna, por lo que debe permitir NULL para conservar sus registros.
     */
    public function up(): void
    {
        Schema::table('firma_proyecto', function (Blueprint $table): void {
            $table->string('tipo_firma', 30)->nullable()->after('estado_revision');
            $table->index(
                ['firmable_type', 'firmable_id', 'tipo_firma'],
                'firma_proyecto_firmable_tipo_firma_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('firma_proyecto', function (Blueprint $table): void {
            $table->dropIndex('firma_proyecto_firmable_tipo_firma_idx');
            $table->dropColumn('tipo_firma');
        });
    }
};
