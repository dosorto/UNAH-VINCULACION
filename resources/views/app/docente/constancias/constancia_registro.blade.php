<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>

    <style>
        @page {
            margin: 3cm 0cm 3.2cm 0.5cm;
        }
        .header {
            position: fixed;
            top: -3cm;
            left: 0;
            width: 100%;
            height: 3cm;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }
        .imgHeader {
            float: left;
            width: 3cm;
        }
        .imgHeader img {
            width: 10cm;
            height: auto;
        }
        .infoHeader {
            text-align: right;
            font-size: 12px;
            line-height: 1.4;
            white-space: nowrap;
            margin-right: 35px;
        }
        .infoHeader p {
            margin: 0;
            line-height: 1.4;
            white-space: nowrap;
            font-family: Calibri, sans-serif;
            font-weight: bold;
            color: rgb(4, 4, 94);
            line-height: 1.4;
        }
        .header-yellow-box {
            position: absolute;
            top: 0;
            right: 0;
            width: 25px;
            height: 100px;
            background-color: #f8d50f;
        }
        .constancia-texto {
            max-width: 7in;
            margin: 20px auto 0;
            font-family: Arial, sans-serif;
            line-height: 1.0;
        }
        .constancia-texto p {
            text-align: justify;
            margin: 10px 0;
            font-size: 16px;
            font-family: Arial, sans-serif;
            line-height: 1.2;
        }
        .tabla-responsables {
            width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
            font-size: 15px;
        }
        .tabla-responsables th,
        .tabla-responsables td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: left;
        }
        .tabla-responsables th {
            background: #0f0258;
            color: #fff;
            font-weight: bold;
        }
        #fecha-automatica { 
            font-family: Arial, sans-serif;
            text-align: justify;
            font-size: 16px;
            margin-top: 10px;
            margin-bottom: 60px;
            line-height: 1.0;
        }
        .firma-container {
            position: relative;
            text-align: center;
            margin-top: 20px;
            height: 100px;
        }
        .firma {
            display: inline-block;
            position: relative;
        }
        .firma img {
            display: block;
        }
        .sello {
            position: absolute;
            top: -25px;
            right: -40px;
            z-index: 2;
        }
        .nombre-firma {
            text-align: center;
            margin-top: 2px;
            font-weight: bold;
        }
        .observacion {
            margin: 3px auto 0;
            padding: 10px 15px;
            border-left: 4px solid #ffffff;
            text-align: justify;
            font-size: 13px;
            max-width: 9in;
            page-break-inside: avoid;
            page-break-before: auto;
            min-height: 130px;
        }
        .footer {
            position: fixed;
            bottom: -3.2cm;
            left: 0;
            right: 0;
            height: 3.2cm;
            padding: 0 0.2in;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
            z-index: 10;
        }
        .footer-logo img {  
            position: relative;    
            margin-top: 0px; 
            margin-bottom: -20px;                 
        }
        .footer-year {
            text-align: right;
            font-size: 13px;
            line-height: 1.2;
            color: #032e5c;
            margin-bottom: 45px;
            margin-right: 85px;
        }
        .footer-bottom {
            text-align: justify;
            margin-bottom: 10px;
        }
        .footer-bottom hr {
            border: none;
            border-top: 1px dashed #063363;
            margin: -35px;
            margin-bottom: 15px;
        }
        .footer-bottom p {
            font-size: 12px;
            color: #053972;
            margin: 0;
            text-align: justify;
            font-family: Arial, sans-serif;
            padding-bottom: -40px;
        }
        .footer-blue-box {
            position: fixed;
            padding: 0px;
            margin-left: 730px;
            margin-top: -70px;
            width: 50px;
            height: 50px;
            background-color: #061761;
        }
        body::before {
            content: '';
            position: fixed;
            bottom: 0;
            right: 0;
            width: 290px;
            height: 550px;
            background-image: url('{{ $lucenLogo }}');
            background-size: contain;
            background-repeat: no-repeat;
            opacity: 0.9;
            pointer-events: none;
            z-index: -1;
            box-shadow: none;
            filter: none;
        }
    </style>
</head>

