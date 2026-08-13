<?php

namespace Database\Seeders\UnidadAcademica;

use App\Models\Personal\CategoriaEmpleado;
use Illuminate\Database\Seeder;

class CategoriaEmpleadoSeeder extends Seeder
{
    public const CATEGORIAS = [
        ['nombre' => 'Auxiliar', 'descripcion' => 'Auxiliar de la universidad'],
        ['nombre' => 'Titular I', 'descripcion' => 'Titular I de la universidad'],
        ['nombre' => 'Titular II', 'descripcion' => 'Titular II de la universidad'],
        ['nombre' => 'Titular III', 'descripcion' => 'Titular III de la universidad'],
        ['nombre' => 'Titular IV', 'descripcion' => 'Titular IV de la universidad'],
        ['nombre' => 'Titular V', 'descripcion' => 'Titular V de la universidad'],
        ['nombre' => 'Profesores x hora', 'descripcion' => 'Profesores por hora'],
        ['nombre' => 'Profesores horarios', 'descripcion' => 'Profesores horarios'],
        ['nombre' => 'Administrativo', 'descripcion' => 'Personal administrativo'],
        ['nombre' => 'Servicios', 'descripcion' => 'Personal de servicios'],
        [
            'nombre' => 'Asistentes técnicos laboratorios / Instructores',
            'descripcion' => 'Asistentes técnicos de laboratorios e instructores',
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIAS as $datos) {
            $categoria = CategoriaEmpleado::withTrashed()->updateOrCreate(
                ['nombre' => $datos['nombre']],
                ['descripcion' => $datos['descripcion']],
            );

            if ($categoria->trashed()) {
                $categoria->restore();
            }
        }
    }
}
