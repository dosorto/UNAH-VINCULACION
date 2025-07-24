<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instrumento_formalizacion', function (Blueprint $table) {
            $table->enum('tipo_documento', [
                'carta_formal_solicitud',
                'carta_intenciones', 
                'convenio_marco'
            ])->nullable()->after('entidad_contraparte_id');
        });
    }

    public function down(): void
    {
        Schema::table('instrumento_formalizacion', function (Blueprint $table) {
            $table->dropColumn('tipo_documento');
        });
    }
};
