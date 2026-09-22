<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Añade el catálogo; no reescribe estados ni firmas históricas.
        foreach (['Borrador', 'En revision', 'Subsanacion', 'Registrado', 'Finalizado'] as $nombre) {
            if (! DB::table('tipo_estado')->where('nombre', $nombre)->whereNull('deleted_at')->exists()) {
                DB::table('tipo_estado')->insert(['nombre' => $nombre, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        \App\Support\Dashboard\EstadosProyecto::olvidar();
    }

    public function down(): void
    {
        // Los estados pueden estar referenciados por expedientes e historial.
    }
};
