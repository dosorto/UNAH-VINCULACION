<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enf_equipo', function (Blueprint $table) {
            $table->string('numero_empleado', 80)->nullable()->after('nombre_completo');
            $table->string('correo', 180)->nullable()->after('numero_empleado');
            $table->string('celular', 80)->nullable()->after('correo');
            $table->string('categoria', 120)->nullable()->after('celular');
            $table->string('departamento', 180)->nullable()->after('categoria');
            $table->string('jornada_laboral', 120)->nullable()->after('departamento');
            $table->string('profesion', 180)->nullable()->after('jornada_laboral');
            $table->string('nacionalidad', 120)->nullable()->after('profesion');
        });

        Schema::table('enf_contrapartes', function (Blueprint $table) {
            $table->boolean('tiene_contraparte')->default(false)->after('instrumento_alianza_id');
            $table->string('cargo_contacto', 180)->nullable()->after('representante');
            $table->text('direccion')->nullable()->after('correo');
        });

        Schema::table('enf_practicas_asignatura', function (Blueprint $table) {
            $table->string('codigo_asignatura', 80)->nullable()->after('carrera_id');
            $table->string('nombre_asignatura', 220)->nullable()->after('codigo_asignatura');
            $table->string('periodo_academico_texto', 120)->nullable()->after('nombre_asignatura');
            $table->unsignedInteger('matricula_hombres')->default(0)->after('cantidad_docentes');
            $table->unsignedInteger('matricula_mujeres')->default(0)->after('matricula_hombres');
        });

        Schema::table('enf_cronograma', function (Blueprint $table) {
            $table->string('producto', 250)->nullable()->after('actividad');
            $table->string('responsable_texto', 180)->nullable()->after('responsable_empleado_id');
            $table->unsignedInteger('horas_requeridas')->default(0)->after('responsable_texto');
        });
    }

    public function down(): void
    {
        Schema::table('enf_cronograma', function (Blueprint $table) {
            $table->dropColumn(['producto', 'responsable_texto', 'horas_requeridas']);
        });

        Schema::table('enf_practicas_asignatura', function (Blueprint $table) {
            $table->dropColumn([
                'codigo_asignatura',
                'nombre_asignatura',
                'periodo_academico_texto',
                'matricula_hombres',
                'matricula_mujeres',
            ]);
        });

        Schema::table('enf_contrapartes', function (Blueprint $table) {
            $table->dropColumn(['tiene_contraparte', 'cargo_contacto', 'direccion']);
        });

        Schema::table('enf_equipo', function (Blueprint $table) {
            $table->dropColumn([
                'numero_empleado',
                'correo',
                'celular',
                'categoria',
                'departamento',
                'jornada_laboral',
                'profesion',
                'nacionalidad',
            ]);
        });
    }
};
