<?php

namespace Database\Seeders\UnidadAcademica;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\UnidadAcademica\EntidadAcademica;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\TipoEntidadAcademica;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\Proyecto\Modalidad;


class UnidadAcademicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //crear un campus
        $CU = Campus::create(['nombre_campus' => 'Ciudad Universitaria', 'direccion' => 'Tegucigalpa, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);
        $UNAHVS = Campus::create(['nombre_campus' => 'UNAH-VS', 'direccion' => 'San Pedro Sula, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);
        $UNAHTecDanli = Campus::create(['nombre_campus' => 'UNAH-TEC Danli', 'direccion' => 'Danli, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);
        $CURNO = Campus::create(['nombre_campus' => 'CURNO', 'direccion' => 'Olancho, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);
        $CURC = Campus::create(['nombre_campus' => 'CURC', 'direccion' => 'Comayagua, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);
        $CURLA = Campus::create(['nombre_campus' => 'CURLA', 'direccion' => 'La Ceiba, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);
        $CURLP = Campus::create(['nombre_campus' => 'CURLP', 'direccion' => 'Choluteca, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);
        $CUROC = Campus::create(['nombre_campus' => 'CUROC', 'direccion' => 'Santa Rosa de Copan, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);
        $UNAHTecAguan = Campus::create(['nombre_campus' => 'UNAH-TEC Aguán', 'direccion' => 'Tocoa, Honduras', 'telefono' => '2232-5678', 'url' => 'https://www.unah.edu.hn']);



        $facultadCienciasSociales = FacultadCentro::create(['nombre' => 'Ciencias Sociales', 'es_facultad' => true, 'siglas' => 'FCS', 'campus_id' => $CU->id]);
        $facultadQuimicaFarmacia = FacultadCentro::create(['nombre' => 'Química y Farmacia', 'es_facultad' => true, 'siglas' => 'FQF', 'campus_id' => $CU->id]);
        $facultadOdontologia = FacultadCentro::create(['nombre' => 'Odontología', 'es_facultad' => true, 'siglas' => 'FO', 'campus_id' => $CU->id]);
        $facultadCienciasJuridicas = FacultadCentro::create(['nombre' => 'Ciencias Jurídicas', 'es_facultad' => true, 'siglas' => 'FCJ', 'campus_id' => $CU->id]);
        $facultadIngenieria = FacultadCentro::create(['nombre' => 'Ingeniería', 'es_facultad' => true, 'siglas' => 'FI', 'campus_id' => $CU->id]);
        $facultadHumanidadesArtes = FacultadCentro::create(['nombre' => 'Humanidades y Artes', 'es_facultad' => true, 'siglas' => 'FHA', 'campus_id' => $CU->id]);
        $facultadCienciasEspaciales = FacultadCentro::create(['nombre' => 'Ciencias Espaciales', 'es_facultad' => true, 'siglas' => 'FCE', 'campus_id' => $CU->id]);
        $facultadCienciasEconomicas = FacultadCentro::create(['nombre' => 'Ciencias Económicas, Administrativas y Contables', 'es_facultad' => true, 'siglas' => 'FCEAC', 'campus_id' => $CU->id]);
        $facultadCiencias = FacultadCentro::create(['nombre' => 'Ciencias', 'es_facultad' => true, 'siglas' => 'FC', 'campus_id' => $CU->id]);
        $facultadCienciasMedicas = FacultadCentro::create(['nombre' => 'Ciencias Médicas', 'es_facultad' => true, 'siglas' => 'FCM', 'campus_id' => $CU->id]);

        $centroDanli = FacultadCentro::create(['nombre' => 'UNAH-TEC Danli Centro Tecnológico de Danlí', 'es_facultad' => false, 'siglas' => 'UNAH-TEC Danli', 'campus_id' => $UNAHTecDanli->id]);
        $centroCurNo = FacultadCentro::create(['nombre' => 'CURNO Centro Universitario Regional Nororiental', 'es_facultad' => false, 'siglas' => 'CURNO', 'campus_id' => $CURNO->id]);
        $centroUnahVs = FacultadCentro::create(['nombre' => 'UNAH-VS UNAH Valle de Sula', 'es_facultad' => false, 'siglas' => 'UNAH-VS', 'campus_id' => $UNAHVS->id]);
        $centroCurc = FacultadCentro::create(['nombre' => 'CURC Centro Universitario Regional del Centro', 'es_facultad' => false, 'siglas' => 'CURC', 'campus_id' => $CURC->id]);
        $centroCurlA = FacultadCentro::create(['nombre' => 'CURLA Centro Universitario Regional de Litoral Atlántico', 'es_facultad' => false, 'siglas' => 'CURLA', 'campus_id' => $CURLA->id]);
        $centroCurlP = FacultadCentro::create(['nombre' => 'CURLP Centro Universitario Regional del Litoral Pacífico', 'es_facultad' => false, 'siglas' => 'CURLP', 'campus_id' => $CURLP->id]);
        $centroCuroc = FacultadCentro::create(['nombre' => 'CUROC Centro Universitario Regional de Occidente', 'es_facultad' => false, 'siglas' => 'CUROC', 'campus_id' => $CUROC->id]);
        $centroAguan = FacultadCentro::create(['nombre' => 'UNAH-TEC AGUÁN Centro Tecnológico del Valle de Aguan', 'es_facultad' => false, 'siglas' => 'UNAH-TEC Aguán', 'campus_id' => $UNAHTecAguan->id]);
        


        $departamentoDerechoAdministrativo = DepartamentoAcademico::create(['nombre' => 'Derecho Administrativo', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoInternacional = DepartamentoAcademico::create(['nombre' => 'Derecho Internacional', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoPenal = DepartamentoAcademico::create(['nombre' => 'Derecho Penal', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoPrivado = DepartamentoAcademico::create(['nombre' => 'Derecho Privado', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoProcesal = DepartamentoAcademico::create(['nombre' => 'Derecho Procesal', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoSocial = DepartamentoAcademico::create(['nombre' => 'Derecho Social', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoTeoriaHistoriaDerecho = DepartamentoAcademico::create(['nombre' => 'Teoría e Historia del Derecho', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        
    }
}
