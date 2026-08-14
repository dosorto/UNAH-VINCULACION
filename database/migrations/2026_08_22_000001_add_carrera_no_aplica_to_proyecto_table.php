<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->boolean('carrera_no_aplica')->default(false)->after('lineas_investigacion_academica');
        });
    }

    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->dropColumn('carrera_no_aplica');
        });
    }
};
