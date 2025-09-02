<?php

namespace App\Http\Controllers;

use App\Models\Constancia\Constancia as ConstanciaModel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;

class PDFconstanciaRegistro extends Controller  
{
    // datos requeridos
    private $titulo;
    private $texto;
    private $layout;
    private $firmaDirector;
    private $selloDirector;
    private $anioAcademico;
    private $numeroDictamen;
    private $Campus;
    private $departamento;
    private $municipio;
    private $codigoProyecto;
    private $fechaInicio;
    private $fechaFinalizacion;
    private $nombreEmpleado;
    private $nombreProyecto;
    private $nombreDirector;
    private $numeroEmpleado;
    private $categoria;
    private $deptoAcademico;
    private $Rol;


    private $hash;
    private $constancia;


    private $qrpath;
    private $pdfPath;
    private $data;


    public function __construct(ConstanciaModel $constancia)
    {
        $this->hash = $constancia->hash;
        $this->titulo = 'Constancia';
        $this->texto = 'ENTRENGA DE CONSTANCIA';
    }

    public static function make(ConstanciaModel $constancia): self
    {
        return new self($constancia);
    }

    public function setTitulo(string $titulo): self
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function setTexto(string $texto): self
    {
        $this->texto = $texto;
        return $this;
    }

    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function setFirmaDirector(string $firmaDirector): self
    {
        $this->firmaDirector = $firmaDirector;
        return $this;
    }

    public function setSelloDirector(string $selloDirector): self
    {
        $this->selloDirector = $selloDirector;
        return $this;
    }

    public function setAnioAcademico(string $anioAcademico): self
    {
        $this->anioAcademico = $anioAcademico;
        return $this;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }
    public function getTexto(): string
    {
        return $this->texto;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }
    public function getFirmaDirector(): string
    {
        return $this->firmaDirector;
    }
    public function getSelloDirector(): string
    {
        return $this->selloDirector;
    }
    public function getAnioAcademico(): string
    {
        return $this->anioAcademico;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function preparateData(): self
    {
        $this->data = [
            'titulo' => $this->titulo,
            'texto' => $this->texto,
            'layout' => $this->layout,
            'firmaDirector' => $this->firmaDirector,
            'selloDirector' => $this->selloDirector,
            'anioAcademico' => $this->anioAcademico,
        ];
        return $this;
    }

    public function generate(): self
    {
        // si la variable data es null entonces llamar a generateData sino usar la data de ahi
        if (is_null($this->data))
            $this->preparateData();
        $this->buildPDF();
        return $this;
    }

    


    public function buildPDF()
    {
        
        $pdf = PDF::loadView($this->layout, $this->data);
        // Generar un nombre único para el archivo basándome en los id del empleado en el proyecto
        $fileName = 'constancia_' . $this->hash . '_' . time() . '.pdf';

        // Definir la ruta con el nombre único
        $filePath = storage_path('app/public/' . $fileName);

        // Guardar el PDF en la ruta
        $pdf->save($filePath);

        $this->pdfPath = $filePath;
        // eliminar el qr despues de generar el pdf
        //unlink($this->qrpath);

        // Descargar el PDF y eliminarlo al instante para que no quede en storage
        return $this;
    }

    public function downloadPDF()
    {
        return response()
            ->download($this->pdfPath)
            ->deleteFileAfterSend(true);
    }
}
