<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('carrera', function (Blueprint $table) {
            $table->unsignedBigInteger('facultad_centro_id')->nullable()->change();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('carrera', function (Blueprint $table) {
            $table->unsignedBigInteger('facultad_centro_id')->nullable(false)->change();
        });
    }
};
