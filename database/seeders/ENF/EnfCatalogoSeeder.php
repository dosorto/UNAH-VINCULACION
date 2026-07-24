<?php

namespace Database\Seeders\ENF;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnfCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $catalogos = [
            'tipo_accion_enf' => [
                'Certificado universitario',
                'Proyecto de educacion continua',
                'Programa de educacion continua',
                'Diplomado',
                'Curso',
                'Taller',
                'Congreso',
                'Seminario',
            ],
            'perfil_participante' => [
                'Egresados UNAH',
                'Funcionarios publicos',
                'Estudiantes universitarios',
                'Empresa privada de servicios',
                'Sociedad civil',
                'Lideres comunitarios',
                'ONG',
                'Profesionales universitarios otros IES',
                'Sector productivo',
                'Academicos',
            ],
            'rango_edad' => [
                '14-18',
                '19-25',
                '26-40',
                '41-55',
                '56-70',
                'Mayores de 70',
            ],
            'condicion_social' => [
                'Mestizos',
                'Grupos etnicos',
                'Poblacion vulnerable',
                'Personas con discapacidad',
                'Desplazados por violencia',
                'Otro',
            ],
            'plataforma' => [
                'Teams',
                'Zoom',
                'Meet',
                'Webex',
                'Campus Virtual UNAH',
                'Moodle',
                'Classroom Google',
                'Otro',
            ],
            'antecedente' => [
                'Iniciativa de la unidad academica',
                'Solicitud externa privada',
                'Solicitud de Secretaria de Estado',
                'Solicitud de gobierno local',
                'Alianza con otras universidades',
                'Solicitud de ONG',
                'Solicitud de patronatos',
                'Solicitud de sector financiero',
                'Solicitud de sector productivo',
                'Otros',
            ],
            'difusion' => [
                'Sitio web institucional',
                'Redes sociales',
                'Correo institucional',
                'Radio o television',
                'Afiches o brochures',
                'Otro',
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
            'tipo_contraparte' => [
                'Secretaria de Estado',
                'Gobierno Municipal',
                'Sector productivo',
                'Entidades financieras',
                'Sector privado de servicios',
                'Organizaciones gremiales',
                'Sociedad civil organizada',
                'Sector academico',
                'Organismos internacionales',
                'Unidad de la UNAH',
            ],
            'instrumento_alianza' => [
                'Carta formal de solicitud',
                'Carta de intenciones con la UNAH',
                'Convenio marco con la UNAH',
            ],
            'figura_acreditacion' => [
                'Certificado de participacion',
                'Certificado de aprobacion',
                'Constancia',
                'Microcredencial',
            ],
        ];

        foreach ($catalogos as $tipo => $valores) {
            if ($valores === []) {
                DB::table('enf_catalogos')->updateOrInsert(
                    ['tipo' => $tipo, 'codigo' => 'PENDIENTE'],
                    [
                        'nombre' => 'Pendiente de definir',
                        'descripcion' => 'Valor inicial para completar el catalogo.',
                        'activo' => true,
                        'orden' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                continue;
            }

            foreach (array_values($valores) as $index => $nombre) {
                DB::table('enf_catalogos')->updateOrInsert(
                    ['tipo' => $tipo, 'codigo' => Str::upper(Str::slug($nombre, '_'))],
                    [
                        'nombre' => $nombre,
                        'descripcion' => null,
                        'activo' => true,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
