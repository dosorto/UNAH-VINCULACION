<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: letter;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            background-color: #fff;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* Permite que el contenido ocupe el espacio disponible */
        }

        .container {
            width: 100%;
            max-width: 816px;
            max-height: 1056px;
            margin: 0 auto;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
            /* Permite que el contenedor crezca para ocupar el espacio disponible */
        }

       

        .logo-space {
            height: 70px;
            background-color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
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

        a {
            color: inherit;
            text-decoration: none;
        }

        h1 {
            font-size: 18px;
            font-family: Aptos Narrow, sans-serif;
            text-align: center;
            margin-top: 20px;
        }

        .constancia {
            font-size: 14px;
            font-family: Arial, sans-serif;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
        }

        .constancia-texto {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            margin-top: 20px;
        }

        .constancia-texto p {
            text-align: justify;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .destacado {
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 10px 0;
            margin-top: 20px;
        }

        footer p {
            font-family: Helvetica, sans-serif;
            margin: 5px 0;
            font-size: 0.7em;
            color: #0023ad;
        }


        footer {
            text-align: center;
        }

        footer p {
            font-size: 0.7em;
            color: #0023ad;
        }

        .lucen {
            bottom: 60px;
            position: absolute;
            right: 0px;
            transform: scaleX(-1);
            /* Voltear horizontalmente (efecto espejo) */
            z-index: -1;
            /* Enviar al fondo */
        }

        .lucent {
            width: 12.5cm;
            height: 16cm;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        h1 {
            font-size: 18px;
            text-align: center;
            margin-top: 10px;
            margin-bottom: 20px;
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

        .firma {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .firma img {
            width: 150px;
            height: 110px;
        }

        .firma div {
            text-align: center;
            margin-top: 15px;
        }

        .nombre-firma {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .nombre-firma img {
            width: 150px;
            height: 110px;
            margin-bottom: 10px;
        }

        .nombre-firma div {
            text-align: center;
            margin-top: 15px;
        }

        .verificar-link a {
            color: #0023ad;
        }

        .verificar-link {
            text-align: right;
            margin-right: 35px;
            margin-top: 90px;
            margin-bottom: 48px;
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

        .logo-container img {
            width: 510px;
            height: auto;
            position: relative;
            z-index: 100;
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
            vertical-align: bottom;
            position: relative;
        }

        .celda-izquierda {
            width: 50%;
            height: 200px;
        }

        .celda-superior-derecha {
            width: 40%;
        }

        .celda-inferior-derecha-1 {
            width: 20%;
        }

        .celda-inferior-derecha-2 {
            width: 20%;
        }

        .celda-amarilla {
            width: 10%;
            height: 200px;
        }

        .rectangulo-interno {
            width: 80%;
            height: 70%;
            background-color: #f7d70f;
            position: absolute;
            padding-top: 10px;
            left: 50%;
        }

    </style>
</head>

<body>
    <div class="container">
        <div class="lucen">
            <img class="lucent" src='{{ public_path('/images/Image/Sol.png') }}' alt=""></img>

        </div>
        <div class="header">

            <table>
                <tr>
                    <td class="celda-izquierda" rowspan="2" style="vertical-align: top !important;">
                        <div class="logo-space">
                            <img src="file://{{ public_path('images/imagenvinculacion.jpg') }}" width="450px"
                                alt="Escudo de la UNAH">
                        </div>
                        <div class="codigo-texto" style="padding-left: 20px;">
                            Código de Verificación: <br>
                            <span style="font-size: 20px; font-weight: bold;"></span>
                        </div>
                    </td>
                    <td class="celda-superior-derecha" colspan="2" style="text-align: right;">
                        <div class="contact-info" style="">
                            <a href="mailto:vinculacion.sociedad@unah.edu.hn">vinculacion.sociedad@unah.edu.hn</a><br>
                            Tel. 2216-7070 Ext. 110576
                        </div>
                    </td>
                    <td class="celda-amarilla" rowspan="2">
                        <div class="rectangulo-interno"></div>
                    </td>
                </tr>
                <tr class="fila-2">
                    <td class="celda-inferior-derecha-1">

                    </td> 
                    <td class="celda-inferior-derecha-2" style="text-align: right;">
                        <div class="qr-code" style="">
                            <img src="{{ $qrCode }}" alt="QR Code" style="width: 80px; height: 80px;">
                        </div>
                    </td>
                </tr>
            </table>



        </div>

        <h1>CONSTANCIA DE ACTIVIDAD DE VINCULACIÓN</h1>

        <div class="constancia-texto">
            <p>
                La Suscrita Directora de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace CONSTAR
                que, el Profesor Dorian Ordoñez Osorno con número de empleado 14703 del departamento de
                ingeniería en sistema dependiente de UNAH Campus Choluteca, es parte del equipo que ejecuta y ha
                registrado la actividad de vinculación denominada Primer congreso Acuicultura " Innovación y desarrollo
                sostenible" Choluteca, Honduras 2024, la cual se ejecuta del 15 de marzo de 2024 hasta el 14 de marzo de
                2025.
                Asimismo, se hace constar que la entrega del informe intermedio y final de dicha actividad, está
                pendiente. En
                consecuencia, esta constancia tiene validez única para fines de inscripción en la Dirección de
                Vinculación
                Universidad – Sociedad. </p>
            <p>
                Dado en Ciudad Universitaria José Trinidad Reyes, a los cinco días del mes de febrero de dos mil
                veinticinco.
            </p>
        </div>
        <div class="firma">
            <img src="f.png" alt="Firma">
        </div>
        <div class="nombre-firma">
            <div>
                Oneyda Cleothilde Mendoza Zepeda<br>
                DIRECTORA DE VINCULACIÓN UNIVERSIDAD – SOCIEDAD<br>
                VRA-UNAH
            </div>
        </div>



        <div class="anio-academico">
            Año Académico 2025 "José Dionisio de Herrera"
        </div>

        <footer>
            <p>Universidad Nacional Autónoma de Honduras | CIUDAD UNIVERSITARIA | Tegucigalpa M.D.C. Honduras C.A |
                <a href="http://www.unah.edu.hn">www.unah.edu.hn</a>
            </p>
        </footer>
    </div>


    </div>
    </div>
</body>

</html>
