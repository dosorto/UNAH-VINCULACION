<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Curso', 'Taller'] as $index => $nombre) {
            DB::table('enf_catalogos')->updateOrInsert(
                [
                    'tipo' => 'tipo_accion_enf',
                    'codigo' => Str::upper(Str::slug($nombre, '_')),
                ],
                [
                    'nombre' => $nombre,
                    'descripcion' => null,
                    'activo' => true,
                    'orden' => 7 + $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Se conservan porque pueden quedar referenciados por acciones registradas.
    }
};
