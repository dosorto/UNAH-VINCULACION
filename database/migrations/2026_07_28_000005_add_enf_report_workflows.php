<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enf_revisiones', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_revisiones', 'proceso')) {
                $table->string('proceso', 40)->default('INSCRIPCION')->after('enf_accion_id')->index();
            }
        });

        Schema::table('enf_informes_finales', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_informes_finales', 'revision_ciclo')) {
                $table->unsignedInteger('revision_ciclo')->default(0)->after('estado');
            }
            if (! Schema::hasColumn('enf_informes_finales', 'archivo_pdf')) {
                $table->string('archivo_pdf', 500)->nullable()->after('revision_ciclo');
            }
            if (! Schema::hasColumn('enf_informes_finales', 'fecha_envio')) {
                $table->timestamp('fecha_envio')->nullable()->after('archivo_pdf');
            }
            if (! Schema::hasColumn('enf_informes_finales', 'enviado_por_usuario_id')) {
                $table->foreignId('enviado_por_usuario_id')->nullable()->after('fecha_envio')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('enf_informes_finales', 'observaciones_revision')) {
                $table->text('observaciones_revision')->nullable()->after('enviado_por_usuario_id');
            }
        });

        Schema::create('enf_informes_intermedios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->string('archivo_pdf', 500)->nullable();
            $table->string('nombre_original', 220)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->string('hash_sha256', 64)->nullable();
            $table->string('estado', 40)->default('BORRADOR')->index();
            $table->unsignedInteger('revision_ciclo')->default(0);
            $table->foreignId('subido_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('enviado_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_carga')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('observaciones_revision')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('enf_accion_id', 'uq_enf_informes_intermedios_accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enf_informes_intermedios');

        Schema::table('enf_informes_finales', function (Blueprint $table) {
            foreach (['observaciones_revision', 'enviado_por_usuario_id', 'fecha_envio', 'archivo_pdf', 'revision_ciclo'] as $column) {
                if (Schema::hasColumn('enf_informes_finales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('enf_revisiones', function (Blueprint $table) {
            if (Schema::hasColumn('enf_revisiones', 'proceso')) {
                $table->dropColumn('proceso');
            }
        });
    }
};
