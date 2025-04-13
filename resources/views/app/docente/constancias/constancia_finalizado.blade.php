@php
    $solImage = $pdf ? public_path('/images/Image/Sol.png') : asset('images/Image/Sol.png');
    $logoImage = $pdf ? 'file://' . public_path('images/imagenvinculacion.jpg') : asset('images/imagenvinculacion.jpg');
    $firmaImage = $pdf ? public_path('f.png') : asset('f.png');
    $qrCode = $pdf ? 'file://' .   storage_path('app/public/' . $qrCode) : asset('storage/'.$qrCode);
    $firmaDirector = $pdf ? 'file://' . storage_path('app/public/'.$firmaDirector) : asset('images/firma.png');
    $selloDirector = $pdf ? 'file://' . storage_path('app/public/'.$selloDirector) : asset('images/sello.png'); 
   // dd($firmaDirector,$selloDirector);  
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        @page { size: letter; margin: 0; }
        
        .fondo {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
}
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            background-color: #fff;
            width: 100%;
            height: 100%;
            
        }
        .container {
            width: 100%;
            max-width: 816px;
            min-height: 1053px;
            height: 1053px;
            margin: 0 auto;
            box-sizing: border-box;
            overflow: hidden;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
            position: relative;
            border: 1px solid #0023ad;
        }
        .logo-space {
            height: 70px;
            margin-bottom: 20px;
            padding-left: 10px;
        }
        .contact-info {
            text-align: right;
            font-size: 14px;
            margin-bottom: 20px;
            font-family: Calibri, sans-serif;
            font-weight: bold;
            color: rgb(4, 4, 126);
        }
        h1 {
            font-size: 18px;
            font-family: Aptos Narrow, sans-serif;
            text-align: center;
            margin: 10px 0 20px;
        }
        .constancia-texto {
            width: 100%;
            max-width: 700px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .constancia-texto p {
            text-align: justify;
            margin-bottom: 10px;
            font-size: 16px;
        }
       
        .firma, .nombre-firma {
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .nombre-firma div {
            text-align: center;
            margin-top: 15px;
        }
        .verificar-link {
            text-align: right;
            margin: 90px 35px 48px;
        }
        .verificar-link a {
            color: #0023ad;
        }
        footer {
            text-align: center;
        }
        footer p {
            font-size: 0.7em;
            color: #0023ad;
        }
        .anio-academico {
            text-align: center;
            font-size: 0.7em;
            color: #0023ad;
            border-bottom: 1px dashed #0023ad;
            padding-bottom: 8px;
        }
        .header {
            padding-top: 10px;
        }
        .lucen {
            bottom: 60px;
            position: absolute;
            right: 0;
            transform: scaleX(-1);
            z-index: -1;
        }
        .lucent {
            width: 12.5cm;
            height: 16cm;
        }
        .codigo-texto {
            width: 100%;
            max-width: 700px;
            font-size: 14px;
            margin-right: 15px;
            color: black;
            white-space: nowrap;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        td {
            height: 100px;
            position: relative;
        }
        .celda-izquierda { width: 50%; height: 200px; }
        .celda-superior-derecha { width: 40%; }
        .celda-inferior-derecha-1, .celda-inferior-derecha-2 { width: 20%; }
        .celda-amarilla {
            width: 10%;
            height: 200px;
        }
        .rectangulo-interno {
            width: 80%;
            height: 70%;
            background-color: #f7d70f;
            padding-top: 10px;
            left: 50%;
        }
    </style>
</head>
<body>
  
<div class="container">
    <div class="lucen">
        <img class="lucent" src="{{ $solImage }}" alt="Fondo">
    </div>

    <table style="width: 100%; border-collapse: collapse; padding: 20px;">
        <tr>
            <!-- LOGO: más grande, ocupa todo el alto -->
            <td style="width: 60%; vertical-align: top; padding-right: 20px;">
                <img src="{{ $logoImage }}" alt="Escudo de la UNAH" style="width: 100%;">
            </td>
    
            <!-- TEXTO DE CONTACTO + RECTÁNGULO DERECHO -->
            <td style="width: 40%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <!-- TEXTO DE CONTACTO, ARRIBA Y AL INICIO -->
                        <td style="text-align: center; font-weight: bold; font-family: Arial, sans-serif; font-size: 14px; color: #0056b3; padding-left: 10px;">
                            vinculacion.sociedad@unah.edu.hn<br>
                            Tel. 2216-7070 Ext. 110576
                        </td>
    
                        <!-- RECTÁNGULO AMBAR MÁS GRUESO Y PEGADO A LA DERECHA -->
                        <td style="width: 20px; background-color: #FFBF00;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
        
    
    
    
    <h1>{{$titulo}}</h1>

    <div class="constancia-texto">
        {!!
            $texto
        !!}
    </div>

   

    <div class="fondo">
        <div class="firma">
            <table align="center" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <img src="{{$selloDirector}}" alt="Sello del director" style="display: block;" width="200px" >
                    <br>
                    <img src="{{$firmaDirector}}" alt="Firma del director" style="margin-top: -100px;" width="200px">
                  </td>
                </tr>
              </table>              
        </div>
    
        <div class="nombre-firma">
            <div>
               {{$nombreDirector}}<br>
               <center>
                <strong>
                    DIRECTOR/A DE VINCULACIÓN UNIVERSIDAD – SOCIEDAD <br>
                    VRA-UNAH
                </strong>
                
               </center>
    
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
            <tr>
               
                <!-- Celda izquierda: Código QR -->
                <td style="width: 50%; padding-lef: 20px; text-align: center; vertical-align: middle;">
                    <div style="
                        border: 2px solid #000;
                        padding: 10px;
                        display: inline-block;
                        font-size: 18px;
                        font-weight: bold;
                    ">
                        Código de Verificación: <br>
                        {{ $codigoVerificacion ?? 'N/A' }}
                    </div>
                </td>
        
                <!-- Celda derecha: Código de verificación con borde -->
                <td style="width: 50%; text-align: center; vertical-align: middle;">
                    <img src="{{ $qrCode }}" width="130px" height="130px"/>
                   
                </td>
            </tr>
        </table>
        <div class="anio-academico">
        {{ $anioAcademico}}
        </div>
    
        <footer>
            <p>Universidad Nacional Autónoma de Honduras | CIUDAD UNIVERSITARIA | Tegucigalpa M.D.C. Honduras C.A |
                <a href="http://www.unah.edu.hn">www.unah.edu.hn</a>
            </p>
        </footer>
    </div>
</div>
</body>
</html>
