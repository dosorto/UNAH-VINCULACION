<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integraciones_api', function (Blueprint $table): void {
            $table->string('api_key_ubicacion', 10)->default('HEADER')->after('api_key_header');
            $table->unsignedTinyInteger('reintentos')->default(0)->after('timeout_segundos');
            $table->boolean('verificar_ssl')->default(true)->after('reintentos');
            $table->boolean('protegida')->default(false)->after('activo');
            $table->unique(['tipo_perfil', 'nombre'], 'integraciones_api_perfil_nombre_unique');
        });

        DB::table('integraciones_api')->updateOrInsert(
            ['codigo' => 'estudiantes'],
            [
                'nombre' => 'Estudiantes',
                'tipo_perfil' => 'ESTUDIANTE',
                'base_url' => '',
                'ruta_busqueda' => '',
                'metodo_http' => 'GET',
                'tipo_autenticacion' => 'NINGUNA',
                'api_key_ubicacion' => 'HEADER',
                'parametro_busqueda' => 'numero_cuenta',
                'timeout_segundos' => 15,
                'reintentos' => 0,
                'verificar_ssl' => true,
                'headers_json' => null,
                'ruta_respuesta' => null,
                'mapeo_campos_json' => json_encode([
                    'numero_cuenta' => 'numeroCuenta',
                    'primer_nombre' => 'primer_nombre',
                    'segundo_nombre' => 'segundo_nombre',
                    'primer_apellido' => 'primer_apellido',
                    'segundo_apellido' => 'segundo_apellido',
                    'sexo' => 'sexo',
                    'correo_institucional' => 'correo',
                    'carrera_nombre' => 'carrera.nombre',
                ], JSON_THROW_ON_ERROR),
                'activo' => false,
                'protegida' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('integraciones_api')
            ->where('codigo', 'estudiantes')
            ->where('protegida', true)
            ->delete();

        Schema::table('integraciones_api', function (Blueprint $table): void {
            $table->dropUnique('integraciones_api_perfil_nombre_unique');
            $table->dropColumn([
                'api_key_ubicacion',
                'reintentos',
                'verificar_ssl',
                'protegida',
            ]);
        });
    }
};
