<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe final ENF - {{ $accion->nombre_accion }}</title>
</head>
<body>
    @include('enf.informes-finales.partials.form-final-document', ['accion' => $accion, 'informe' => $informe, 'isPdf' => true])
</body>
</html>
