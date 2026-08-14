<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveles_academicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        $now = now();
        DB::table('niveles_academicos')->insert([
            ['nombre' => 'Estudiante de grado', 'activo' => true, 'orden' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Maestría', 'activo' => true, 'orden' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Doctorado/Posgrado', 'activo' => true, 'orden' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('niveles_academicos');
    }
};
