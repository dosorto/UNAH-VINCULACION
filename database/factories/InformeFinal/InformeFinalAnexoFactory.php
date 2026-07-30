<?php

namespace Database\Factories\InformeFinal;

use App\Models\InformeFinal\InformeFinalAnexo;
use Illuminate\Database\Eloquent\Factories\Factory;

class InformeFinalAnexoFactory extends Factory
{
    protected $model = InformeFinalAnexo::class;

    public function definition(): array
    {
        return [
            'tipo' => 'otros',
            'categoria' => 'documento_general',
            'archivo' => 'informes-finales/pendiente/documentos/anexo.pdf',
            'nombre_archivo' => 'anexo.pdf',
            'origen' => 'INFORME',
            'orden' => 0,
        ];
    }
}
