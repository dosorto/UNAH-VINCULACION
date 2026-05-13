<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_programa', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->unsignedInteger('horas_minimas')->nullable();
            $table->unsignedInteger('horas_maximas')->nullable();
            $table->string('plantilla_docx_path', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $this->addAuditColumns($table);
        });

        Schema::create('programas_certificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_facultad_id')->constrained('centro_facultad')->cascadeOnDelete();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 200);
            $table->string('tipo_programa', 120)->nullable();
            $table->foreignId('tipo_programa_id')->nullable()->constrained('tipos_programa')->nullOnDelete();
            $table->unsignedInteger('horas_maximas_programa')->default(0);
            $table->unsignedInteger('version_actual')->default(1);
            $table->text('descripcion')->nullable();
            $table->smallInteger('estado')->default(1);
            $table->string('estado_flujo', 30)->default('ELABORACION');
            $table->unsignedInteger('revision_ciclo')->default(0);
            $table->timestamp('enviado_revision_en')->nullable();
            $table->text('observaciones_revision')->nullable();
            $table->unsignedBigInteger('subsanacion_revision_id')->nullable();
            $table->unsignedInteger('subsanacion_etapa_orden')->nullable();
            $table->string('subsanacion_etapa_nombre', 150)->nullable();
            $table->timestamp('subsanacion_devuelto_en')->nullable();
            $table->foreignId('flujo_aprobacion_id')->nullable()->constrained('flujos_aprobacion')->nullOnDelete();
            $table->foreignId('creado_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modificado_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $this->addAuditColumns($table);
        });

        Schema::create('versiones_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_certificacion_id')->constrained('programas_certificacion')->cascadeOnDelete();
            $table->unsignedInteger('numero_version');
            $table->string('estado', 30)->default('ELABORACION');
            $table->boolean('vigente')->default(false);
            $table->timestamp('publicado_en')->nullable();
            $table->foreignId('publicado_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notas', 800)->nullable();
            $table->json('datos_programa')->nullable();
            $table->json('centros_facultad')->nullable();
            $table->json('asignaturas')->nullable();
            $table->timestamps();
            $this->addAuditColumns($table);
            $table->unique(['programa_certificacion_id', 'numero_version'], 'versiones_programa_numero_unique');
        });

        Schema::create('programas_centros_facultad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_certificacion_id')->constrained('programas_certificacion')->cascadeOnDelete();
            $table->foreignId('centro_facultad_id')->constrained('centro_facultad')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $this->addAuditColumns($table);
            $table->unique(['programa_certificacion_id', 'centro_facultad_id'], 'programa_centro_facultad_unique');
        });

        Schema::create('programas_asignaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_certificacion_id')->constrained('programas_certificacion')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->unsignedInteger('orden')->nullable();
            $table->boolean('es_obligatoria')->default(true);
            $table->timestamps();
            $this->addAuditColumns($table);
            $table->unique(['programa_certificacion_id', 'asignatura_id'], 'programa_asignatura_unique');
        });

        Schema::create('ediciones_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_certificacion_id')->constrained('programas_certificacion')->cascadeOnDelete();
            $table->foreignId('centro_facultad_id')->constrained('centro_facultad')->cascadeOnDelete();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->string('codigo_edicion', 50)->nullable();
            $table->unsignedInteger('numero_edicion')->default(1);
            $table->unsignedInteger('cupo_maximo')->default(0);
            $table->date('inicio')->nullable();
            $table->date('fin')->nullable();
            $table->smallInteger('estado')->default(1);
            $table->foreignId('creado_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $this->addAuditColumns($table);
        });

        Schema::create('solicitudes_edicion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edicion_programa_id')->constrained('ediciones_programa')->cascadeOnDelete();
            $table->foreignId('solicitado_por_usuario_id')->constrained('users')->cascadeOnDelete();
            $table->smallInteger('estado')->default(1);
            $table->string('motivo', 800)->nullable();
            $table->timestamp('solicitado_en')->useCurrent();
            $table->timestamp('resuelto_en')->nullable();
            $table->foreignId('resuelto_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $this->addAuditColumns($table);
        });

        Schema::create('solicitudes_cambio_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_certificacion_id')->constrained('programas_certificacion')->cascadeOnDelete();
            $table->foreignId('solicitado_por_usuario_id')->constrained('users')->cascadeOnDelete();
            $table->smallInteger('estado')->default(1);
            $table->string('motivo', 800)->nullable();
            $table->timestamp('solicitado_en')->useCurrent();
            $table->timestamp('resuelto_en')->nullable();
            $table->foreignId('resuelto_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $this->addAuditColumns($table);
        });

        Schema::create('programa_revisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_certificacion_id')->constrained('programas_certificacion')->cascadeOnDelete();
            $table->unsignedInteger('revision_ciclo')->default(1);
            $table->unsignedInteger('orden');
            $table->string('etapa_codigo', 50);
            $table->string('etapa_nombre', 150);
            $table->string('rol_requerido', 80)->nullable();
            $table->string('estado', 30)->default('PENDIENTE');
            $table->foreignId('asignado_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decidido_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->string('firma_nombre', 200)->nullable();
            $table->timestamp('firmado_en')->nullable();
            $table->timestamps();
            $this->addAuditColumns($table);

            $table->index(['programa_certificacion_id', 'revision_ciclo', 'orden'], 'programa_revisiones_ciclo_orden_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_revisiones');
        Schema::dropIfExists('solicitudes_cambio_programa');
        Schema::dropIfExists('solicitudes_edicion');
        Schema::dropIfExists('ediciones_programa');
        Schema::dropIfExists('programas_asignaturas');
        Schema::dropIfExists('programas_centros_facultad');
        Schema::dropIfExists('versiones_programa');
        Schema::dropIfExists('programas_certificacion');
        Schema::dropIfExists('tipos_programa');
    }

    private function addAuditColumns(Blueprint $table): void
    {
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->unsignedBigInteger('deleted_by')->nullable();
        $table->softDeletes();
    }
};
