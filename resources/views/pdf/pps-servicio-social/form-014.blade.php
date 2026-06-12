<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FORM-DVUS-014</title>
    <style>
        body {
            background: #fff;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    @include('components.pps-servicio-social.form-014', [
        'registro' => $registro,
        'formData' => $formData ?? null,
        'isPdf' => true,
    ])
</body>
</html>
