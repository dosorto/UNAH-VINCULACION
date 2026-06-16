<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $catalogos = [
            'tipo_accion_enf' => [
                'Certificado universitario',
            ],
            'grado_academico' => [
                'Titulo de Educacion Media',
                'Titulo Universitario',
                'Acreditar experiencia comprobada en el area',
            ],
            'tipo_certificado' => [
                'Basico',
                'Avanzado',
            ],
        ];

        foreach ($catalogos as $tipo => $valores) {
            foreach ($valores as $index => $nombre) {
                DB::table('enf_catalogos')->updateOrInsert(
                    ['tipo' => $tipo, 'codigo' => Str::upper(Str::slug($nombre, '_'))],
                    [
                        'nombre' => $nombre,
                        'descripcion' => null,
                        'activo' => true,
                        'orden' => $this->ordenCatalogo($tipo, $index),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Catalog values can be referenced by records; keep them on rollback.
    }

    private function ordenCatalogo(string $tipo, int $index): int
    {
        if ($tipo === 'tipo_accion_enf') {
            return 1;
        }

        return $index + 1;
    }
};
