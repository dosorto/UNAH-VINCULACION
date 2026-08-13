<?php

namespace Database\Seeders\UnidadAcademica;

use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Database\Seeder;

class UnidadAcademicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(CategoriaEmpleadoSeeder::class);

        $actualizarCatalogo = static function (string $modelo, array $clave, array $atributos) {
            $registro = $modelo::withTrashed()->updateOrCreate($clave, $atributos);

            if ($registro->trashed()) {
                $registro->restore();
            }

            return $registro;
        };

        $crearCampus = static fn (array $datos) => $actualizarCatalogo(
            Campus::class,
            ['nombre_campus' => $datos['nombre_campus']],
            $datos,
        );
        $crearCentro = static fn (array $datos) => $actualizarCatalogo(
            FacultadCentro::class,
            ['campus_id' => $datos['campus_id'], 'nombre' => $datos['nombre']],
            $datos,
        );
        $crearDepartamento = static fn (array $datos) => $actualizarCatalogo(
            DepartamentoAcademico::class,
            ['centro_facultad_id' => $datos['centro_facultad_id'], 'nombre' => $datos['nombre']],
            $datos,
        );
        $crearCarrera = static fn (array $datos) => $actualizarCatalogo(
            Carrera::class,
            ['facultad_centro_id' => $datos['facultad_centro_id'], 'nombre' => $datos['nombre']],
            $datos,
        );

        // crear un campus
        $UNAH_CU = $crearCampus(['nombre_campus' => 'UNAH Ciudad Universitaria', 'direccion' => 'Bulevar Suyapa, Tegucigalpa M.D.C., Francisco Morazán.', 'telefono' => '(+504) 2216-3000', 'url' => 'https://www.unah.edu.hn/']);
        $UNAH_CORTES = $crearCampus(['nombre_campus' => 'UNAH Campus Cortés', 'direccion' => 'Colonia Villas del Sol, bulevar Roberto Micheletti, sector Jardines del Valle, San Pedro Sula, Cortés.', 'telefono' => '(+504) 2545-6600', 'url' => 'https://vallesula.unah.edu.hn/']);
        $UNAH_ELPARAISO = $crearCampus(['nombre_campus' => 'UNAH Campus El Paraíso', 'direccion' => 'Danlí, El Paraíso, carretera Panamericana, frente al Hospital Regional Gabriela Alvarado.', 'telefono' => '(+504) 2763-9900', 'url' => 'https://tecdanli.unah.edu.hn/']);
        $UNAH_OLANCHO = $crearCampus(['nombre_campus' => 'UNAH Campus Olancho', 'direccion' => 'Juticalpa, Olancho.', 'telefono' => '(+504) 2789-9000', 'url' => 'https://curno.unah.edu.hn/']);
        $UNAH_COMAYAGUA = $crearCampus(['nombre_campus' => 'UNAH Campus Comayagua', 'direccion' => 'Colonia San Miguel, última rotonda, salida a Tegucigalpa, atrás de Ferromax.', 'telefono' => '(+504) 2771-5700', 'url' => 'https://curc.unah.edu.hn/']);
        $UNAH_ATLANTIDA = $crearCampus(['nombre_campus' => 'UNAH Campus Atlátida', 'direccion' => 'Carretera Ceiba-Tela, detrás del aeropuerto Golosón, desvío frente a Supermercado Despensa Familiar.', 'telefono' => '(+504) 2442-9500', 'url' => 'https://curla.unah.edu.hn/']);
        $UNAH_CHOLUTECA = $crearCampus(['nombre_campus' => 'UNAH Campus Choluteca', 'direccion' => 'Km 5 salida a San Marcos de Colón, desvío a la derecha frente a Residencial Anda Lucía.', 'telefono' => '(+504) 2780-5900', 'url' => 'https://curlp.unah.edu.hn/']);
        $UNAH_COPAN = $crearCampus(['nombre_campus' => 'UNAH Campus Copán', 'direccion' => 'Colonia Villa Belén, Santa Rosa de Copán.', 'telefono' => '(+504) 2262-7700', 'url' => 'https://curoc.unah.edu.hn/']);
        $UNAH_YORO = $crearCampus(['nombre_campus' => 'UNAH Campus Yoro', 'direccion' => 'Olanchito, Yoro', 'telefono' => '(+504) 2446-5900', 'url' => 'https://curva.unah.edu.hn/']);

        // FacultadCentro CU
        $facultadCienciasSociales = $crearCentro(['nombre' => 'Ciencias Sociales', 'es_facultad' => true, 'siglas' => 'FCS', 'campus_id' => $UNAH_CU->id]);
        $facultadQuimicaFarmacia = $crearCentro(['nombre' => 'Química y Farmacia', 'es_facultad' => true, 'siglas' => 'FQF', 'campus_id' => $UNAH_CU->id]);
        $facultadOdontologia = $crearCentro(['nombre' => 'Odontología', 'es_facultad' => true, 'siglas' => 'FO', 'campus_id' => $UNAH_CU->id]);
        $facultadCienciasJuridicas = $crearCentro(['nombre' => 'Ciencias Jurídicas', 'es_facultad' => true, 'siglas' => 'FCJ', 'campus_id' => $UNAH_CU->id]);
        $facultadIngenieria = $crearCentro(['nombre' => 'Ingeniería', 'es_facultad' => true, 'siglas' => 'FI', 'campus_id' => $UNAH_CU->id]);
        $facultadHumanidadesArtes = $crearCentro(['nombre' => 'Humanidades y Artes', 'es_facultad' => true, 'siglas' => 'FHA', 'campus_id' => $UNAH_CU->id]);
        $facultadCienciasEspaciales = $crearCentro(['nombre' => 'Ciencias Espaciales', 'es_facultad' => true, 'siglas' => 'FCE', 'campus_id' => $UNAH_CU->id]);
        $facultadCienciasEconomicas = $crearCentro(['nombre' => 'Ciencias Económicas, Administrativas y Contables', 'es_facultad' => true, 'siglas' => 'FCEAC', 'campus_id' => $UNAH_CU->id]);
        $facultadCiencias = $crearCentro(['nombre' => 'Ciencias', 'es_facultad' => true, 'siglas' => 'FC', 'campus_id' => $UNAH_CU->id]);
        $facultadCienciasMedicas = $crearCentro(['nombre' => 'Ciencias Médicas', 'es_facultad' => true, 'siglas' => 'FCM', 'campus_id' => $UNAH_CU->id]);

        // FacultadCentro Resto de Campus
        $centroDanli = $crearCentro(['nombre' => 'UNAH EL PARAÍSO', 'es_facultad' => false, 'siglas' => 'UNAH-TEC Danli', 'campus_id' => $UNAH_ELPARAISO->id]);
        $centroCurNo = $crearCentro(['nombre' => 'UNAH OLANCHO', 'es_facultad' => false, 'siglas' => 'CURNO', 'campus_id' => $UNAH_OLANCHO->id]);
        $centroUnahVs = $crearCentro(['nombre' => 'UNAH CORTES', 'es_facultad' => false, 'siglas' => 'UNAH-VS', 'campus_id' => $UNAH_CORTES->id]);
        $centroCurc = $crearCentro(['nombre' => 'UNAH COMAYAGUA', 'es_facultad' => false, 'siglas' => 'CURC', 'campus_id' => $UNAH_COMAYAGUA->id]);
        $centroCurlA = $crearCentro(['nombre' => 'ATLANTIDA', 'es_facultad' => false, 'siglas' => 'CURLA', 'campus_id' => $UNAH_ATLANTIDA->id]);
        $centroCurlP = $crearCentro(['nombre' => 'UNAH CHOLUTECA', 'es_facultad' => false, 'siglas' => 'CURLP', 'campus_id' => $UNAH_CHOLUTECA->id]);
        $centroCuroc = $crearCentro(['nombre' => 'UNAH COPÁN', 'es_facultad' => false, 'siglas' => 'CUROC', 'campus_id' => $UNAH_COPAN->id]);
        $centroAguan = $crearCentro(['nombre' => 'UNAH YORO', 'es_facultad' => false, 'siglas' => 'UNAH-TEC Aguán', 'campus_id' => $UNAH_YORO->id]);

        $noAdscrita = $crearCentro([
            'nombre' => 'No Adscrita',
            'es_facultad' => false, // o true si quieres tratarla como facultad
            'siglas' => 'NA',
            'campus_id' => $UNAH_CU->id, // o el campus adecuado
        ]);

        // Departamentos de la Facultad de Ciencias Sociales

        // Departamentos de la Facultad de Química y Farmacia
        $departamentoControlQuimico = $crearDepartamento(['nombre' => 'Control Químico', 'centro_facultad_id' => $facultadQuimicaFarmacia->id]);
        $departamentoTecnologiaFarmaceutica = $crearDepartamento(['nombre' => 'Tecnología Farmacéutica', 'centro_facultad_id' => $facultadQuimicaFarmacia->id]);
        $departamentoQuimica = $crearDepartamento(['nombre' => 'Química', 'centro_facultad_id' => $facultadQuimicaFarmacia->id]);

        // Departamentos de la Facultad de Odontología
        $departamentoOdontologiaPreventivaSocial = $crearDepartamento(['nombre' => 'Odontología Preventiva y Social', 'centro_facultad_id' => $facultadOdontologia->id]);
        $departamentoEstomatologia = $crearDepartamento(['nombre' => 'Estomatología', 'centro_facultad_id' => $facultadOdontologia->id]);
        $departamentoProtesisBucalMaxilofacial = $crearDepartamento(['nombre' => 'Protesis Bucal y Maxilofacial', 'centro_facultad_id' => $facultadOdontologia->id]);
        $departamentoOdontologiaRestauradora = $crearDepartamento(['nombre' => 'Odontologia Restauradora', 'centro_facultad_id' => $facultadOdontologia->id]);

        // Departamentos de la Facultad de Ciencias Jurídicas
        $departamentoDerechoAdministrativo = $crearDepartamento(['nombre' => 'Derecho Administrativo', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoInternacional = $crearDepartamento(['nombre' => 'Derecho Internacional', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoPenal = $crearDepartamento(['nombre' => 'Derecho Penal', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoPrivado = $crearDepartamento(['nombre' => 'Derecho Privado', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoProcesal = $crearDepartamento(['nombre' => 'Derecho Procesal', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoDerechoSocial = $crearDepartamento(['nombre' => 'Derecho Social', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);
        $departamentoTeoriaHistoriaDerecho = $crearDepartamento(['nombre' => 'Teoría e Historia del Derecho', 'centro_facultad_id' => $facultadCienciasJuridicas->id]);

        // Departamentos de la Facultad de Ingeniería

        // Departamentos de la Facultad de Humanidades y Artes

        // Departamentos de la Facultad de Ciencias Espaciales
        $departamentoAstronomiaAstrofisica = $crearDepartamento(['nombre' => 'Astronomía Astrofísica', 'centro_facultad_id' => $facultadCienciasEspaciales->id]);
        $departamentoCienciaTecnologiasInformacionGeografica = $crearDepartamento(['nombre' => 'Ciencia y Tecnologías de la Información Geográfica', 'centro_facultad_id' => $facultadCienciasEspaciales->id]);
        $departamentoArqueoastronomíaAstronomíaCultural = $crearDepartamento(['nombre' => 'Arqueoastronomía y Astronomía Cultural', 'centro_facultad_id' => $facultadCienciasEspaciales->id]);
        $departamentoCienciasAeronáuticas = $crearDepartamento(['nombre' => 'Ciencias Aeronáuticas', 'centro_facultad_id' => $facultadCienciasEspaciales->id]);

        // Departamentos de la Facultad de Ciencias Económicas, Administrativas y Contables
        $departamentoAdministracionAduanera = $crearDepartamento(['nombre' => 'Administración Aduanera', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoAdministracionEmpresas = $crearDepartamento(['nombre' => 'Administración de Empresas', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoAdministracionPublica = $crearDepartamento(['nombre' => 'Administración Pública', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoBancaFinanzas = $crearDepartamento(['nombre' => 'Banca y Finanzas', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoComercioInternacional = $crearDepartamento(['nombre' => 'Comercio Internacional', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoContaduriaPublicaFinanzas = $crearDepartamento(['nombre' => 'Contaduría Pública y Finanzas', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoEconomia = $crearDepartamento(['nombre' => 'Economía', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoInformaticaAdministrativas = $crearDepartamento(['nombre' => 'Informática Administrativas', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoMercadotecnia = $crearDepartamento(['nombre' => 'Mercadotecnia', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);
        $departamentoMetodosCuantitativos = $crearDepartamento(['nombre' => 'Metodos Cuantitativos', 'centro_facultad_id' => $facultadCienciasEconomicas->id]);

        // Departamentos de la Facultad de Ciencias

        // Departamentos de la Facultad de Ciencias Médicas
        $departamentoCienciasMorfologicas = $crearDepartamento(['nombre' => 'Ciencias Morfológicas', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoSaludPublica = $crearDepartamento(['nombre' => 'Salud Pública', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoPatologia = $crearDepartamento(['nombre' => 'Patología', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoMedicinaInterna = $crearDepartamento(['nombre' => 'Medicina Interna', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoCirugia = $crearDepartamento(['nombre' => 'Cirugía', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoFisiologia = $crearDepartamento(['nombre' => 'Fisiología', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoGinecoObstetricia = $crearDepartamento(['nombre' => 'Gineco Obstetricia', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoPediatria = $crearDepartamento(['nombre' => 'Pediatría', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoPsiquiatria = $crearDepartamento(['nombre' => 'Psiquiatría', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoEnfermeria = $crearDepartamento(['nombre' => 'Enfermería', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoDepartamentodeCienciasBiomédicaseImágenes = $crearDepartamento(['nombre' => 'Departamento de Ciencias Biomédicas e Imágenes', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoMedicinadeRehabilitacion = $crearDepartamento(['nombre' => 'Medicina de Rehabilitación', 'centro_facultad_id' => $facultadCienciasMedicas->id]);
        $departamentoDepartamentodeNutricion = $crearDepartamento(['nombre' => 'Departamento de Nutrición', 'centro_facultad_id' => $facultadCienciasMedicas->id]);

        // Departamentos en el Campus Choluteca
        $departamentoCienciasSociales = $crearDepartamento(['nombre' => 'Ciencias Sociales Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoHumanidadesArtes = $crearDepartamento(['nombre' => 'Humanidades y Artes Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoAcuicultura = $crearDepartamento(['nombre' => 'Acuicultura Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoAdministracionEmpresas = $crearDepartamento(['nombre' => 'Administración de Empresas Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoComercioInternacional = $crearDepartamento(['nombre' => 'Comercio Internacional Choluteca',  'centro_facultad_id' => $centroCurlP->id]);
        $departamentoAgroindustria = $crearDepartamento(['nombre' => 'Agroindustria Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoIngenieriaSistemas = $crearDepartamento(['nombre' => 'Ingeniería en Sistemas Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoQuimica = $crearDepartamento(['nombre' => 'Química Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoBiologia = $crearDepartamento(['nombre' => 'Biología Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoLenguasExtranjeras = $crearDepartamento(['nombre' => 'Lenguas Extranjeras Choluteca', 'centro_facultad_id' => $centroCurlP->id]);
        $departamentoCulturaFisicaDeportes = $crearDepartamento(['nombre' => 'Cultura Física y Deportes Choluteca', 'centro_facultad_id' => $centroCurlP->id]);

        // seeder de carreras

        $crearCarrera(['nombre' => 'Licenciatura en Ciencias Jurídicas', 'facultad_centro_id' => $facultadCienciasJuridicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Administración de Empresas', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Contaduría y Finanzas', 'facultad_centro_id' => $facultadCienciasSociales->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Administración de Empresas', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Contaduría Pública y Finanzas', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Economía', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Emergencias Médicas', 'facultad_centro_id' => $facultadCienciasMedicas->id]);
        $crearCarrera(['nombre' => 'Química y Farmacia', 'facultad_centro_id' => $facultadQuimicaFarmacia->id]);
        $crearCarrera(['nombre' => 'Cirujano Dentista', 'facultad_centro_id' => $facultadOdontologia->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Imágenes Biomédicas', 'facultad_centro_id' => $facultadCienciasMedicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Microbiología y Salud Pública', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Trabajo Social', 'facultad_centro_id' => $facultadCienciasSociales->id]);
        $crearCarrera(['nombre' => 'Ingeniería Agronómica', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Ingeniería Civil', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Ingeniería Eléctrica', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Ingeniería en Sistemas', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Ingeniería Mecánica Industrial', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Ingeniería Química', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Ingeniería Textil', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Artes Plásticas', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Biología', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Física', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Matemática', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Pedagogía y Ciencias de la Educación', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Música', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Psicología', 'facultad_centro_id' => $facultadCienciasSociales->id]);
        $crearCarrera(['nombre' => 'Profesorado en Ciencias Naturales', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Física', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Musical', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Primaria', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Prebásica', 'facultad_centro_id' => $facultadCienciasSociales->id]);
        $crearCarrera(['nombre' => 'Profesorado en Filosofía con Orientación en Ciencias Sociales', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Letras con Orientación en Literatura Hispanoamericana', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Artes Gráficas', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Diseño Gráfico', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Electrónica', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Finanzas', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Producción Gráfica', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Tributación', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Ventas', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Banca y Finanzas', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Administración Pública', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Administración Aduanera', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Administración de Recursos Humanos', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Antropología', 'facultad_centro_id' => $facultadCienciasSociales->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Contabilidad Pública', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Estadística', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Fotografía', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Producción Audiovisual', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Publicidad', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Química Industrial', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Secretariado Ejecutivo', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Seguridad Industrial', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Supervisión Bancaria', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Topografía', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Comercio Internacional', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Estadística Aplicada', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Ingeniería Forestal', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Química', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Microbiología', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Enfermería', 'facultad_centro_id' => $facultadCienciasMedicas->id]);
        $crearCarrera(['nombre' => 'Medicina y Cirugía', 'facultad_centro_id' => $facultadCienciasMedicas->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Teatro', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Archivística', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Música', 'facultad_centro_id' => $facultadHumanidadesArtes->id]);
        $crearCarrera(['nombre' => 'Ingeniería Industrial', 'facultad_centro_id' => $facultadIngenieria->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Comercial', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Artística', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Finanzas', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Estadística', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Comercio Internacional', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Bibliotecología', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Media en Ciencias Sociales', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Desarrollo Empresarial', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Terapia Funcional', 'facultad_centro_id' => $facultadCienciasMedicas->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Sociología', 'facultad_centro_id' => $facultadCienciasSociales->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Geografía', 'facultad_centro_id' => $facultadCienciasEspaciales->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Geografía', 'facultad_centro_id' => $facultadCiencias->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Antropología', 'facultad_centro_id' => $facultadCienciasSociales->id]);
        $crearCarrera(['nombre' => 'Ingeniería en Agrimensura', 'facultad_centro_id' => $facultadCienciasEspaciales->id]);
        $crearCarrera(['nombre' => 'Ingeniería en Geomática', 'facultad_centro_id' => $facultadCienciasEspaciales->id]);
        $crearCarrera(['nombre' => 'Ingeniería en Ordenamiento Territorial', 'facultad_centro_id' => $facultadCienciasEspaciales->id]);
        $crearCarrera(['nombre' => 'Técnico Universitario en Mercadotecnia', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Fisioterapia', 'facultad_centro_id' => $facultadCienciasMedicas->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Marketing', 'facultad_centro_id' => $facultadCienciasEconomicas->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Meteorología', 'facultad_centro_id' => $facultadCienciasEspaciales->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Básica', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Inglés', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Profesorado en Educación Media en Lengua y Literatura', 'facultad_centro_id' => $noAdscrita->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Química Industrial', 'facultad_centro_id' => $facultadCiencias->id]);

        // Carreras específicas para UNAH Choluteca
        $crearCarrera(['nombre' => 'Licenciatura en Administración de Empresas Choluteca', 'facultad_centro_id' => $centroCurlP->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Comercio Internacional Choluteca', 'facultad_centro_id' => $centroCurlP->id]);
        $crearCarrera(['nombre' => 'Ingeniería en Sistemas Choluteca', 'facultad_centro_id' => $centroCurlP->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Biología Choluteca', 'facultad_centro_id' => $centroCurlP->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Química Choluteca', 'facultad_centro_id' => $centroCurlP->id]);
        $crearCarrera(['nombre' => 'Técnico en Acuicultura', 'facultad_centro_id' => $centroCurlP->id]);
        $crearCarrera(['nombre' => 'Licenciatura en Ciencias Sociales Choluteca', 'facultad_centro_id' => $centroCurlP->id]);

    }
}
