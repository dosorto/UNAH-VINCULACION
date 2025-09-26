<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->enum('nombre', ['Primer Periodo', 'Segundo Periodo', 'Tercer Periodo', 'Primer Semestre', 'Segundo Semestre'])->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->string('nombre')->change();
        });
    }
};