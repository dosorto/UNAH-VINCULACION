@php
    $headerPath = 'images/enf/form-018-header.png';
    $watermarkPath = 'images/enf/form-018-watermark.png';
    $headerUrl = $isPdf ? 'file://'.public_path($headerPath) : asset($headerPath);
    $watermarkUrl = $isPdf ? 'file://'.public_path($watermarkPath) : asset($watermarkPath);
@endphp
<style>
    .inf001-header { position: {{ $isPdf ? 'fixed' : 'absolute' }}; z-index: 3; top: {{ $isPdf ? '8pt' : '12pt' }}; left: {{ $isPdf ? '30pt' : '0' }}; right: {{ $isPdf ? '30pt' : '0' }}; height: 58pt; border-bottom: .45pt solid #d9d9d9; }
    .inf001-header img { display: block; width: 260pt; height: 58pt; }
    .inf001-contact { position: absolute; top: 17pt; right: 17pt; color: #002060; font-size: 6.5pt; line-height: 1.35; text-align: right; }
    .inf001-yellow-accent { position: {{ $isPdf ? 'fixed' : 'absolute' }}; z-index: 4; top: 8pt; right: 8pt; width: 12pt; height: 82pt; background: #f9c900; }
    .inf001-watermark { position: {{ $isPdf ? 'fixed' : 'absolute' }}; z-index: 0; top: {{ $isPdf ? '205pt' : '250pt' }}; left: {{ $isPdf ? '115pt' : '185pt' }}; width: 285pt; height: 111pt; opacity: .055; }
    .inf001-footer { position: fixed; right: 0; bottom: -27pt; color: #596273; font-size: 6.5pt; }
</style>
<header class="inf001-header">
    <img src="{{ $headerUrl }}" alt="Universidad Nacional Autónoma de Honduras y Vicerrectoría Académica">
    <div class="inf001-contact">Dirección de Vinculación Universidad Sociedad<br>vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070 Ext. 110576</div>
</header>
<div class="inf001-yellow-accent" aria-hidden="true"></div>
<img class="inf001-watermark" src="{{ $watermarkUrl }}" alt="">
@if($isPdf)<div class="inf001-footer">INF-001 · {{ $informe->numero_registro ?: 'Pendiente de asignación' }}</div>@endif
