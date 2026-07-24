<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista previa INF-001</title>
    <style>
        @page{size:letter portrait;margin:0}body{margin:0;background:#eef2f7;font-family:Arial,sans-serif}.toolbar{position:sticky;z-index:10;top:0;display:flex;flex-wrap:wrap;gap:8px;justify-content:center;padding:12px;background:#fff;border-bottom:1px solid #d9e0e8}.toolbar a{border:0;border-radius:5px;padding:9px 14px;background:#07529b;color:#fff;text-decoration:none;cursor:pointer}.preview-scroll{overflow-x:auto;padding:0 12px}.sheet{position:relative;width:816px;min-height:1056px;margin:20px auto;background:#fff;box-shadow:0 3px 18px #aab3bf;overflow:hidden}@media print{body{background:#fff}.toolbar{display:none}.preview-scroll{padding:0;overflow:visible}.sheet{width:auto;min-height:0;margin:0;box-shadow:none;overflow:visible}}
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('historialproyecto',$informe->proyecto) }}">Volver al proyecto</a><a target="_blank" href="{{ route('informes-finales.inf-001.print',$informe) }}">Imprimir PDF</a><a href="{{ route('informes-finales.inf-001.pdf',$informe) }}">{{ $esBorrador ? 'Descargar PDF preliminar' : 'Descargar PDF final' }}</a></div>
    <div class="preview-scroll"><main class="sheet">@include('proyectos.informe-final.partials.inf-001-document', ['isPdf' => false])</main></div>
</body>
</html>
