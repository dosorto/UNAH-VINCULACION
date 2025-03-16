<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;

class PDFController extends Controller
{
    public function generatePDF()
    {
        $qrcodePath = storage_path('app/qrcode.png'); // Ruta donde se guardará
        QrCode::format('png')->size(200)->errorCorrection('H')->generate('string', $qrcodePath);
    
        $data = [
            'title' => 'Welcome to Online Web Tutor',
            'qrcode' => $qrcodePath // Pasamos la ruta
        ];
    
        $pdf = PDF::loadView('my-pdf', $data);
    
        return $pdf->download('onlinewebtutor.pdf');
  
        return $pdf->download('onlinewebtutor.pdf');
    }

    public function verVista(){

        $qrcode = base64_encode(QrCode::format('png')->size(200)->errorCorrection('H')->generate('string'));
        
        $data = [
            'title' => 'Welcome to Online Web Tutor',
            'qrcode' => $qrcode
        ];  
              
        return view('my-pdf', $data);
    }
}
