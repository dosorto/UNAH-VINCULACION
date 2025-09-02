<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
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

        .header {
            padding: 10px;
            margin-bottom: 20px;
            display: flex;

        }

        .logo-space {
            height: 70px;
            background-color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
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

        .qr-code {
            text-align: center;
            margin-top: 30px;
            /* Asegura que el código QR esté al final */
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td>
                        <div class="logo-space">
                            <img src="file://{{ public_path('images/imagenvinculacion.jpg') }}" width="500px"
                                height="120px" alt="Escudo de la UNAH">
                        </div>

                    </td>
                    <td style="vertical-align: bottom;">
                        <div class="contact-info" style="margin-right: 20px;">
                            <a href="mailto:vinculacion.sociedad@unah.edu.hn">vinculacion.sociedad@unah.edu.hn</a><br>
                            Tel. 2216-7070 Ext. 110576
                        </div>
                    </td>
                </tr>
            </table>
            <h1 style="margin-top: 50px;">{{ $title }}</h1>
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: space-between;">
            <div class="constancia" style=" ">
                <div class="constancia-texto">

                    <p>
                        CONSTANCIA DE ACTIVIDAD DE VINCULACIÓN
                        La Suscrita Directora de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace
                        CONSTAR que, el Profesor {{ $empleado->nombre_completo }} con número de empleado
                        {{ $empleado->numero_empleado }} del
                        departamento de {{ $empleado->departamento_academico->nombre }} dependiente de
                        , es parte del equipo que ejecuta y ha registrado la actividad de vinculación
                        denominada {{ $proyecto->nombre_proyecto }} , Honduras 2024, la cual se ejecuta del
                        15 de marzo de 2024 hasta el 14 de marzo
                        de 2025. Asimismo, se hace constar que la entrega del informe intermedio y final de dicha
                        actividad, está pendiente. En consecuencia, esta constancia tiene validez únicamente para
                        fines de inscripción en la Dirección de Vinculación Universidad – Sociedad.
                        Dado en Ciudad Universitaria José Trinidad Reyes, a los cinco días del mes de febrero de
                        dos mil veinticinco.
                    </p>



                </div>
            </div>

            <div>
                <div class="qr-code">
                    <img src="{{ $qrCode }}" alt="QR Code" style="width: 200px; height: 200px;">
                </div>
                <center>
                    <a href="{{ $enlace }}" style="text-align: center; margin-top: 20px; font-size: 16px;">
                        ver constancia en línea
                    </a>
                </center>

                <footer>
                    <p>Universidad Nacional Autónoma de Honduras | CIUDAD UNIVERSITARIA | Tegucigalpa, M.C.D Honduras
                        C.A |
                        <a href="http://www.unah.edu.hn">www.unah.edu.hn</a>
                    </p>
                </footer>
            </div>
        </div>
    </div>
</body>

</html>
