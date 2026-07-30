<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrante_internacional', function (Blueprint $table) {
            $table->string('rtn', 50)->nullable()->change();
        });

        Schema::table('entidad_contraparte', function (Blueprint $table) {
            $table->string('rtn', 50)->nullable()->change();
        });

        Schema::table('entidad_contraparte_proyecto', function (Blueprint $table) {
            $table->string('rtn', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('integrante_internacional', function (Blueprint $table) {
            $table->string('rtn', 14)->nullable()->change();
        });

        Schema::table('entidad_contraparte', function (Blueprint $table) {
            $table->string('rtn', 20)->nullable()->change();
        });

        Schema::table('entidad_contraparte_proyecto', function (Blueprint $table) {
            $table->string('rtn', 20)->nullable()->change();
        });
    }
};
