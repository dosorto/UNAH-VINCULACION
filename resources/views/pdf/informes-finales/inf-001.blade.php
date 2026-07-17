<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>INF-001 — {{ $informe->numero_registro }}</title>
    <style>
        @page { size: letter landscape; margin: 88px 112px 62px 72px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; }
        .official-header { position: fixed; top: -70px; left: 0; right: 0; height: 54px; }
        .official-header img { width: 385px; height: auto; }
        .official-contact { position: absolute; right: 4px; top: 4px; color: #17365d; font-size: 7px; line-height: 1.45; text-align: right; }
        .official-stripe { position: fixed; top: -88px; right: -112px; width: 20px; height: 792px; background: #fdc300; }
        .official-watermark { position: fixed; top: 105px; left: 285px; width: 350px; opacity: .055; z-index: -1; }
        .official-footer { position: fixed; right: 0; bottom: -42px; color: #4b5563; font-size: 7px; }
        .institution { margin-top: 0; }
        .institution > div { display: none; }
        .institution h1 { color: #17365d; font-size: 13px; text-transform: uppercase; }
        .institution h2 { color: #17365d; }
        h3 { margin: 12px 0 5px; padding: 5px 7px; background: #17365d; color: #fff; font-size: 9px; }
        h4 { color: #17365d; font-size: 8.5px; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th { background: #e7edf5; color: #17365d; }
        th, td { border-color: #6b7280; padding: 3px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="official-header">
        <img src="file://{{ public_path('images/enf/form-018-header.png') }}" alt="Universidad Nacional Autónoma de Honduras y Vicerrectoría Académica">
        <div class="official-contact">Dirección de Vinculación Universidad Sociedad<br>vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070 Ext. 110576</div>
    </div>
    <div class="official-stripe"></div>
    <img class="official-watermark" src="file://{{ public_path('images/enf/form-018-watermark.png') }}" alt="">
    <div class="official-footer">INF-001 · {{ $informe->numero_registro }}</div>

    @include('proyectos.informe-final.partials.inf-001-document')

</body>
</html>
