<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->string('tipo_etapa', 40)->default('REVISION')->after('nombre');
            $table->foreignId('rol_revisor_id')->nullable()->constrained('roles')->nullOnDelete()->after('tipo_etapa');
            $table->foreignId('usuario_responsable_id')->nullable()->constrained('users')->nullOnDelete()->after('rol_revisor_id');
        });

        Schema::table('programa_revisiones', function (Blueprint $table) {
            $table->foreignId('flujo_aprobacion_etapa_id')
                ->nullable()
                ->after('programa_certificacion_id')
                ->constrained('flujos_aprobacion_etapas')
                ->nullOnDelete();
            $table->foreignId('responsable_usuario_id')
                ->nullable()
                ->after('rol_requerido')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('programa_revisiones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsable_usuario_id');
            $table->dropConstrainedForeignId('flujo_aprobacion_etapa_id');
        });

        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('usuario_responsable_id');
            $table->dropConstrainedForeignId('rol_revisor_id');
            $table->dropColumn('tipo_etapa');
        });
    }
};
