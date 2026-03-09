<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\Constancia\BuilderConstancia;

use App\Models\Constancia\Constancia;
use App\Models\Constancia\TipoConstancia;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use PDF;

class PDFController extends Controller
{
    public function descargarPerfilProyecto(Proyecto $proyecto)
    {
        // The profile view traverses many nested relationships; eager load to avoid
        // N+1 queries and reduce render time before passing HTML to DomPDF.
        $proyecto->loadMissing([
            'facultades_centros',
            'departamentos_academicos',
            'carreras',
            'ejes_prioritarios_unah',
            'categoria',
            'departamento',
            'municipio',
            'presupuesto',
            'empleados',
            'actividades.empleados',
            'entidad_contraparte.instrumento_formalizacion',
            'anexos',
            'estudiante_proyecto.estudiante.user',
        ]);

        // DomPDF can exceed PHP defaults for complex tables and images.
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $pdf = PDF::loadView('components.fichas.ficha-proyecto-vinculacion', [
            'proyecto' => $proyecto,
            'isPdf' => true,
        ])->setPaper('letter', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'Arial')
            ->setOption('dpi', 96);

        return $pdf->download("perfil_proyecto_{$proyecto->id}.pdf");
    }

    public function generatePDF()
    {
        $qrcode = base64_encode(QrCode::format('png')->size(200)->errorCorrection('H')->generate('string'));
        $headerLogo = env('PDF_HEADER_LOGO');
        $footerLogo = env('PDF_FOOTER_LOGO');
        $solLogo = env ('PDF_SOL_LOGO');
        $lucenLogo = env('PDF_LUCEN_LOGO'); 
        //$enlace = url('/constancias/EZ43B3D182');

        $data = [
            'title' => 'CONSTANCIA DE REGISTRO DE PROYECTO DE VINCULACIÓN',
            'qrcode' => $qrcode,
            'headerLogo' => $headerLogo,
            'footerLogo' => $footerLogo,
            'solLogo' => $solLogo,
            'lucenLogo' => $lucenLogo,
            'texto' => ' <p>
                    El Suscrito Director de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace
                    <strong>CONSTAR</strong> que <strong>UNAH Campus Atlántida</strong> ha registrado el proyecto de
                    vinculación denominado <strong>Evaluación de Medio Término y Final del Proyecto de Fortalecimiento
                        del Enfoque de Salvaguarda Infantil y Juvenil de Aldeas SOS</strong> el cual se ejecuta en
                    Choluteca, Santa Rosa de Copán, Tela, Tegucigalpa / Departamentos de Choluteca, Copán, Atlántida y
                    Francisco Morazán durante el período <strong>10 febrero de 2025</strong> hasta el <strong>30 enero
                        de 2026</strong>, el cual fue registrado en esta dirección con el número
                    <strong>DVUS-VRA-083-2025</strong>. El equipo docente responsable de la ejecución de este proyecto
                    es el siguiente:
                </p>',/*
            'selloDirector' => $selloDirector,
            'firmaDirector' => $firmaDirector,
            'nombreDirector' => $nombreDirector,
            'codigoVerificacion' => $codigoVerificacion,
            'anioAcademico' => $anioAcademico,
*/
        ];  
              
        $pdf = PDF::loadView('my-pdf', $data);
  
        return $pdf->download('constanciaregistro.pdf');
    }
    

    public function verVista()
{
    $qrcode = base64_encode(QrCode::format('png')->size(200)->errorCorrection('H')->generate('string'));
    $headerLogo = env('PDF_HEADER_LOGO');
    $footerLogo = env('PDF_FOOTER_LOGO');
    $solLogo = env('PDF_SOL_LOGO'); 
    $lucenLogo = env('PDF_LUCEN_LOGO'); 
    

    $data = [
        'title' => 'CONSTANCIA DE REGISTRO DE PROYECTO DE VINCULACIÓN',
        'qrcode' => $qrcode,
        'headerLogo' => $headerLogo,
        'footerLogo' => $footerLogo,
        'solLogo' => $solLogo,
        'lucenLogo' => $lucenLogo,
        'texto' => '<p>
                El Suscrito Director de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace
                <strong>CONSTAR</strong> que <strong>UNAH Campus Atlántida</strong> ha registrado el proyecto de
                vinculación denominado <strong>Evaluación de Medio Término y Final del Proyecto de Fortalecimiento
                    del Enfoque de Salvaguarda Infantil y Juvenil de Aldeas SOS</strong> el cual se ejecuta en
                Choluteca, Santa Rosa de Copán, Tela, Tegucigalpa / Departamentos de Choluteca, Copán, Atlántida y
                Francisco Morazán durante el período <strong>10 febrero de 2025</strong> hasta el <strong>30 enero
                    de 2026</strong>, el cual fue registrado en esta dirección con el número
                <strong>DVUS-VRA-083-2025</strong>. El equipo docente responsable de la ejecución de este proyecto
                es el siguiente:
            </p>',
    ];

    $pdf = PDF::loadView('my-pdf', $data);

    // Usa stream() para mostrar el PDF en el navegador
    return $pdf->stream('constancia_ver.pdf');
}
}
