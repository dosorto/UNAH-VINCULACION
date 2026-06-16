<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enf_acciones', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_acciones', 'unidad_academica_responsable_texto')) {
                $table->string('unidad_academica_responsable_texto', 220)->nullable()->after('carrera_id');
            }

            if (! Schema::hasColumn('enf_acciones', 'escuela_departamento_texto')) {
                $table->string('escuela_departamento_texto', 220)->nullable()->after('unidad_academica_responsable_texto');
            }

            if (! Schema::hasColumn('enf_acciones', 'carga_horaria_creditos')) {
                $table->unsignedInteger('carga_horaria_creditos')->default(0)->after('total_horas');
            }

            if (! Schema::hasColumn('enf_acciones', 'impacto_esperado')) {
                $table->text('impacto_esperado')->nullable()->after('resumen');
            }
        });

        Schema::table('enf_lugares_ejecucion', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_lugares_ejecucion', 'aula')) {
                $table->string('aula', 80)->nullable()->after('nombre_lugar');
            }

            if (! Schema::hasColumn('enf_lugares_ejecucion', 'edificio')) {
                $table->string('edificio', 120)->nullable()->after('aula');
            }

            if (! Schema::hasColumn('enf_lugares_ejecucion', 'centro')) {
                $table->string('centro', 180)->nullable()->after('edificio');
            }

            if (! Schema::hasColumn('enf_lugares_ejecucion', 'descripcion_plataformas')) {
                $table->text('descripcion_plataformas')->nullable()->after('url_acceso');
            }
        });

        Schema::table('enf_certificados', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_certificados', 'codigo_certificado')) {
                $table->string('codigo_certificado', 120)->nullable()->after('nombre_certificado');
            }

            if (! Schema::hasColumn('enf_certificados', 'vigencia_certificado')) {
                $table->string('vigencia_certificado', 120)->nullable()->after('requisitos_emision');
            }

            if (! Schema::hasColumn('enf_certificados', 'fecha_emision_maxima')) {
                $table->date('fecha_emision_maxima')->nullable()->after('vigencia_certificado');
            }

            if (! Schema::hasColumn('enf_certificados', 'pac_certificado')) {
                $table->string('pac_certificado', 80)->nullable()->after('fecha_emision_maxima');
            }

            if (! Schema::hasColumn('enf_certificados', 'hora_inicio')) {
                $table->time('hora_inicio')->nullable()->after('pac_certificado');
            }

            if (! Schema::hasColumn('enf_certificados', 'hora_finalizacion')) {
                $table->time('hora_finalizacion')->nullable()->after('hora_inicio');
            }

            if (! Schema::hasColumn('enf_certificados', 'dias_imparticion')) {
                $table->json('dias_imparticion')->nullable()->after('hora_finalizacion');
            }
        });

        Schema::table('enf_certificado_carreras', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_certificado_carreras', 'nombre_carrera')) {
                $table->string('nombre_carrera', 220)->nullable()->after('centro_facultad_id');
            }

            if (! Schema::hasColumn('enf_certificado_carreras', 'acuerdo_consejo_universitario')) {
                $table->string('acuerdo_consejo_universitario', 180)->nullable()->after('nombre_carrera');
            }
        });

        Schema::table('enf_espacios_aprendizaje', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_espacios_aprendizaje', 'codigo')) {
                $table->string('codigo', 80)->nullable()->after('nombre');
            }

            if (! Schema::hasColumn('enf_espacios_aprendizaje', 'creditos')) {
                $table->unsignedInteger('creditos')->default(0)->after('codigo');
            }

            if (! Schema::hasColumn('enf_espacios_aprendizaje', 'horas')) {
                $table->unsignedInteger('horas')->default(0)->after('creditos');
            }
        });

        Schema::table('enf_equipo', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_equipo', 'identidad')) {
                $table->string('identidad', 80)->nullable()->after('numero_empleado');
            }

            if (! Schema::hasColumn('enf_equipo', 'espacio_aprendizaje')) {
                $table->string('espacio_aprendizaje', 220)->nullable()->after('rol');
            }

            if (! Schema::hasColumn('enf_equipo', 'ultimo_titulo')) {
                $table->string('ultimo_titulo', 180)->nullable()->after('profesion');
            }

            if (! Schema::hasColumn('enf_equipo', 'pais_procedencia')) {
                $table->string('pais_procedencia', 120)->nullable()->after('nacionalidad');
            }

            if (! Schema::hasColumn('enf_equipo', 'universidad_procedencia')) {
                $table->string('universidad_procedencia', 180)->nullable()->after('pais_procedencia');
            }

            if (! Schema::hasColumn('enf_equipo', 'perfil_docente')) {
                $table->string('perfil_docente', 80)->nullable()->after('universidad_procedencia');
            }

            if (! Schema::hasColumn('enf_equipo', 'carga_academica_pac')) {
                $table->boolean('carga_academica_pac')->default(false)->after('perfil_docente');
            }

            if (! Schema::hasColumn('enf_equipo', 'contratacion_jornada_contraria')) {
                $table->boolean('contratacion_jornada_contraria')->default(false)->after('carga_academica_pac');
            }
        });

        Schema::table('enf_firmas', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_firmas', 'nombre_firmante')) {
                $table->string('nombre_firmante', 220)->nullable()->after('rol_firma');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enf_firmas', function (Blueprint $table) {
            if (Schema::hasColumn('enf_firmas', 'nombre_firmante')) {
                $table->dropColumn('nombre_firmante');
            }
        });

        Schema::table('enf_equipo', function (Blueprint $table) {
            foreach ([
                'identidad',
                'espacio_aprendizaje',
                'ultimo_titulo',
                'pais_procedencia',
                'universidad_procedencia',
                'perfil_docente',
                'carga_academica_pac',
                'contratacion_jornada_contraria',
            ] as $column) {
                if (Schema::hasColumn('enf_equipo', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('enf_espacios_aprendizaje', function (Blueprint $table) {
            foreach (['codigo', 'creditos', 'horas'] as $column) {
                if (Schema::hasColumn('enf_espacios_aprendizaje', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('enf_certificado_carreras', function (Blueprint $table) {
            foreach (['nombre_carrera', 'acuerdo_consejo_universitario'] as $column) {
                if (Schema::hasColumn('enf_certificado_carreras', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('enf_certificados', function (Blueprint $table) {
            foreach ([
                'codigo_certificado',
                'vigencia_certificado',
                'fecha_emision_maxima',
                'pac_certificado',
                'hora_inicio',
                'hora_finalizacion',
                'dias_imparticion',
            ] as $column) {
                if (Schema::hasColumn('enf_certificados', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('enf_lugares_ejecucion', function (Blueprint $table) {
            foreach (['aula', 'edificio', 'centro', 'descripcion_plataformas'] as $column) {
                if (Schema::hasColumn('enf_lugares_ejecucion', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('enf_acciones', function (Blueprint $table) {
            foreach ([
                'unidad_academica_responsable_texto',
                'escuela_departamento_texto',
                'carga_horaria_creditos',
                'impacto_esperado',
            ] as $column) {
                if (Schema::hasColumn('enf_acciones', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