<body>
    <div class="footer">
        <div class="footer-logo">
            <img src="{{ $footerLogo }}" alt="logo" style="height: 70px;">
        </div>
        <div class="footer-year">
            {{ $anioAcademico }}
        </div>
        <div class="footer-bottom">
            <hr>
            <p>Universidad Nacional Autónoma de Honduras | CIUDAD UNIVERSITARIA | Tegucigalpa M.D.C. Honduras C.A | www.unah.edu.hn</p>
        </div>
        <div class="footer-blue-box"></div>
    </div>

    <div class="header">
        <div class="imgHeader"">
            <img src="{{ $headerLogo }}">
        </div>
        <div class="infoHeader">
            <p>Tel. 2216-6100 &nbsp; Ext. 110576</p>
            <p>Correo Electrónico:</p>
            <p>vinculacion.sociedad@unah.edu.hn</p>
        </div>
        <div class="header-yellow-box"></div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: -40px; margin-left: 0; padding-left: 0;">
        <tr>
            <td style="width: 40%; text-align: left; vertical-align: top; padding-left: 400px; padding-top: -80px;">
                <div style="border: 1.5px solid #000; padding: 8px 10px; display: inline-block; font-size: 14px; font-weight: bold; background-color: #ffffff; white-space: nowrap;">  
                    Código Verificación:
                    <span style="font-size: 14px; color: #00060c;">
                        {{ $codigoVerificacion ?? 'N/A' }}
                    </span>
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top; padding-right: 65px; padding-top: -80px;">
                <img src="{{ $qrCode }}" width="100" height="100" style="border: 1px solid #ddd; margin-left: 20px;" />
            </td>
        </tr>
    </table>

    <div style="font-family: Arial, sans-serif; font-size: 1.3em; font-weight: bold; margin-bottom: 2px; text-align: center; line-height: 1.4;">
        {{ $correlativo }}
    </div>
    <div style="font-family: Arial, sans-serif; font-size: 1.2em; font-weight: bold; margin-bottom: 10px; text-align: center; line-height: 1.2;">
        {!! str_replace('REGISTRO ', 'REGISTRO<br>', $titulo) !!}
    </div>

    <div class="constancia-texto">
        <p>{!! $texto !!}</p>
        
        <table class="tabla-responsables">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nombre completo</th>
                    <th>No. Empleado</th>
                    <th>Categoría</th>
                    <th>Departamento</th>
                    <th>Rol</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1.</td>
                    <td>{{ $nombreEmpleado }}</td>
                    <td>{{ $numeroEmpleado }} </td>
                    <td>{{ $categoria }}</td>
                    <td>{{ $nombreDepartamento }}</td>
                    <td>{{ $rol }}</td>
                </tr>
            </tbody>
        </table>

        @php
            $hoy = \Carbon\Carbon::today()->locale('es');
            $diaNum = $hoy->day;
            $mes = $hoy->translatedFormat('F');
            $anio = $hoy->year;
            if (class_exists(\NumberFormatter::class)) {
                $fmt = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
                $diaLetras = $fmt->format($diaNum);
            } else {
                $dias = ["","uno","dos","tres","cuatro","cinco","seis","siete","ocho","nueve","diez","once","doce","trece","catorce","quince","dieciséis","diecisiete","dieciocho","diecinueve","veinte","veintiuno","veintidós","veintitrés","veinticuatro","veinticinco","veintiséis","veintisiete","veintiocho","veintinueve","treinta","treinta y uno"];
                $diaLetras = $dias[$diaNum] ?? (string)$diaNum;
            }
        @endphp

        <p id="fecha-automatica">
            Dado en Ciudad Universitaria José Trinidad Reyes, a los {{ $diaLetras }} días del mes de {{ $mes }} de {{ $anio }}.
        </p>

        <div class="firma-container">
            <div class="firma">
                <div class="sello">
                    <img src="{{$selloDirector}}" alt="Sello de la UNAH" width="120">
                </div>
                <img src="{{$firmaDirector}}" alt="Firma del Director" width="120">
            </div>
        </div>

        <div class="nombre-firma">
            {{$nombreDirector}}<br>
            Director de Vinculación Universidad – Sociedad<br>
            VRA-UNAH
        </div>

        <div class="observacion">
            <strong>Observación:</strong> Esta constancia no tiene validez para efectos de calificación en los
            méritos de la función de vinculación establecidos en las Normas de la UNAH, sino para validez de
            registro de la función de vinculación en la asignación académica, según Artículo 277 de las Normas
            Académicas.
        </div>
    </div>
</body>
</html>