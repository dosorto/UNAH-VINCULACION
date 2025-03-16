<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }
        .container {
            width: 100%;
            max-width: 810px; /* Ancho de página A4 */
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 20px;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-space {
            height: 100px;
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
            padding: 20px;
        }

        .constancia-texto {
            width: 100%;
            max-width: 500px;
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
        }


    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-space">
                <img src="data:image/jpeg;base64,{{ $logoBase64 }}" width="500px" height="120px" alt="Escudo de la UNAH">
            </div>
            <div class="contact-info">
                <a href="mailto:vinculacion.sociedad@unah.edu.hn">vinculacion.sociedad@unah.edu.hn</a><br>
                Tel. 2216-7070 Ext. 110576
            </div>
            <h1>{{ $title }}</h1>
        </div>

        <div class="constancia">
            <div class="constancia-texto">
                <p>El Área Dirección Vinculación Universidad Sociedad, hace constar que:</p>
                <p>{{ $empleado->nombre_completo }} con número de empleado: 
                    <span class="destacado">{{ $empleado->numero_empleado }}</span> participó en el proyecto
                    <strong>"{{ $proyecto->nombre_proyecto }}"</strong>
                <p>Y para los fines que al interesado(a) convenga, se le extiende la presente Constancia en la Ciudad Universitaria, José Trinidad Reyes, a los veinte días del mes de enero del año dos mil veintitrés.</p>
            </div>
        </div>
        
        <div class="qr-code">
        <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code" style="width: 200px; height: 200px;">
        </div>

        <footer>
            <p>Universidad Nacional Autónoma de Honduras | CIUDAD UNIVERSITARIA | Tegucigalpa, M.C.D Honduras C.A | <a href="http://www.unah.edu.hn">www.unah.edu.hn</a></p>
        </footer>
    </div>
</body>
</html>
