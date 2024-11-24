<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Seeder;
use App\Models\Proyecto\Proyecto;

class DataProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Proyecto::insert([
            [
                'nombre_proyecto' => 'Fortalecimiento del Acceso a la Justicia',
                // 'coordinador_id' => 2,
                'modalidad_id' => 1,
                // 'municipio_id' => 9,
                // 'departamento_id' => 2,
                //'ciudad_id' => ,
                'aldea' => 'El Banquito',
                'resumen' => 'Proyecto enfocado en mejorar el acceso a servicios legales en El Banquito.',
                'objetivo_general' => 'Aumentar el acceso a la justicia para comunidades vulnerables.',
                'objetivos_especificos' => 'Realizar talleres sobre derechos, proporcionar asesoría legal gratuita.',
                'fecha_inicio' => '2024-01-15',
                'fecha_finalizacion' => '2024-12-15',
                'evaluacion_intermedia' => '2024-05-15',
                'evaluacion_final' => '2024-11-15',
                'poblacion_participante' => 200,
                'modalidad_ejecucion' => 'Presencial',
                'resultados_esperados' => 'Incremento en el número de personas que conocen sus derechos.',
                'indicadores_medicion_resultados' => 'Cantidad de talleres realizados y asistencia.',
                'fecha_registro' => now(),
                'responsable_revision_id' => 2,
                'fecha_aprobacion' => now(),
                'numero_libro' => '456',
                'numero_tomo' => '789',
                'numero_folio' => '123',
                'numero_dictamen' => 'Aprobado',
            ],
        ]);
    }
}

