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
        $addedCarreraId = false;
        $addedDepartamentoAcademicoId = false;

        if (!Schema::hasTable('asignaturas')) {
            Schema::create('asignaturas', function (Blueprint $table) {
                $table->id();
                $table->string('codigo')->nullable();
                $table->string('nombre');
                $table->unsignedBigInteger('carrera_id')->nullable()->index();
                $table->unsignedBigInteger('departamento_academico_id')->nullable()->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('asignaturas', function (Blueprint $table) use (&$addedCarreraId, &$addedDepartamentoAcademicoId) {
            if (!Schema::hasColumn('asignaturas', 'carrera_id')) {
                $table->unsignedBigInteger('carrera_id')->nullable()->after('nombre');
                $addedCarreraId = true;
            }

            if (!Schema::hasColumn('asignaturas', 'departamento_academico_id')) {
                $table->unsignedBigInteger('departamento_academico_id')->nullable()->after('carrera_id');
                $addedDepartamentoAcademicoId = true;
            }
        });

        if ($addedCarreraId) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->index('carrera_id');
            });
        }

        if ($addedDepartamentoAcademicoId) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->index('departamento_academico_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('asignaturas')) {
            return;
        }

        if (Schema::hasColumn('asignaturas', 'departamento_academico_id')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->dropIndex(['departamento_academico_id']);
                $table->dropColumn('departamento_academico_id');
            });
        }

        if (Schema::hasColumn('asignaturas', 'carrera_id')) {
            Schema::table('asignaturas', function (Blueprint $table) {
                $table->dropIndex(['carrera_id']);
                $table->dropColumn('carrera_id');
            });
        }

    }
};
