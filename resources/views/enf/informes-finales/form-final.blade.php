<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informe final ENF - {{ $accion->nombre_accion }}</title>
    <style>
        @page { size: letter portrait; margin: 0; }
        body { margin: 0; background: #eef2f7; font-family: Arial, sans-serif; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; padding: 12px; background: #fff; border-bottom: 1px solid #d9e0e8; }
        .toolbar a { border: 0; border-radius: 5px; padding: 9px 14px; background: #07529b; color: #fff; font-size: 13px; text-decoration: none; cursor: pointer; }
        .preview-scroll { overflow-x: auto; padding: 0 12px; }
        .sheet { position: relative; width: 816px; min-height: 1056px; margin: 20px auto; background: #fff; box-shadow: 0 3px 18px #aab3bf; overflow: hidden; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .preview-scroll { padding: 0; overflow: visible; }
            .sheet { width: auto; min-height: 0; margin: 0; box-shadow: none; overflow: visible; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('enf.acciones.informe-final.edit', $accion) }}">Volver</a>
        <a target="_blank" href="{{ route('enf.acciones.informe-final.print', $accion) }}">Imprimir PDF</a>
        <a href="{{ route('enf.acciones.informe-final.pdf', $accion) }}">Descargar PDF</a>
    </div>
    <div class="preview-scroll">
        <main class="sheet">
            @include('enf.informes-finales.partials.form-final-document', ['accion' => $accion, 'informe' => $informe, 'isPdf' => false])
        </main>
    </div>
</body>
</html>
