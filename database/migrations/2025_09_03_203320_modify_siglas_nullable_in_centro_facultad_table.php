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
        Schema::table('centro_facultad', function (Blueprint $table) {
            $table->string('siglas')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('centro_facultad', function (Blueprint $table) {
            $table->string('siglas')->nullable(false)->change();
        });
    }
};
