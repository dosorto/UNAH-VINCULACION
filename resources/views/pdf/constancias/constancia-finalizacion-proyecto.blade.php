<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter portrait; margin: 0; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: "DejaVu Sans", Arial, sans-serif; font-size: 7.4pt; line-height: 1.14; margin: 0; }
        .constancia-header { border-bottom: .5pt solid #b8c1cf; height: 74pt; left: 30pt; position: fixed; right: 30pt; top: 8pt; z-index: 4; }
        .constancia-header-brand { display: block; height: auto; left: 0; position: absolute; top: 0; width: 268pt; }
        .constancia-contacto { color: #002060; font-size: 6.1pt; line-height: 1.25; position: absolute; right: 62pt; text-align: right; top: 6pt; width: 175pt; }
        .constancia-qr { height: 42pt; position: absolute; right: 10pt; top: 2pt; width: 42pt; }
        .constancia-accent { background: #ffc000; height: 57pt; position: absolute; right: -22pt; top: 0; width: 8pt; }
        .constancia-identificacion { bottom: 4pt; color: #002060; font-size: 6.5pt; left: 0; position: absolute; right: 0; }
        .constancia-identificacion strong { font-size: 7.3pt; }
        .constancia-identificacion span { float: right; }
        .constancia-watermark { left: 118pt; opacity: .075; position: fixed; top: 215pt; width: 285pt; z-index: 0; }
        .constancia-footer { bottom: 9pt; color: #4b5563; font-family: Arial, sans-serif; font-size: 5.9pt; left: 52pt; position: fixed; right: 52pt; text-align: center; z-index: 4; }
        .constancia-footer img { display: block; height: auto; margin: 0 auto 4pt; width: 410pt; }
        .constancia-page-number:before { content: counter(page); }
        .constancia-content { margin: 94pt 38pt 50pt; position: relative; z-index: 2; }
        .constancia-page-two { page-break-before: always; }
        h1 { color: #002060; font-family: Arial, sans-serif; font-size: 12.5pt; line-height: 1.05; margin: 0 0 10pt; text-align: center; }
        h2 { background: #001b44; color: #fff; font-family: Arial, sans-serif; font-size: 7.7pt; line-height: 1; margin: 8pt 0 4pt; padding: 4pt 6pt; page-break-after: avoid; }
        p { margin: 0 0 6pt; text-align: justify; }
        .constancia-data { border-collapse: collapse; page-break-inside: avoid; table-layout: fixed; width: 100%; }
        .constancia-data th, .constancia-data td { border: .55pt solid #5d6673; padding: 3pt 4pt; vertical-align: top; }
        .constancia-data th { background: #001b44; color: #fff; font-family: Arial, sans-serif; font-size: 6.25pt; font-weight: bold; line-height: 1.06; text-align: left; }
        .constancia-data td { font-size: 6.7pt; line-height: 1.1; }
        .constancia-data .label { background: #e8edf4; color: #001b44; font-family: Arial, sans-serif; font-weight: bold; width: 23%; }
        .constancia-data thead { display: table-header-group; }
        .constancia-signature { height: 182pt; margin-top: 17pt; page-break-inside: avoid; position: relative; text-align: center; }
        .constancia-signature-assets { height: 111pt; position: relative; }
        .constancia-signature-firma { left: 160pt; max-height: 80pt; max-width: 190pt; position: absolute; top: 27pt; }
        .constancia-signature-sello { left: 283pt; max-height: 74pt; max-width: 92pt; position: absolute; top: 9pt; }
        .constancia-signature-name { border-top: .65pt solid #374151; display: inline-block; font-size: 7pt; min-width: 245pt; padding-top: 5pt; }
        .constancia-note { color: #4b5563; font-size: 6pt; margin-top: 4pt; text-align: center; }
    </style>
</head>
<body>
    @php($fechaEmision = \Carbon\Carbon::parse(data_get($snapshot, 'constancia.fecha_emision'))->locale('es'))

    @include('pdf.constancias.partials.watermark')
    @include('pdf.constancias.partials.header')
    @include('pdf.constancias.partials.footer')

    <main class="constancia-content">
        <section>
            <h1>CONSTANCIA DE FINALIZACIÓN<br>DE ACCIÓN DE VINCULACIÓN</h1>
            <p>La Dirección de Vinculación Universidad-Sociedad de la Universidad Nacional Autónoma de Honduras hace <strong>CONSTAR</strong> que la acción de vinculación denominada <strong>{{ data_get($snapshot, 'proyecto.nombre') }}</strong>, con código de registro <strong>{{ data_get($snapshot, 'proyecto.codigo') }}</strong>, fue ejecutada y cuenta con Informe Final aprobado.</p>

            <h2>1. COORDINACIÓN DEL PROYECTO</h2>
            <table class="constancia-data"><thead><tr><th style="width:14%">Fase o rol</th><th style="width:24%">Nombre completo</th><th style="width:12%">N.º empleado</th><th style="width:15%">Categoría</th><th style="width:22%">Departamento</th><th style="width:13%">Tiempo</th></tr></thead><tbody><tr><td>{{ data_get($snapshot, 'coordinador.rol') }}</td><td>{{ data_get($snapshot, 'coordinador.nombre') }}</td><td>{{ data_get($snapshot, 'coordinador.numero_empleado') }}</td><td>{{ data_get($snapshot, 'coordinador.categoria') }}</td><td>{{ data_get($snapshot, 'coordinador.departamento') }}</td><td>{{ data_get($snapshot, 'coordinador.horas') }} horas</td></tr></tbody></table>

            <h2>2. EQUIPO PARTICIPANTE</h2>
            <table class="constancia-data"><thead><tr><th style="width:7%">N.º</th><th style="width:28%">Nombre completo</th><th style="width:13%">N.º empleado</th><th style="width:16%">Categoría</th><th style="width:23%">Departamento</th><th style="width:13%">Tiempo</th></tr></thead><tbody>
                @forelse(data_get($snapshot, 'equipo', []) as $persona)
                    <tr><td>{{ $loop->iteration }}</td><td>{{ data_get($persona, 'nombre') }}</td><td>{{ data_get($persona, 'numero_empleado') }}</td><td>{{ data_get($persona, 'categoria') }}</td><td>{{ data_get($persona, 'departamento') }}</td><td>{{ data_get($persona, 'horas') }} horas</td></tr>
                @empty
                    <tr><td colspan="6">No registrado</td></tr>
                @endforelse
            </tbody></table>

            <h2>3. DATOS PRINCIPALES DEL PROYECTO EJECUTADO</h2>
            <table class="constancia-data"><tbody>
                <tr><td class="label">Unidad académica</td><td>{{ data_get($snapshot, 'proyecto.unidad_academica') }}</td><td class="label">Período de ejecución</td><td>{{ data_get($snapshot, 'proyecto.periodo_ejecucion') }}</td></tr>
                <tr><td class="label">Comunidad beneficiada</td><td>{{ data_get($snapshot, 'proyecto.comunidad_beneficiada') }}</td><td class="label">Categoría de la acción</td><td>{{ data_get($snapshot, 'proyecto.categoria') }}</td></tr>
                <tr><td class="label">Beneficiarios hombres</td><td>{{ data_get($snapshot, 'beneficiarios.hombres') }}</td><td class="label">Beneficiarias mujeres</td><td>{{ data_get($snapshot, 'beneficiarios.mujeres') }}</td></tr>
                <tr><td class="label">Fecha del Informe Final</td><td>{{ data_get($snapshot, 'proyecto.fecha_informe_final') }}</td><td class="label">Estudiantes participantes</td><td>{{ data_get($snapshot, 'participacion.estudiantes') }}</td></tr>
                <tr><td class="label">Voluntarios docentes</td><td>{{ data_get($snapshot, 'participacion.voluntarios_docentes') }}</td><td class="label">Voluntarios estudiantes</td><td>{{ data_get($snapshot, 'participacion.voluntarios_estudiantes') }}</td></tr>
            </tbody></table>
        </section>

        <section class="constancia-page-two">
            <h2>4. INFORMACIÓN COMPLEMENTARIA DE EJECUCIÓN</h2>
            <table class="constancia-data"><tbody>
                <tr><td class="label">Voluntarios de personal administrativo</td><td>{{ data_get($snapshot, 'participacion.personal_administrativo') }}</td></tr>
                <tr><td class="label">Presupuesto ejecutado UNAH</td><td>{{ data_get($snapshot, 'presupuesto.moneda') }} {{ data_get($snapshot, 'presupuesto.unah') }}</td></tr>
                <tr><td class="label">Presupuesto ejecutado por contraparte</td><td>{{ data_get($snapshot, 'presupuesto.moneda') }} {{ data_get($snapshot, 'presupuesto.contraparte') }}</td></tr>
                <tr><td class="label">Total ejecutado</td><td>{{ data_get($snapshot, 'presupuesto.moneda') }} {{ data_get($snapshot, 'presupuesto.total') }}</td></tr>
            </tbody></table>
            <p style="margin-top:18pt;">Se expide la presente constancia en {{ data_get($snapshot, 'constancia.ciudad_emision') }}, a los {{ $fechaEmision->translatedFormat('d') }} días del mes de {{ $fechaEmision->translatedFormat('F') }} de {{ $fechaEmision->format('Y') }}, para los fines institucionales que correspondan.</p>

            <div class="constancia-signature">
                <div class="constancia-signature-assets">
                    @if($firma)<img class="constancia-signature-firma" src="{{ $firma }}" alt="Firma de la autoridad">@endif
                    @if($sello)<img class="constancia-signature-sello" src="{{ $sello }}" alt="Sello institucional">@endif
                </div>
                <div class="constancia-signature-name"><strong>{{ data_get($snapshot, 'autoridad.nombre') }}</strong><br>{{ data_get($snapshot, 'autoridad.cargo') }}</div>
            </div>
            <p class="constancia-note">Esta constancia puede verificarse mediante el código QR y el código visible indicado en el documento.</p>
        </section>
    </main>
</body>
</html>
