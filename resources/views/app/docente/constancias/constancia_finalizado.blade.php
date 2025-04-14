<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        @page { size: letter; margin: 0; }

        .constancia-wrapper {
            width: 816px;
            height: 1035px;
        }

        .constancia-wrapper .container {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #fff;
            width: 816px;
            height: 1035px;
            min-width: 816px;
            min-height: 1035px;
            margin: 10px auto;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        
        .constancia-wrapper .fondo {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
        }

        .constancia-wrapper .logo-space {
            height: 70px;
            margin-bottom: 20px;
            padding-left: 10px;
        }

        .constancia-wrapper .contact-info {
            text-align: right;
            font-size: 14px;
            margin-bottom: 20px;
            font-family: Calibri, sans-serif;
            font-weight: bold;
            color: rgb(4, 4, 126);
        }

        .constancia-wrapper h1 {
            font-size: 18px;
            font-family: 'Aptos Narrow', sans-serif;
            text-align: center;
            margin: 10px 0 20px;
        }

        .constancia-wrapper .constancia-texto {
            width: 100%;
            max-width: 700px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .constancia-wrapper .constancia-texto p {
            text-align: justify;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .constancia-wrapper .firma,
        .constancia-wrapper .nombre-firma {
            align-items: center;
            justify-content: center;
        }

        .constancia-wrapper .nombre-firma div {
            text-align: center;
            margin-top: 15px;
        }

        .constancia-wrapper .verificar-link {
            text-align: right;
            margin: 90px 35px 48px;
        }

        .constancia-wrapper .verificar-link a {
            color: #0023ad;
        }

        .constancia-wrapper footer {
            text-align: center;
        }

        .constancia-wrapper footer p {
            font-size: 0.7em;
            color: #0023ad;
        }

        .constancia-wrapper .anio-academico {
            text-align: center;
            font-size: 0.7em;
            color: #0023ad;
            border-bottom: 1px dashed #0023ad;
            padding-bottom: 8px;
        }

        .constancia-wrapper .header {
            padding-top: 10px;
        }

        .constancia-wrapper .lucen {
            bottom: 60px;
            position: absolute;
            right: 0;
            transform: scaleX(-1);
            z-index: -1;
        }

        .constancia-wrapper .lucent {
            width: 12.5cm;
            height: 16cm;
        }

        .constancia-wrapper .codigo-texto {
            width: 100%;
            max-width: 700px;
            font-size: 14px;
            margin-right: 15px;
            color: black;
            white-space: nowrap;
        }

        .constancia-wrapper table {
            border-collapse: collapse;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .constancia-wrapper td {
            height: 100px;
            position: relative;
        }

        .constancia-wrapper .celda-izquierda { width: 50%; height: 200px; }
        .constancia-wrapper .celda-superior-derecha { width: 40%; }
        .constancia-wrapper .celda-inferior-derecha-1,
        .constancia-wrapper .celda-inferior-derecha-2 { width: 20%; }

        .constancia-wrapper .celda-amarilla {
            width: 10%;
            height: 200px;
        }

        .constancia-wrapper .rectangulo-interno {
            width: 80%;
            height: 70%;
            background-color: #f7d70f;
            padding-top: 10px;
            left: 50%;
        }
    </style>
</head>
<body>
    <div class="constancia-wrapper">
        <div class="container">
            <div class="lucen">
                <img class="lucent" src="{{ $solImage }}" alt="Fondo">
            </div>

            <table style="width: 100%; border-collapse: collapse; ">
                <tr>
                    <td style="width: 60%; vertical-align: top; padding-right: 20px;">
                        <img src="{{ $logoImage }}" alt="Escudo de la UNAH" style="width: 100%;">
                    </td>
                    <td style="width: 40%; vertical-align: top;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="text-align: center; font-weight: bold; font-family: Arial, sans-serif; font-size: 14px; color: #0056b3; padding-left: 10px;">
                                    vinculacion.sociedad@unah.edu.hn<br>
                                    Tel. 2216-7070 Ext. 110576
                                </td>
                                <td style="width: 20px; background-color: #FFBF00;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <h1>{{$titulo}}</h1>

            <div class="constancia-texto">
                {!! $texto !!}
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
                        <td style="width: 50%; text-align: center; vertical-align: middle;">
                            <img src="{{ $qrCode }}" width="130px" height="130px"/>
                        </td>
                    </tr>
                </table>

                <div class="anio-academico">
                    {{ $anioAcademico }}
                </div>

                <footer>
                    <p>
                        Universidad Nacional Autónoma de Honduras | CIUDAD UNIVERSITARIA | Tegucigalpa M.D.C. Honduras C.A |
                        <a href="http://www.unah.edu.hn">www.unah.edu.hn</a>
                    </p>
                </footer>
            </div>
        </div>
    </div>
</body>
</html>
