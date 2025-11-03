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
        // Primero normalizar los datos existentes
    DB::table('periodos_academicos')->update(['nombre' => DB::raw("
        CASE 
            WHEN nombre LIKE '%Primer Periodo%' THEN 'Primer Periodo'
            WHEN nombre LIKE '%Segundo Periodo%' THEN 'Segundo Periodo'
            WHEN nombre LIKE '%Tercer Periodo%' THEN 'Tercer Periodo'
            WHEN nombre LIKE '%Primer Semestre%' THEN 'Primer Semestre'
            WHEN nombre LIKE '%Segundo Semestre%' THEN 'Segundo Semestre'
            ELSE 'Primer Periodo'
        END
    ")]);
    
    // Luego cambiar el tipo de columna
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