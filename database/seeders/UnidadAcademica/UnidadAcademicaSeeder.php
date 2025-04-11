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
        $UNAH_CU = Campus::create(['nombre_campus' => 'UNAH Ciudad Universitaria', 'direccion' => 'Bulevar Suyapa, Tegucigalpa M.D.C., Francisco Morazán.', 'telefono' => '(+504) 2216-3000', 'url' => 'https://www.unah.edu.hn/']);
        $UNAH_CORTES = Campus::create(['nombre_campus' => 'UNAH Campus Cortés', 'direccion' => 'Colonia Villas del Sol, bulevar Roberto Micheletti, sector Jardines del Valle, San Pedro Sula, Cortés.', 'telefono' => '(+504) 2545-6600', 'url' => 'https://vallesula.unah.edu.hn/']);
        $UNAH_ELPARAISO = Campus::create(['nombre_campus' => 'UNAH Campus El Paraíso', 'direccion' => 'Danlí, El Paraíso, carretera Panamericana, frente al Hospital Regional Gabriela Alvarado.', 'telefono' => '(+504) 2763-9900', 'url' => 'https://tecdanli.unah.edu.hn/']);
        $UNAH_OLANCHO = Campus::create(['nombre_campus' => 'UNAH Campus Olancho', 'direccion' => 'Juticalpa, Olancho.', 'telefono' => '(+504) 2789-9000', 'url' => 'https://curno.unah.edu.hn/']);
        $UNAH_COMAYAGUA = Campus::create(['nombre_campus' => 'UNAH Campus Comayagua', 'direccion' => 'Colonia San Miguel, última rotonda, salida a Tegucigalpa, atrás de Ferromax.', 'telefono' => '(+504) 2771-5700', 'url' => 'https://curc.unah.edu.hn/']);
        $UNAH_ATLANTIDA = Campus::create(['nombre_campus' => 'UNAH Campus Atlátida', 'direccion' => 'Carretera Ceiba-Tela, detrás del aeropuerto Golosón, desvío frente a Supermercado Despensa Familiar.', 'telefono' => '(+504) 2442-9500', 'url' => 'https://curla.unah.edu.hn/']);
        $UNAH_CHOLUTECA = Campus::create(['nombre_campus' => 'UNAH Campus Choluteca', 'direccion' => 'Km 5 salida a San Marcos de Colón, desvío a la derecha frente a Residencial Anda Lucía.', 'telefono' => '(+504) 2780-5900', 'url' => 'https://curlp.unah.edu.hn/']);
        $UNAH_COPAN = Campus::create(['nombre_campus' => 'UNAH Campus Copán', 'direccion' => 'Colonia Villa Belén, Santa Rosa de Copán.', 'telefono' => '(+504) 2262-7700', 'url' => 'https://curoc.unah.edu.hn/']);
        $UNAH_YORO = Campus::create(['nombre_campus' => 'UNAH Campus Yoro', 'direccion' => 'Olanchito, Yoro', 'telefono' => '(+504) 2446-5900', 'url' => 'https://curva.unah.edu.hn/']);


        //FacultadCentro CU
        $facultadCienciasSociales = FacultadCentro::create(['nombre' => 'Ciencias Sociales', 'es_facultad' => true, 'siglas' => 'FCS', 'campus_id' => $UNAH_CU->id]);
        $facultadQuimicaFarmacia = FacultadCentro::create(['nombre' => 'Química y Farmacia', 'es_facultad' => true, 'siglas' => 'FQF', 'campus_id' => $UNAH_CU->id]);
        $facultadOdontologia = FacultadCentro::create(['nombre' => 'Odontología', 'es_facultad' => true, 'siglas' => 'FO', 'campus_id' => $UNAH_CU->id]);
        $facultadCienciasJuridicas = FacultadCentro::create(['nombre' => 'Ciencias Jurídicas', 'es_facultad' => true, 'siglas' => 'FCJ', 'campus_id' => $UNAH_CU->id]);
        $facultadIngenieria = FacultadCentro::create(['nombre' => 'Ingeniería', 'es_facultad' => true, 'siglas' => 'FI', 'campus_id' => $UNAH_CU->id]);
        $facultadHumanidadesArtes = FacultadCentro::create(['nombre' => 'Humanidades y Artes', 'es_facultad' => true, 'siglas' => 'FHA', 'campus_id' => $UNAH_CU->id]);
        $facultadCienciasEspaciales = FacultadCentro::create(['nombre' => 'Ciencias Espaciales', 'es_facultad' => true, 'siglas' => 'FCE', 'campus_id' => $UNAH_CU->id]);
        $facultadCienciasEconomicas = FacultadCentro::create(['nombre' => 'Ciencias Económicas, Administrativas y Contables', 'es_facultad' => true, 'siglas' => 'FCEAC', 'campus_id' => $UNAH_CU->id]);
        $facultadCiencias = FacultadCentro::create(['nombre' => 'Ciencias', 'es_facultad' => true, 'siglas' => 'FC', 'campus_id' => $UNAH_CU->id]);
        $facultadCienciasMedicas = FacultadCentro::create(['nombre' => 'Ciencias Médicas', 'es_facultad' => true, 'siglas' => 'FCM', 'campus_id' => $UNAH_CU->id]);

        //FacultadCentro Resto de Campus
        $centroDanli = FacultadCentro::create(['nombre' => 'UNAH EL PARAÍSO', 'es_facultad' => false, 'siglas' => 'UNAH-TEC Danli', 'campus_id' => $UNAH_ELPARAISO->id]);
        $centroCurNo = FacultadCentro::create(['nombre' => 'UNAH OLANCHO', 'es_facultad' => false, 'siglas' => 'CURNO', 'campus_id' => $UNAH_OLANCHO->id]);
        $centroUnahVs = FacultadCentro::create(['nombre' => 'UNAH CORTES', 'es_facultad' => false, 'siglas' => 'UNAH-VS', 'campus_id' => $UNAH_CORTES->id]);
        $centroCurc = FacultadCentro::create(['nombre' => 'UNAH COMAYAGUA', 'es_facultad' => false, 'siglas' => 'CURC', 'campus_id' => $UNAH_COMAYAGUA->id]);
        $centroCurlA = FacultadCentro::create(['nombre' => 'ATLANTIDA', 'es_facultad' => false, 'siglas' => 'CURLA', 'campus_id' => $UNAH_ATLANTIDA->id]);
        $centroCurlP = FacultadCentro::create(['nombre' => 'UNAH CHOLUTECA', 'es_facultad' => false, 'siglas' => 'CURLP', 'campus_id' => $UNAH_CHOLUTECA->id]);
        $centroCuroc = FacultadCentro::create(['nombre' => 'UNAH COPÁN', 'es_facultad' => false, 'siglas' => 'CUROC', 'campus_id' => $UNAH_COPAN->id]);
        $centroAguan = FacultadCentro::create(['nombre' => 'UNAH YORO', 'es_facultad' => false, 'siglas' => 'UNAH-TEC Aguán', 'campus_id' => $UNAH_YORO->id]);



        //Departamentos de la Facultad de Ciencias Sociales



        //Departamentos de la Facultad de Química y Farmacia
        $departamentoControlQuimico = DepartamentoAcademico::create(['nombre' => 'Control Químico', 'centro_facultad_id' => $facultadQuimicaFarmacia->id]);
        $departamentoTecnologiaFarmaceutica = DepartamentoAcademico::create(['nombre' => 'Tecnología Farmacéutica', 'centro_facultad_id' => $facultadQuimicaFarmacia->id]);
        $departamentoQuimica = DepartamentoAcademico::create(['nombre' => 'Química', 'centro_facultad_id' => $facultadQuimicaFarmacia->id]);

        //Departamentos de la Facultad de Odontología
        $departamentoOdontologiaPreventivaSocial = DepartamentoAcademico::create(['nombre' => 'Odontología Preventiva y Social', 'centro_facultad_id' => $facultadOdontologia->id]);
        $departamentoEstomatologia = DepartamentoAcademico::create(['nombre' => 'Estomatología', 'centro_facultad_id' => $facultadOdontologia->id]);
        $departamentoProtesisBucalMaxilofacial = DepartamentoAcademico::create(['nombre' => 'Protesis Bucal y Maxilofacial', 'centro_facultad_id' => $facultadOdontologia->id]);
        $departamentoOdontologiaRestauradora = DepartamentoAcademico::create(['nombre' => 'Odontologia Restauradora', 'centro_facultad_id' => $facultadOdontologia->id]);

        //Departamentos de la Facultad de Ciencias Jurídicas
        $departamentoDerechoAdministrativo = DepartamentoAcademico::create(['nombre' => 'Derecho Administrativo', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoInternacional = DepartamentoAcademico::create(['nombre' => 'Derecho Internacional', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoPenal = DepartamentoAcademico::create(['nombre' => 'Derecho Penal', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoPrivado = DepartamentoAcademico::create(['nombre' => 'Derecho Privado', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoProcesal = DepartamentoAcademico::create(['nombre' => 'Derecho Procesal', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoSocial = DepartamentoAcademico::create(['nombre' => 'Derecho Social', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoTeoriaHistoriaDerecho = DepartamentoAcademico::create(['nombre' => 'Teoría e Historia del Derecho', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);



        //Departamentos de la Facultad de Ingeniería 


        //Departamentos de la Facultad de Humanidades y Artes



        //Departamentos de la Facultad de Ciencias Espaciales
        $departamentoAstronomiaAstrofisica = DepartamentoAcademico::create(['nombre' => 'Astronomía Astrofísica', 'centro_facultad_id' => $facultadCienciasEspaciales->id]);
        $departamentoCienciaTecnologiasInformacionGeografica = DepartamentoAcademico::create(['nombre' => 'Ciencia y Tecnologías de la Información Geográfica', 'centro_facultad_id' => $facultadCienciasEspaciales->id]);
        $departamentoArqueoastronomíaAstronomíaCultural = DepartamentoAcademico::create(['nombre' => 'Arqueoastronomía y Astronomía Cultural', 'centro_facultad_id' => $facultadCienciasEspaciales->id]);
        $departamentoCienciasAeronáuticas = DepartamentoAcademico::create(['nombre' => 'Ciencias Aeronáuticas', 'centro_facultad_id' => $facultadCienciasEspaciales->id]);


        //Departamentos de la Facultad de Ciencias Económicas, Administrativas y Contables
        $departamentoAdministracionAduanera = DepartamentoAcademico::create(['nombre' => 'Administración Aduanera', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoAdministracionEmpresas = DepartamentoAcademico::create(['nombre' => 'Administración de Empresas', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoAdministracionPublica = DepartamentoAcademico::create(['nombre' => 'Administración Pública', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoBancaFinanzas = DepartamentoAcademico::create(['nombre' => 'Banca y Finanzas', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoComercioInternacional = DepartamentoAcademico::create(['nombre' => 'Comercio Internacional', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoContaduriaPublicaFinanzas = DepartamentoAcademico::create(['nombre' => 'Contaduría Pública y Finanzas', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoEconomia = DepartamentoAcademico::create(['nombre' => 'Economía', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoInformaticaAdministrativas = DepartamentoAcademico::create(['nombre' => 'Informática Administrativas', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoMercadotecnia = DepartamentoAcademico::create(['nombre' => 'Mercadotecnia', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoMetodosCuantitativos = DepartamentoAcademico::create(['nombre' => 'Metodos Cuantitativos', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);



        //Departamentos de la Facultad de Ciencias



        //Departamentos de la Facultad de Ciencias Médicas
        $departamentoCienciasMorfologicas = DepartamentoAcademico::create(['nombre' => 'Ciencias Morfológicas', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoSaludPublica = DepartamentoAcademico::create(['nombre' => 'Salud Pública', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoPatologia = DepartamentoAcademico::create(['nombre' => 'Patología', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoMedicinaInterna = DepartamentoAcademico::create(['nombre' => 'Medicina Interna', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoCirugia = DepartamentoAcademico::create(['nombre' => 'Cirugía', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoFisiologia = DepartamentoAcademico::create(['nombre' => 'Fisiología', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoGinecoObstetricia = DepartamentoAcademico::create(['nombre' => 'Gineco Obstetricia', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoPediatria = DepartamentoAcademico::create(['nombre' => 'Pediatría', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoPsiquiatria = DepartamentoAcademico::create(['nombre' => 'Psiquiatría', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoEnfermeria = DepartamentoAcademico::create(['nombre' => 'Enfermería', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoDepartamentodeCienciasBiomédicaseImágenes = DepartamentoAcademico::create(['nombre' => 'Departamento de Ciencias Biomédicas e Imágenes', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoMedicinadeRehabilitacion = DepartamentoAcademico::create(['nombre' => 'Medicina de Rehabilitación', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoDepartamentodeNutricion = DepartamentoAcademico::create(['nombre' => 'Departamento de Nutrición', 'centro_facultad_id' => $facultadCienciasMedicas->id]);

        //Departamentos en el Campus Choluteca
        $departamentoCienciasSociales = DepartamentoAcademico::create(['nombre' => 'Ciencias Sociales Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoHumanidadesArtes = DepartamentoAcademico::create(['nombre' => 'Humanidades y Artes Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoAcuicultura = DepartamentoAcademico::create(['nombre' => 'Acuicultura Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoAdministracionEmpresas = DepartamentoAcademico::create(['nombre' => 'Administración de Empresas Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoComercioInternacional = DepartamentoAcademico::create(['nombre' => 'Comercio Internacional Choluteca',  'centro_facultad_id' => $centroCurlP->id]);
        $departamentoAgroindustria = DepartamentoAcademico::create(['nombre' => 'Agroindustria Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoIngenieriaSistemas = DepartamentoAcademico::create(['nombre' => 'Ingeniería en Sistemas Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoQuimica = DepartamentoAcademico::create(['nombre' => 'Química Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoBiologia = DepartamentoAcademico::create(['nombre' => 'Biología Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoLenguasExtranjeras = DepartamentoAcademico::create(['nombre' => 'Lenguas Extranjeras Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoCulturaFisicaDeportes = DepartamentoAcademico::create(['nombre' => 'Cultura Física y Deportes Choluteca', 'centro_facultad_id' => $centroCurlP->id]);


        Carrera::create(
            [
                'nombre' => 'Ingenieria en sistemas',
                'facultad_centro_id' =>  $facultadIngenieria->id,
            ]
        );
    }
}
