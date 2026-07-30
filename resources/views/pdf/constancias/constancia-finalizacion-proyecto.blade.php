<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @include('pdf.constancias.partials.styles')
</head>
<body>
    @php($fechaEmision = \Carbon\Carbon::parse(data_get($snapshot, 'constancia.fecha_emision'))->locale('es'))

    @include('pdf.constancias.partials.watermark')
    @include('pdf.constancias.partials.header')
    @include('pdf.constancias.partials.footer')

    <main class="constancia-content">
        <div class="constancia-meta">
            <strong>{{ data_get($snapshot, 'constancia.numero') }}</strong><br>
            {{ $fechaEmision->translatedFormat('d \\d\\e F \\d\\e Y') }}
        </div>
        <section>
            <h1>CONSTANCIA DE FINALIZACIÓN<br>DE ACCIÓN DE VINCULACIÓN</h1>
            <p>La Dirección de Vinculación Universidad-Sociedad de la Universidad Nacional Autónoma de Honduras hace <strong>CONSTAR</strong> que la acción de vinculación denominada <strong>{{ data_get($snapshot, 'proyecto.nombre') }}</strong>, con código de registro <strong>{{ data_get($snapshot, 'proyecto.codigo') }}</strong>, fue ejecutada y cuenta con Informe Final aprobado.</p>

            <h2>1. COORDINACIÓN DEL PROYECTO</h2>
            <table class="constancia-data"><thead><tr><th style="width:14%">Fase o rol</th><th style="width:24%">Nombre completo</th><th style="width:12%">No. empleado</th><th style="width:15%">Categoría</th><th style="width:22%">Departamento</th><th style="width:13%">Tiempo</th></tr></thead><tbody><tr><td>{{ data_get($snapshot, 'coordinador.rol') }}</td><td>{{ data_get($snapshot, 'coordinador.nombre') }}</td><td>{{ data_get($snapshot, 'coordinador.numero_empleado') }}</td><td>{{ data_get($snapshot, 'coordinador.categoria') }}</td><td>{{ data_get($snapshot, 'coordinador.departamento') }}</td><td>{{ data_get($snapshot, 'coordinador.horas') }} horas</td></tr></tbody></table>

            <h2>2. EQUIPO PARTICIPANTE</h2>
            <table class="constancia-data"><thead><tr><th style="width:7%">No.</th><th style="width:28%">Nombre completo</th><th style="width:13%">No. empleado</th><th style="width:16%">Categoría</th><th style="width:23%">Departamento</th><th style="width:13%">Tiempo</th></tr></thead><tbody>
                @forelse(data_get($snapshot, 'equipo', []) as $persona)
                    <tr><td>{{ $loop->iteration }}</td><td>{{ data_get($persona, 'nombre') }}</td><td>{{ data_get($persona, 'numero_empleado') }}</td><td>{{ data_get($persona, 'categoria') }}</td><td>{{ data_get($persona, 'departamento') }}</td><td>{{ data_get($persona, 'horas') }} horas</td></tr>
                @empty
                    <tr><td colspan="6">No registrado</td></tr>
                @endforelse
            </tbody></table>

            <h2 class="constancia-section-spacing">3. DATOS PRINCIPALES DEL PROYECTO EJECUTADO</h2>
            <table class="constancia-data"><tbody>
                <tr><td class="label">Unidad académica</td><td>{{ data_get($snapshot, 'proyecto.unidad_academica') }}</td></tr>
                <tr><td class="label">Período de ejecución</td><td>{{ data_get($snapshot, 'proyecto.periodo_ejecucion') }}</td></tr>
                <tr><td class="label">Comunidad beneficiada</td><td>{{ data_get($snapshot, 'proyecto.comunidad_beneficiada') }}</td></tr>
                <tr><td class="label">Beneficiarios (hombres)</td><td>{{ data_get($snapshot, 'beneficiarios.hombres') }}</td></tr>
                <tr><td class="label">Beneficiarias (mujeres)</td><td>{{ data_get($snapshot, 'beneficiarios.mujeres') }}</td></tr>
                <tr><td class="label">Categoría de la acción</td><td>{{ data_get($snapshot, 'proyecto.categoria') }}</td></tr>
                <tr><td class="label">Fecha del informe final</td><td>{{ data_get($snapshot, 'proyecto.fecha_informe_final') }}</td></tr>
                <tr><td class="label">Voluntarios estudiantes</td><td>{{ data_get($snapshot, 'participacion.estudiantes') }}</td></tr>
                <tr><td class="label">Voluntarios docentes</td><td>{{ data_get($snapshot, 'participacion.voluntarios_docentes') }}</td></tr>
            </tbody></table>
        </section>

        <section class="constancia-page-two">
            <table class="constancia-data"><tbody>
                <tr><td class="label">Voluntarios de personal administrativo</td><td>{{ data_get($snapshot, 'participacion.personal_administrativo') }}</td></tr>
                <tr><td class="label">Presupuesto ejecutado UNAH</td><td>{{ data_get($snapshot, 'presupuesto.moneda') }} {{ data_get($snapshot, 'presupuesto.unah') }}</td></tr>
                <tr><td class="label">Presupuesto ejecutado por contraparte</td><td>{{ data_get($snapshot, 'presupuesto.moneda') }} {{ data_get($snapshot, 'presupuesto.contraparte') }}</td></tr>
                <tr><td class="label">Total ejecutado</td><td>{{ data_get($snapshot, 'presupuesto.moneda') }} {{ data_get($snapshot, 'presupuesto.total') }}</td></tr>
            </tbody></table>
            <p style="margin-top:25pt;">Se expide la presente constancia en {{ data_get($snapshot, 'constancia.ciudad_emision') }}, a los {{ $fechaEmision->translatedFormat('d') }} días del mes de {{ $fechaEmision->translatedFormat('F') }} de {{ $fechaEmision->format('Y') }}, para los fines institucionales que correspondan.</p>

            <div class="constancia-signature">
                <div class="constancia-signature-assets"></div>
                <div class="constancia-signature-name"><strong>{{ data_get($snapshot, 'autoridad.nombre') }}</strong><br>{{ data_get($snapshot, 'autoridad.cargo') }}</div>
            </div>
            <p class="constancia-note">Esta constancia puede verificarse mediante el código QR y el código visible indicado en el encabezado del documento.</p>
            <p class="constancia-validation-url">Verifique la autenticidad de esta constancia escaneando el código QR del encabezado.</p>
        </section>
    </main>
</body>
</html>
