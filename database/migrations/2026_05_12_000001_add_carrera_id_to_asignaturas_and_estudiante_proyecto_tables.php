<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asignaturas') && !Schema::hasColumn('asignaturas', 'carrera_id')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->foreignId('carrera_id')
                    ->nullable()
                    ->after('nombre')
                    ->constrained('carrera')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('estudiante_proyecto') && !Schema::hasColumn('estudiante_proyecto', 'carrera_id')) {
            Schema::table('estudiante_proyecto', function (Blueprint $table) {
                $table->foreignId('carrera_id')
                    ->nullable()
                    ->after('tipo_participacion_estudiante')
                    ->constrained('carrera')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('estudiante_proyecto') && Schema::hasColumn('estudiante_proyecto', 'carrera_id')) {
            Schema::table('estudiante_proyecto', function (Blueprint $table) {
                $table->dropConstrainedForeignId('carrera_id');
            });
        }

        if (Schema::hasTable('asignaturas') && Schema::hasColumn('asignaturas', 'carrera_id')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('carrera_id');
            });
        }
    }
};
