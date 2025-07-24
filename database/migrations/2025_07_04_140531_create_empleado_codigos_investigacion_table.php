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
        Schema::create('empleado_codigos_investigacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleado_id');
            $table->string('codigo_proyecto', 50); // Código correlativo del proyecto
            $table->string('nombre_proyecto'); // Nombre del proyecto (requerido)
            $table->text('descripcion')->nullable(); // Descripción adicional
            $table->string('rol_docente'); // Rol del docente en el proyecto (requerido)
            $table->year('año')->nullable(); // Año del proyecto
            $table->enum('estado_verificacion', ['pendiente', 'verificado', 'rechazado'])->default('pendiente');
            $table->text('observaciones_admin')->nullable(); // Observaciones del administrador
            $table->unsignedBigInteger('verificado_por')->nullable(); // ID del admin que verificó
            $table->timestamp('fecha_verificacion')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Claves foráneas
            $table->foreign('empleado_id')->references('id')->on('empleado')->onDelete('cascade');
            $table->foreign('verificado_por')->references('id')->on('empleado')->onDelete('set null');
            
            // Índices con nombres específicos
            $table->index(['empleado_id', 'estado_verificacion'], 'emp_cod_inv_emp_estado_idx');
            $table->unique(['codigo_proyecto', 'empleado_id'], 'emp_cod_inv_codigo_emp_unique'); // Un empleado no puede tener el mismo código duplicado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleado_codigos_investigacion');
    }
};
