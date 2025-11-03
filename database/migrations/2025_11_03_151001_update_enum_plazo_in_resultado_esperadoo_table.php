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
        Schema::table('resultado_esperado', function (Blueprint $table) {
            $table->enum('plazo', ['corto_plazo', 'largo_plazo', 'mediano_plazo'])->default('corto_plazo')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resultado_esperado', function (Blueprint $table) {
            $table->enum('plazo', ['corto_plazo', 'largo_plazo'])->default('corto_plazo')->change();
        });
    }
};