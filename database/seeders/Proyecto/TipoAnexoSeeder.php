<?php

namespace Database\Seeders\Proyecto;

use App\Models\Proyecto\TipoAnexo;
use Illuminate\Database\Seeder;

class TipoAnexoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TipoAnexo::TIPOS_BASE as $datos) {
            $tipo = TipoAnexo::withTrashed()->updateOrCreate(
                ['codigo' => $datos['codigo']],
                array_merge($datos, ['activo' => true])
            );

            if ($tipo->trashed()) {
                $tipo->restore();
            }
        }
    }
}
