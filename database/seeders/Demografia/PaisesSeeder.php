<?php

namespace Database\Seeders\Demografia;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Demografia\Pais;

class PaisesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $paises = [
            [
                'codigo_area' => '+54',
                'codigo_iso' => 'ARG',
                'codigo_iso_numerico' => '032',
                'codigo_iso_alpha_2' => 'AR',
                'nombre' => 'Argentina',
                'gentilicio' => 'Argentino/a',
            ],
            [
                'codigo_area' => '+591',
                'codigo_iso' => 'BOL',
                'codigo_iso_numerico' => '068',
                'codigo_iso_alpha_2' => 'BO',
                'nombre' => 'Bolivia',
                'gentilicio' => 'Boliviano/a',
            ],
            [
                'codigo_area' => '+55',
                'codigo_iso' => 'BRA',
                'codigo_iso_numerico' => '076',
                'codigo_iso_alpha_2' => 'BR',
                'nombre' => 'Brasil',
                'gentilicio' => 'Brasileño/a',
            ],
            [
                'codigo_area' => '+56',
                'codigo_iso' => 'CHL',
                'codigo_iso_numerico' => '152',
                'codigo_iso_alpha_2' => 'CL',
                'nombre' => 'Chile',
                'gentilicio' => 'Chileno/a',
            ],
            [
                'codigo_area' => '+57',
                'codigo_iso' => 'COL',
                'codigo_iso_numerico' => '170',
                'codigo_iso_alpha_2' => 'CO',
                'nombre' => 'Colombia',
                'gentilicio' => 'Colombiano/a',
            ],
            [
                'codigo_area' => '+506',
                'codigo_iso' => 'CRI',
                'codigo_iso_numerico' => '188',
                'codigo_iso_alpha_2' => 'CR',
                'nombre' => 'Costa Rica',
                'gentilicio' => 'Costarricense',
            ],
            [
                'codigo_area' => '+53',
                'codigo_iso' => 'CUB',
                'codigo_iso_numerico' => '192',
                'codigo_iso_alpha_2' => 'CU',
                'nombre' => 'Cuba',
                'gentilicio' => 'Cubano/a',
            ],
            [
                'codigo_area' => '+593',
                'codigo_iso' => 'ECU',
                'codigo_iso_numerico' => '218',
                'codigo_iso_alpha_2' => 'EC',
                'nombre' => 'Ecuador',
                'gentilicio' => 'Ecuatoriano/a',
            ],
            [
                'codigo_area' => '+503',
                'codigo_iso' => 'SLV',
                'codigo_iso_numerico' => '222',
                'codigo_iso_alpha_2' => 'SV',
                'nombre' => 'El Salvador',
                'gentilicio' => 'Salvadoreño/a',
            ],
            [
                'codigo_area' => '+502',
                'codigo_iso' => 'GTM',
                'codigo_iso_numerico' => '320',
                'codigo_iso_alpha_2' => 'GT',
                'nombre' => 'Guatemala',
                'gentilicio' => 'Guatemalteco/a',
            ],
            [
                'codigo_area' => '+504',
                'codigo_iso' => 'HND',
                'codigo_iso_numerico' => '340',
                'codigo_iso_alpha_2' => 'HN',
                'nombre' => 'Honduras',
                'gentilicio' => 'Hondureño/a',
            ],
            [
                'codigo_area' => '+52',
                'codigo_iso' => 'MEX',
                'codigo_iso_numerico' => '484',
                'codigo_iso_alpha_2' => 'MX',
                'nombre' => 'México',
                'gentilicio' => 'Mexicano/a',
            ],
            [
                'codigo_area' => '+505',
                'codigo_iso' => 'NIC',
                'codigo_iso_numerico' => '558',
                'codigo_iso_alpha_2' => 'NI',
                'nombre' => 'Nicaragua',
                'gentilicio' => 'Nicaragüense',
            ],
            [
                'codigo_area' => '+507',
                'codigo_iso' => 'PAN',
                'codigo_iso_numerico' => '591',
                'codigo_iso_alpha_2' => 'PA',
                'nombre' => 'Panamá',
                'gentilicio' => 'Panameño/a',
            ],
            [
                'codigo_area' => '+595',
                'codigo_iso' => 'PRY',
                'codigo_iso_numerico' => '600',
                'codigo_iso_alpha_2' => 'PY',
                'nombre' => 'Paraguay',
                'gentilicio' => 'Paraguayo/a',
            ],
            [
                'codigo_area' => '+51',
                'codigo_iso' => 'PER',
                'codigo_iso_numerico' => '604',
                'codigo_iso_alpha_2' => 'PE',
                'nombre' => 'Perú',
                'gentilicio' => 'Peruano/a',
            ],
            [
                'codigo_area' => '+58',
                'codigo_iso' => 'VEN',
                'codigo_iso_numerico' => '862',
                'codigo_iso_alpha_2' => 'VE',
                'nombre' => 'Venezuela',
                'gentilicio' => 'Venezolano/a',
            ],
            [
                'codigo_area' => '+598',
                'codigo_iso' => 'URY',
                'codigo_iso_numerico' => '858',
                'codigo_iso_alpha_2' => 'UY',
                'nombre' => 'Uruguay',
                'gentilicio' => 'Uruguayo/a',
            ]
        ];

        // Inserta los países en la base de datos
        foreach ($paises as $pais) {
            Pais::create($pais);
        }
    }
}
