<!doctype html>
<html lang="es"><head><meta charset="utf-8"><style>
@page{margin:14mm 17mm 15mm}body{font-family:Arial,Helvetica,sans-serif;font-size:10.5pt;line-height:1.3;color:#111}.watermark{position:fixed;right:-8mm;bottom:18mm;width:105mm;opacity:.09;z-index:-1}.chrome{padding-bottom:3mm;margin-bottom:3mm}.chrome table,.facts{width:100%;border-collapse:collapse}.brand{width:100%}.brand img{width:100%;height:auto}.contact{display:none}.year{text-align:center;font-size:9pt;font-weight:bold;margin:1mm 0 3mm}h1{text-align:center;color:#06265d;font-size:15pt;margin:0 0 3mm}.date{text-align:right;margin-bottom:3mm}.recipient{margin:0 0 4mm 18mm;font-weight:bold}p{margin:0 0 3.5mm;text-align:justify}.facts{margin:3mm 0}.facts td{border:.5pt solid #777;padding:1.8mm}.facts td:first-child{width:38%;background:#edf0f4;font-weight:bold}.note{font-weight:bold;margin-top:4mm}.signature{margin-top:12mm;text-align:center}.signature-line{border-top:1px solid #111;width:65%;margin:9mm auto 2mm}.footer{position:fixed;bottom:-7mm;left:0;right:0;border-top:1px dashed #777;padding-top:2mm;text-align:center;font-size:7.5pt;color:#06265d}.motto{position:fixed;bottom:1mm;left:0;right:0;text-align:center;font-size:8pt;font-style:italic;font-weight:bold}
</style></head><body>
<img class="watermark" src="file://{{ public_path('assets/pdf/common/sol_gris.png') }}" alt=""><div class="chrome"><table><tr><td class="brand"><img src="file://{{ public_path('assets/pdf/common/vra.png') }}" alt="UNAH, Vicerrectoría Académica y Dirección de Vinculación Universidad-Sociedad"></td></tr></table></div>
@php
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $fechaGeneracion = now();
    $coordinadorFirma = $pps->firmasDeEtapa->first(fn ($firma) => str_contains(mb_strtolower((string) ($firma->etapa_nombre ?? '')), 'coordinador'));
    $coordinadorNombre = $coordinadorFirma?->empleado?->nombre_completo ?: 'Coordinador(a) de la carrera';
    $coordinadorCargo = $coordinadorFirma?->etapa_nombre ?: 'Coordinador(a) de la carrera';
    $carrera = trim(preg_replace('/\s+(UNAH[- ]?C(h|h)oluteca|Choluteca)$/iu', '', (string) $pps->carrera));
    $centro = trim((string) $pps->facultad_centro) ?: 'UNAH';
    $lugarPractica = trim((string) ($pps->municipio ?: $pps->departamento ?: $pps->facultad_centro ?: 'Tegucigalpa'));
    $cargoDestinatario = trim((string) $pps->cargo_jefe_directo);
    if ($cargoDestinatario === '' || mb_strtolower($cargoDestinatario) === 'jefazo') $cargoDestinatario = 'Jefe inmediato';
    $tratamiento = str_starts_with(mb_strtolower($cargoDestinatario), 'ingenier') ? 'Ingeniero' : 'señor(a)';
@endphp
<div class="year">“Año Académico {{ $fechaGeneracion->year }} María Elena Bottazzi”</div>
<h1>{{ $tipo === 'solicitud_practica' ? 'SOLICITUD DE PRÁCTICA' : 'AUTORIZACIÓN DE PRÁCTICA PROFESIONAL' }}</h1>
<div class="date">{{ $lugarPractica }}, {{ $fechaGeneracion->day }} de {{ $meses[$fechaGeneracion->month - 1] }} {{ $fechaGeneracion->year }}</div>
@if($tipo === 'solicitud_practica')
<div class="recipient">{{ $tratamiento }}<br>{{ $pps->nombre_jefe_directo ?: 'A quien corresponda' }}<br>{{ $cargoDestinatario }}<br>{{ $pps->nombre_institucion }}<br>Presente</div>
<p><strong>Estimado {{ $tratamiento }}:</strong></p>
<p>Reciba de esta Coordinación muestras de respeto y consideración.</p>
<p>Por este medio tengo el agrado de dirigirme a Usted, con el objetivo de manifestarle que un estudiante por egresar de la Carrera de {{ $carrera }} de la Universidad Nacional Autónoma de Honduras desea realizar la práctica profesional supervisada de <strong>{{ $pps->total_horas }} horas</strong> en su institución, la cual será válida solo en modalidad <strong>{{ $pps->modalidad_ejecucion }}</strong>.</p>
<p>NOMBRE DEL ALUMNO: <strong>{{ $pps->nombre_estudiante }}</strong><br>NÚMERO DE CUENTA: <strong>{{ $pps->numero_cuenta }}</strong></p>
<p>De ser favorecido el estudiante, agradeceré envíe por escrito el perfil del puesto a desempeñar, las funciones que desarrollará, fecha tentativa de inicio de la práctica, horario de trabajo (máximo 40 horas semanales y 8 horas diarias sin contabilizar el tiempo de almuerzo) y el nombre del Jefe Inmediato que se le asignará al practicante.</p>
<p class="note">Observación: Esta solicitud no indica autorización de práctica, posteriormente se realiza un análisis de las funciones del puesto para autorizar la práctica y la fecha oficial de comienzo.</p>
@else
<p>El suscrito Coordinador de la Carrera de {{ $carrera }} de {{ $centro }}, por este medio <strong>AUTORIZA</strong> al estudiante <strong>{{ $pps->nombre_estudiante }}</strong>, con número de cuenta <strong>{{ $pps->numero_cuenta }}</strong>, para realizar la Práctica Profesional Supervisada de <strong>{{ $pps->total_horas }} horas</strong> en {{ $pps->nombre_institucion }}, bajo la modalidad {{ $pps->modalidad_ejecucion }}, iniciando el {{ $pps->fecha_inicio?->format('d/m/Y') }} y finalizando el {{ $pps->fecha_finalizacion?->format('d/m/Y') }}.</p><p>En virtud de haber cumplido con los requisitos académicos y administrativos exigidos por la UNAH, se extiende la presente autorización para los fines que el interesado convenga.</p>
@endif
<div class="signature"><div class="signature-line"></div><strong>{{ $coordinadorNombre }}</strong><br>{{ $coordinadorCargo }}<br>{{ $carrera }} · {{ $centro }}</div><div class="motto">“La Educación es la Primera Necesidad de la República”</div><div class="footer">Universidad Nacional Autónoma de Honduras | {{ $centro }} | Dirección de Vinculación Universidad-Sociedad</div>
</body></html>
