<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('carrera', function (Blueprint $table) {
            $table->unsignedBigInteger('departamento_academico_id')->nullable()->after('facultad_centro_id');
            $table->foreign('departamento_academico_id')->references('id')->on('departamento_academico');
        });
    }

    public function down(): void
    {
        Schema::table('carrera', function (Blueprint $table) {
            $table->dropColumn('departamento_academico_id');
        });
    }
};