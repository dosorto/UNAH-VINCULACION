<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entidad_contraparte', function (Blueprint $table) {
            // Agregar el campo tipo_entidad
            $table->enum('tipo_entidad', [
                'internacional',
                'gobierno_nacional',
                'gobierno_municipal',
                'ong',
                'sociedad_civil',
                'sector_privado'
            ])->nullable()->after('nombre');
            $table->string('cargo_contacto')->nullable()->after('nombre_contacto');
            
            // Eliminar el campo es_internacional si existe
            $table->dropColumn('es_internacional');
        });
    }

    public function down(): void
    {
        Schema::table('entidad_contraparte', function (Blueprint $table) {
            // Restaurar el campo es_internacional
            $table->boolean('es_internacional')->default(false)->after('nombre');
            
            // Eliminar el campo tipo_entidad
            $table->dropColumn('tipo_entidad');
        });
    }
};
