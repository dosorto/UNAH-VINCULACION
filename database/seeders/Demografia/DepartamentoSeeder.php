<?php

namespace Database\Seeders\Demografia;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Pais;

class DepartamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $honduras = Pais::where('nombre', 'Honduras')->first();

        // Verifica si se encontró el país
        if ($honduras) {
            $departamentos = [
                ['nombre' => 'Atlántida', 'codigo_departamento' => '01'],
                ['nombre' => 'Colón', 'codigo_departamento' => '02'],
                ['nombre' => 'Comayagua', 'codigo_departamento' => '03'],
                ['nombre' => 'Copán', 'codigo_departamento' => '04'],
                ['nombre' => 'Cortés', 'codigo_departamento' => '05'],
                ['nombre' => 'Choluteca', 'codigo_departamento' => '06'],
                ['nombre' => 'El Paraíso', 'codigo_departamento' => '07'],
                ['nombre' => 'Francisco Morazán', 'codigo_departamento' => '08'],
                ['nombre' => 'Gracias a Dios', 'codigo_departamento' => '09'],
                ['nombre' => 'Intibucá', 'codigo_departamento' => '10'],
                ['nombre' => 'Islas de la Bahía', 'codigo_departamento' => '11'],
                ['nombre' => 'La Paz', 'codigo_departamento' => '12'],
                ['nombre' => 'Lempira', 'codigo_departamento' => '13'],
                ['nombre' => 'Ocotepeque', 'codigo_departamento' => '14'],
                ['nombre' => 'Olancho', 'codigo_departamento' => '15'],
                ['nombre' => 'Santa Bárbara', 'codigo_departamento' => '16'],
                ['nombre' => 'Valle', 'codigo_departamento' => '17'],
                ['nombre' => 'Yoro', 'codigo_departamento' => '18'],
            ];

            // Inserta cada departamento asociado al país
            foreach ($departamentos as $departamento) {
                Departamento::create([
                    'pais_id' => $honduras->id,
                    'nombre' => $departamento['nombre'],
                    'codigo_departamento' => $departamento['codigo_departamento'],
                ]);
            }
        } else {
            echo "El país Honduras no se encontró en la base de datos.\n";
        }
    }
}
