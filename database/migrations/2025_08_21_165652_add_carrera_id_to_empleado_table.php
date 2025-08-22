<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empleado', function (Blueprint $table) {
            $table->unsignedBigInteger('carrera_id')->nullable()->after('departamento_academico_id');
            // Si quieres agregar la relación, puedes agregar el foreign key:
            // $table->foreign('carrera_id')->references('id')->on('carrera');
        });
    }

    public function down(): void
    {
        Schema::table('empleado', function (Blueprint $table) {
            $table->dropColumn('carrera_id');
        });
    }
};