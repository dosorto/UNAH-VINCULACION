<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>INF-001 — {{ $informe->numero_registro }}</title>
    <style>
        body{margin:0;background:#eef2f7;color:#172033;font-family:Arial,sans-serif}.toolbar{position:sticky;z-index:5;top:0;display:flex;gap:8px;justify-content:center;padding:12px;background:#fff;border-bottom:1px solid #d9e0e8}.toolbar a,.toolbar button{border:0;border-radius:5px;padding:9px 14px;background:#07529b;color:#fff;text-decoration:none;cursor:pointer}.sheet{position:relative;max-width:1100px;min-height:760px;margin:20px auto;background:#fff;padding:90px 105px 55px 65px;box-shadow:0 3px 18px #ccd3dc;overflow:hidden}.preview-header{position:absolute;top:22px;left:65px;right:70px;border-bottom:1px solid #d8dee7;padding-bottom:8px}.preview-header img{width:385px}.preview-contact{position:absolute;right:0;top:0;color:#17365d;font-size:10px;line-height:1.45;text-align:right}.preview-stripe{position:absolute;right:0;top:0;bottom:0;width:18px;background:#fdc300}.preview-watermark{position:absolute;z-index:0;left:34%;top:170px;width:360px;opacity:.055}.document-content{position:relative;z-index:1}@media print{body{background:#fff}.toolbar{display:none}.sheet{max-width:none;margin:0;padding:0;box-shadow:none}.preview-header,.preview-stripe,.preview-watermark{display:none}}
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('proyectos.informe-final',$informe->proyecto) }}">Volver al formulario</a><a href="{{ route('informes-finales.inf-001.pdf',$informe) }}">Descargar PDF</a><button onclick="window.print()">Imprimir INF-001</button></div>
    <div class="sheet"><div class="preview-header"><img src="{{ asset('images/enf/form-018-header.png') }}" alt="UNAH y Vicerrectoría Académica"><div class="preview-contact">Dirección de Vinculación Universidad Sociedad<br>vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070 Ext. 110576</div></div><div class="preview-stripe"></div><img class="preview-watermark" src="{{ asset('images/enf/form-018-watermark.png') }}" alt=""><div class="document-content">@include('proyectos.informe-final.partials.inf-001-document')</div></div>
    @if($print)<script>window.addEventListener('load',()=>window.print())</script>@endif
</body>
</html>
