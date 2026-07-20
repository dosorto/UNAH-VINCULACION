<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('integraciones_api', function (Blueprint $table) {
            $table->id(); $table->string('nombre'); $table->string('codigo')->unique(); $table->string('tipo_perfil');
            $table->string('base_url'); $table->string('ruta_busqueda')->default(''); $table->string('metodo_http', 10)->default('GET');
            $table->string('tipo_autenticacion', 30)->default('NINGUNA'); $table->text('token_encriptado')->nullable();
            $table->text('usuario_api_encriptado')->nullable(); $table->text('password_api_encriptado')->nullable();
            $table->string('api_key_header')->nullable(); $table->string('parametro_busqueda')->default('numero_cuenta');
            $table->unsignedSmallInteger('timeout_segundos')->default(15); $table->text('headers_json')->nullable();
            $table->string('ruta_respuesta')->nullable(); $table->json('mapeo_campos_json'); $table->boolean('activo')->default(false);
            $table->timestamp('ultima_prueba_at')->nullable(); $table->boolean('ultima_prueba_exitosa')->nullable();
            $table->unsignedSmallInteger('ultimo_codigo_http')->nullable(); $table->unsignedInteger('ultima_duracion_ms')->nullable(); $table->string('ultimo_mensaje')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(); $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('integraciones_api'); }
};
