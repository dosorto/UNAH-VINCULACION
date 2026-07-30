<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @include('pdf.constancias.partials.registro-styles')
</head>
<body>
    @php
        $fechaEmision = \Carbon\Carbon::parse(data_get($snapshot, 'constancia.fecha_emision'))->locale('es');
        $diaNum = $fechaEmision->day;
        $mes = $fechaEmision->translatedFormat('F');
        $anio = $fechaEmision->year;
        if (class_exists(\NumberFormatter::class)) {
            $fmt = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
            $diaLetras = $fmt->format($diaNum);
        } else {
            $dias = ["","uno","dos","tres","cuatro","cinco","seis","siete","ocho","nueve","diez","once","doce","trece","catorce","quince","dieciséis","diecisiete","dieciocho","diecinueve","veinte","veintiuno","veintidós","veintitrés","veinticuatro","veinticinco","veintiséis","veintisiete","veintiocho","veintinueve","treinta","treinta y uno"];
            $diaLetras = $dias[$diaNum] ?? (string)$diaNum;
        }
    @endphp

    @include('pdf.constancias.partials.registro-watermark')
    @include('pdf.constancias.partials.registro-header')
    @include('pdf.constancias.partials.registro-footer')

    <main class="registro-content">
        <div class="registro-meta">
            <strong>{{ data_get($snapshot, 'constancia.numero') }}</strong><br>
            {{ $fechaEmision->translatedFormat('d \\d\\e F \\d\\e Y') }}
        </div>

        <h1 class="registro-title">Constancia de Registro de Proyecto de Vinculación</h1>

        <p class="registro-body">La Suscrita Directora de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace <strong>CONSTAR</strong> que, el Profesor <strong>{{ data_get($snapshot, 'coordinador.nombre') }}</strong> con número de empleado {{ data_get($snapshot, 'coordinador.numero_empleado') }} del departamento {{ data_get($snapshot, 'coordinador.departamento') }} dependiente de {{ data_get($snapshot, 'proyecto.unidad_academica') }}, coordina y ha registrado el proyecto de Vinculación denominado <strong>{{ data_get($snapshot, 'proyecto.nombre') }}</strong>, el cual se ejecuta a partir del {{ data_get($snapshot, 'proyecto.fecha_inicio') }} hasta el {{ data_get($snapshot, 'proyecto.fecha_fin') }}.</p>

        <p class="registro-body">En consecuencia, esta constancia no tiene validez para efecto de reclasificación, sino para validez de carga académica del {{ data_get($snapshot, 'constancia.periodo_academico') }}, según Artículo 277 de las Normas Académicas.</p>

        <p class="registro-fecha">Dado en {{ data_get($snapshot, 'constancia.ciudad_emision') }}, a los {{ $diaLetras }} días del mes de {{ $mes }} de {{ $anio }}.</p>

        <div class="registro-signature">
            @if(filled($firma) || filled($sello))
                <div class="registro-signature-assets">
                    @if(filled($firma))
                        <img class="registro-signature-firma" src="{{ $firma }}" alt="Firma">
                    @endif
                    @if(filled($sello))
                        <img class="registro-signature-sello" src="{{ $sello }}" alt="Sello">
                    @endif
                </div>
            @else
                <div class="registro-signature-placeholder"></div>
            @endif
            <div class="registro-signature-name">
                <strong>{{ data_get($snapshot, 'autoridad.nombre') }}</strong><br>
                {{ data_get($snapshot, 'autoridad.cargo') }}<br>
                VRA-UNAH
            </div>
        </div>

        <p class="registro-observacion">Observación: Esta constancia no tiene validez para efectos de calificación en los méritos de la función de vinculación establecidos en las Normas de la UNAH, sino para validez de registro de la función de vinculación en la asignación académica, según Artículo 277 de las Normas Académicas.</p>

        <div class="registro-bottom-row">
            <div class="registro-vigencia">
                <span class="registro-vigencia-box">Válida durante el {{ data_get($snapshot, 'constancia.periodo_academico') }}</span>
            </div>
            <div class="registro-validation">
                <p>Verifique la autenticidad de esta constancia<br>escaneando el código QR del encabezado.</p>
            </div>
        </div>

        <div class="registro-academic-year">Año Académico {{ $fechaEmision->year }} &ldquo;Doctora María Elena Botazzi&rdquo;</div>
    </main>
</body>
</html>
