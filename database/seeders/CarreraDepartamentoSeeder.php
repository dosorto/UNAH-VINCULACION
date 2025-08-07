<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;

class CarreraDepartamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar relaciones existentes
        DB::table('carrera_departamento_academico')->truncate();

        // Mapeo completo de carreras por centro con sus departamentos correspondientes
        $relacionesPorCentro = [
            // Centro 1: Ciencias Sociales
            1 => [
                'departamentos' => [], // No hay departamentos específicos en la lista
                'carreras' => [3, 12, 26, 31, 45, 76, 79] // Contaduría, Trabajo Social, Psicología, etc.
            ],
            
            // Centro 2: Química y Farmacia
            2 => [
                'departamentos' => [1, 2, 3], // Control Químico, Tecnología Farmacéutica, Química
                'carreras' => [8] // Química y Farmacia
            ],
            
            // Centro 3: Odontología
            3 => [
                'departamentos' => [4, 5, 6, 7], // Todos los departamentos de Odontología
                'carreras' => [9] // Cirujano Dentista
            ],
            
            // Centro 4: Ciencias Jurídicas
            4 => [
                'departamentos' => [8, 9, 10, 11, 12, 13, 14], // Todos los departamentos de Derecho
                'carreras' => [1] // Licenciatura en Ciencias Jurídicas
            ],
            
            // Centro 5: Ingeniería
            5 => [
                'departamentos' => [], // No hay departamentos específicos listados para ingeniería
                'carreras' => [13, 14, 15, 16, 17, 18, 19, 36, 58, 66] // Todas las ingenierías
            ],
            
            // Centro 6: Humanidades y Artes
            6 => [
                'departamentos' => [], // No hay departamentos específicos
                'carreras' => [20, 25, 34, 35, 38, 48, 49, 50, 63, 65] // Artes, Música, Diseño, etc.
            ],
            
            // Centro 7: Ciencias Espaciales
            7 => [
                'departamentos' => [15, 16, 17, 18], // Astronomía, Geográfica, Arqueoastronomía, Aeronáuticas
                'carreras' => [77, 80, 81, 82, 86] // Geografía, Agrimensura, Geomática, etc.
            ],
            
            // Centro 8: Ciencias Económicas, Administrativas y Contables
            8 => [
                'departamentos' => [19, 20, 21, 22, 23, 24, 25, 26, 27, 28], // Todos los departamentos económicos
                'carreras' => [2, 4, 5, 6, 37, 39, 40, 41, 42, 44, 46, 52, 54, 56, 69, 71, 74, 83, 85] // Todas las carreras económicas/administrativas
            ],
            
            // Centro 9: Ciencias
            9 => [
                'departamentos' => [], // No hay departamentos específicos listados
                'carreras' => [11, 21, 22, 23, 47, 51, 59, 60, 70, 78, 90] // Microbiología, Biología, Física, Matemática, etc.
            ],
            
            // Centro 10: Ciencias Médicas
            10 => [
                'departamentos' => [29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41], // Todos los departamentos médicos
                'carreras' => [7, 10, 61, 62, 75, 84] // Emergencias Médicas, Imágenes, Enfermería, Medicina, etc.
            ],
            
            // Centro 16: UNAH CHOLUTECA
            16 => [
                'departamentos' => [42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52], // Departamentos de Choluteca
                'carreras' => [91, 92, 93, 94, 95, 96, 97] // Carreras específicas de Choluteca
            ],
            
            // Centro 19: No Adscrita (carreras generales/educación)
            19 => [
                'departamentos' => [], // No hay departamentos específicos
                'carreras' => [24, 27, 28, 29, 30, 32, 33, 43, 53, 55, 57, 64, 67, 68, 72, 73, 87, 88, 89] // Educación, técnicos diversos
            ]
        ];

        // Crear relaciones basadas en el mapeo por centro
        foreach ($relacionesPorCentro as $centroId => $datos) {
            $departamentos = $datos['departamentos'];
            $carreras = $datos['carreras'];
            
            foreach ($carreras as $carreraId) {
                if (!empty($departamentos)) {
                    // Si el centro tiene departamentos específicos, relacionar con todos
                    foreach ($departamentos as $departamentoId) {
                        DB::table('carrera_departamento_academico')->insert([
                            'carrera_id' => $carreraId,
                            'departamento_academico_id' => $departamentoId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    // Si no hay departamentos específicos, buscar departamentos del mismo centro
                    $departamentosPorCentro = DB::table('departamento_academico')
                        ->where('centro_facultad_id', $centroId)
                        ->pluck('id');
                    
                    foreach ($departamentosPorCentro as $departamentoId) {
                        DB::table('carrera_departamento_academico')->insert([
                            'carrera_id' => $carreraId,
                            'departamento_academico_id' => $departamentoId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Relaciones específicas adicionales para casos especiales
        $relacionesEspecificas = [
            // Carreras que pueden pertenecer a múltiples departamentos transversales
            [
                'carrera_id' => 24, // Pedagogía
                'departamentos' => [42, 43] // Ciencias Sociales y Humanidades Choluteca
            ],
            [
                'carrera_id' => 88, // Profesorado en Inglés
                'departamentos' => [51] // Lenguas Extranjeras Choluteca
            ],
        ];

        foreach ($relacionesEspecificas as $relacion) {
            foreach ($relacion['departamentos'] as $departamentoId) {
                DB::table('carrera_departamento_academico')->updateOrInsert([
                    'carrera_id' => $relacion['carrera_id'],
                    'departamento_academico_id' => $departamentoId,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
