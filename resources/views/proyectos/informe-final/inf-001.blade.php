<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>INF-001 — {{ $informe->numero_registro }}</title>
    <style>
        body{margin:0;background:#eef2f7;color:#172033;font-family:Arial,sans-serif}.toolbar{position:sticky;top:0;display:flex;gap:8px;justify-content:center;padding:12px;background:#fff;border-bottom:1px solid #d9e0e8}.toolbar a,.toolbar button{border:0;border-radius:5px;padding:9px 14px;background:#07529b;color:#fff;text-decoration:none;cursor:pointer}.sheet{max-width:920px;margin:20px auto;background:#fff;padding:38px;box-shadow:0 3px 18px #ccd3dc}@media print{body{background:#fff}.toolbar{display:none}.sheet{max-width:none;margin:0;padding:0;box-shadow:none}}
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('proyectos.informe-final',$informe->proyecto) }}">Volver al formulario</a><a href="{{ route('informes-finales.inf-001.pdf',$informe) }}">Descargar PDF</a><button onclick="window.print()">Imprimir INF-001</button></div>
    <div class="sheet">@include('proyectos.informe-final.partials.inf-001-document')</div>
    @if($print)<script>window.addEventListener('load',()=>window.print())</script>@endif
</body>
</html>
