<?php

namespace App\Services\Constancia;

use App\Models\Constancia\Constancia as ConstanciaModel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;

class MakeConstancia
{
    // datos requeridos
    private $titulo;
    private $texto;
    private $layout;
    private $firmaDirector;
    private $selloDirector;
    private $anioAcademico;

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
        $this->buildQR();
        // si la variable data es null entonces llamar a generateData sino usar la data de ahi
        if (is_null($this->data))
            $this->preparateData();


        $this->buildPDF();

        return $this;
    }

    public function buildQRBase64()
    {
        // Enlace que deseas codificar en el QR
        $enlace = route('verificacion_constancia', ['hash' => $this->hash]);

        // Generar el código QR como imagen binaria en memoria
        $qrBinary = base64_encode(QrCode::format('png')
            ->size(200)
            ->errorCorrection('H')
            ->generate('string'));

          

        // Codificar la imagen binaria en base64
        $this->qrpath = $qrBinary;
        // Retornar el base64
        return $this->qrpath;
    }





    public function buildQR()
    {

        $qrCodeName = $this->hash . '.png';
        $qrcodePath = storage_path('app/public/' . $qrCodeName);
        $enlace =  route('verificacion_constancia', ['hash' => $this->hash]);

        // Generar el código QR como imagen base64
        QrCode::format('png')
            ->size(200)
            ->errorCorrection('H')
            ->generate($enlace, $qrcodePath);

        // Obtener la URL de la imagen QR
        $qrCodeUrl =  $qrCodeName;

        // Devolver la URL de la imagen QR
        $this->qrpath = $qrCodeUrl;
    }


    public function buildPDF()
    {
        $this->data['pdf']  = true;
        $this->data['qrCode'] = $this->qrpath;

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
