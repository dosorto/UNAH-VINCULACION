<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->longText('participacion_unah')->nullable()->after('descripcion_participantes');
            $table->longText('participacion_contraparte')->nullable()->after('participacion_unah');
            $table->longText('participacion_comunidad')->nullable()->after('participacion_contraparte');
        });
    }

    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->dropColumn(['participacion_unah', 'participacion_contraparte', 'participacion_comunidad']);
        });
    }
};
