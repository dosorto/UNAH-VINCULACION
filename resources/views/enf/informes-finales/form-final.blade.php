<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informe final ENF - {{ $accion->nombre_accion }}</title>
    <style>
        body { margin: 0; background: #eef2f7; font-family: Arial, sans-serif; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-end; padding: 10px 18px; background: #fff; border-bottom: 1px solid #d7dee8; }
        .toolbar a { border: 1px solid #b9c6d8; border-radius: 6px; color: #002060; font-size: 13px; font-weight: 700; padding: 7px 10px; text-decoration: none; }
        .preview { max-width: 8.5in; margin: 18px auto; background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .16); }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('enf.acciones.informe-final.edit', $accion) }}">Volver</a>
        <a target="_blank" href="{{ route('enf.acciones.informe-final.print', $accion) }}">Imprimir PDF</a>
        <a href="{{ route('enf.acciones.informe-final.pdf', $accion) }}">Descargar PDF</a>
    </div>
    <main class="preview">
        @include('enf.informes-finales.partials.form-final-document', ['accion' => $accion, 'informe' => $informe, 'isPdf' => false])
    </main>
</body>
</html>
