<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VinculacionTiposAccionSeeder extends Seeder
{
    public function run(): void
    {
        $tiposAccion = [
            [
                'codigo' => 'DESARROLLO_LOCAL_REGIONAL',
                'nombre' => 'Proyectos de desarrollo local y regional',
                'descripcion' => 'Acciones de vinculación diseñadas para impulsar el crecimiento social y económico en comunidades o regiones específicas. Pueden enfocarse en áreas como la sostenibilidad, la innovación o la mejora de la calidad de vida. Son acciones de mediano y largo plazo, aunque pueden ejecutarse de forma unidisciplinar, las acciones integrales se realizan de forma multi y trans disciplinar.',
                'badge' => 'Vinculación Territorial',
                'icono' => 'documento',
                'activo' => true,
                'orden' => 1,
            ],
            [
                'codigo' => 'ASESORAMIENTO_CONSULTORIA',
                'nombre' => 'Asesoramiento y consultoría',
                'descripcion' => 'Son las acciones de vinculación que tienen como propósito brindar experticia académica y técnica para resolver problemas específicos o mejorar procesos para el fortalecimiento de organizaciones, empresas, instituciones públicas o comunidades.',
                'badge' => 'Proximamente',
                'icono' => 'asesoria',
                'activo' => false,
                'orden' => 2,
            ],
            [
                'codigo' => 'PRESTACION_SERVICIOS_TECNICOS',
                'nombre' => 'Prestación de servicios técnicos, de infraestructura y de servicios académicos',
                'descripcion' => 'Acciones de vinculación a través del cual se ofrecen servicios especializados a entidades externas, generalmente remunerados, que utilizan la infraestructura, laboratorios o conocimiento de la universidad (servicios académicos).',
                'badge' => 'Proximamente',
                'icono' => 'reloj',
                'activo' => false,
                'orden' => 3,
            ],
            [
                'codigo' => 'ALINEAMIENTO_CURRICULAR',
                'nombre' => 'Alineamiento curricular (prácticas en asignaturas, tesis de posgrados)',
                'descripcion' => 'Integrar actividades prácticas de vinculación, como estudios de caso o desarrollo de proyectos, con organizaciones reales, directamente en el contenido de las asignaturas o en las tesis de posgrados.',
                'badge' => 'Proximamente',
                'icono' => 'megafono',
                'activo' => false,
                'orden' => 4,
            ],
            [
                'codigo' => 'PRACTICAS_EDUCATIVAS_INTEGRALES',
                'nombre' => 'Prácticas educativas integrales',
                'descripcion' => 'Son acciones de vinculación que tienen el propósito de colocar a los estudiantes en entornos reales fuera del campus para que apliquen sus conocimientos y desarrollen habilidades en el contexto profesional o social como parte de la práctica evaluable de una asignatura. Para el desarrollo de estas acciones, hay una línea base y se realizan con la participación de la comunidad.',
                'badge' => 'Proximamente',
                'icono' => 'graduacion',
                'activo' => false,
                'orden' => 5,
            ],
        ];

        foreach ($tiposAccion as $tipoAccion) {
            DB::table('vinculacion_tipos_accion')->updateOrInsert(
                ['codigo' => $tipoAccion['codigo']],
                $tipoAccion
            );
        }

    }
}
