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
                'codigo' => 'EDUCACION_NO_FORMAL',
                'nombre' => 'Educacion No Formal / Educacion Continua',
                'descripcion' => 'Registra diplomados, cursos, talleres, seminarios, conferencias, webinars, programas de educacion continua, certificados y microcertificados.',
                'badge' => 'Disponible',
                'icono' => 'asesoria',
                'activo' => true,
                'orden' => 2,
            ],
            [
                'codigo' => 'SEGUMIENTO_A_EGRESADOS',
                'nombre' => 'Seguimiento a egresados',
                'descripcion' => 'Son las acciones de vinculación que tienen como propósito brindar experticia académica y técnica para resolver problemas específicos o mejorar procesos para el fortalecimiento de organizaciones, empresas, instituciones públicas o comunidades.',
                'badge' => 'Proximamente',
                'icono' => 'asesoria',
                'activo' => false,
                'orden' => 3,
            ],
            [
                'codigo' => 'PRESTACION_SERVICIOS_TECNICOS',
                'nombre' => 'Prestación de servicios técnicos, de infraestructura y de servicios académicos',
                'descripcion' => 'Acciones de vinculación a través del cual se ofrecen servicios especializados a entidades externas, generalmente remunerados, que utilizan la infraestructura, laboratorios o conocimiento de la universidad (servicios académicos).',
                'badge' => 'Proximamente',
                'icono' => 'reloj',
                'activo' => false,
                'orden' => 4,
            ],
            [
                'codigo' => 'ALINEAMIENTO_CURRICULAR',
                'nombre' => 'Alineamiento curricular (prácticas en asignaturas, tesis de posgrados)',
                'descripcion' => 'Integrar actividades prácticas de vinculación, como estudios de caso o desarrollo de proyectos, con organizaciones reales, directamente en el contenido de las asignaturas o en las tesis de posgrados.',
                'badge' => 'Proximamente',
                'icono' => 'megafono',
                'activo' => false,
                'orden' => 5,
            ],
            [
                'codigo' => 'PRACTICAS_EDUCATIVAS_INTEGRALES',
                'nombre' => 'Prácticas educativas integrales',
                'descripcion' => 'Son acciones de vinculación que tienen el propósito de colocar a los estudiantes en entornos reales fuera del campus para que apliquen sus conocimientos y desarrollen habilidades en el contexto profesional o social como parte de la práctica evaluable de una asignatura. Para el desarrollo de estas acciones, hay una línea base y se realizan con la participación de la comunidad.',
                'badge' => 'Proximamente',
                'icono' => 'graduacion',
                'activo' => false,
                'orden' => 6,
            ],
            [
                'codigo' => 'PPS_VOLUNTARIADO_GESTION_RIESGO',
                'nombre' => 'PPS, Voluntariado y Gestión del Riesgo',
                'descripcion' => 'Acciones de práctica profesional supervisada, servicio social, voluntariado y gestión del riesgo.',
                'badge' => 'Disponible',
                'icono' => 'reloj',
                'activo' => true,
                'orden' => 6,
            ],
            [
                'codigo' => 'VOLUNTARIADO',
                'nombre' => 'Proyectos de Voluntariado Académico',
                'descripcion' => 'Registro de proyectos de voluntariado académico (FORM-DVUS-015): acciones de vinculación donde la comunidad universitaria participa de forma voluntaria en beneficio de comunidades, con marco lógico, equipo ejecutor, contraparte y presupuesto.',
                'badge' => 'Disponible',
                'icono' => 'graduacion',
                'activo' => true,
                'orden' => 7,
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
