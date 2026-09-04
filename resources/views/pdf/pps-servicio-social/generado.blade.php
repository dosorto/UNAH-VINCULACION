@php
    $formData = $formData ?? \App\Support\PpsServicioSocial\FormDvus014Data::from($pps);
    $fields = $formData['fields'] ?? [];
    $firmas = $formData['firmas'] ?? [];
    $coordinadorFirma = \App\Support\PpsServicioSocial\FormDvus014Data::coordinadorFirma($pps);
    $coordinador = $firmas['coordinador'] ?? null;
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $fechaGeneracion = now();
    $fechaInicio = $fields['fecha_inicio'] ?? null;
    $fechaFinalizacion = $fields['fecha_finalizacion'] ?? null;
    $fechaEnEspanol = static function ($fecha) use ($meses): string {
        if (! $fecha) {
            return '';
        }

        $fecha = $fecha instanceof \DateTimeInterface ? \Illuminate\Support\Carbon::instance($fecha) : \Illuminate\Support\Carbon::parse($fecha);

        return $fecha->day.' de '.$meses[$fecha->month - 1].' de '.$fecha->year;
    };
    $valor = static fn (string $campo, string $fallback = ''): string => trim((string) ($fields[$campo] ?? $fallback));
    $carrera = $valor('carrera', 'la carrera correspondiente');
    $centro = $valor('facultad_centro', 'UNAH');
    $institucion = $valor('nombre_institucion', 'la empresa o institución correspondiente');
    $destinatario = $valor('nombre_jefe_directo', 'A quien corresponda');
    $cargoDestinatario = $valor('cargo_jefe_directo', 'Jefe inmediato');
    $modalidad = $valor('modalidad_ejecucion', 'la modalidad indicada en el formulario');
    $coordinadorNombre = trim((string) ($coordinador['nombre'] ?? '')) ?: 'Coordinador(a) de la carrera';
    $coordinadorCargo = trim((string) ($coordinadorFirma?->etapa_nombre ?? '')) ?: 'Coordinador(a) de la carrera';
    $lugar = $valor('municipio') ?: $valor('departamento') ?: $centro;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter portrait; margin: 24mm 19mm 24mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font-family: Arial, Helvetica, "DejaVu Sans", sans-serif; font-size: 10.5pt; line-height: 1.35; margin: 0; }
        .watermark { bottom: 24mm; opacity: .08; position: fixed; right: -4mm; width: 105mm; z-index: -1; }
        .accent { height: 25mm; left: -19mm; position: absolute; top: -24mm; width: 5mm; }
        .header { border-bottom: .5pt solid #001b44; margin-bottom: 5mm; padding-bottom: 3mm; position: relative; width: 100%; }
        .header table, .footer table { border-collapse: collapse; width: 100%; }
        .brand { width: 75%; }
        .brand img { height: auto; width: 100%; }
        .contact { color: #001b44; font-size: 7.5pt; text-align: right; vertical-align: middle; width: 25%; }
        .year { color: #001b44; font-size: 9pt; font-weight: bold; margin: 2mm 0 5mm; text-align: center; }
        h1 { color: #001b44; font-size: 15pt; letter-spacing: .2pt; margin: 0 0 6mm; text-align: center; }
        .date { margin-bottom: 6mm; text-align: right; }
        .recipient { margin: 0 0 6mm 12mm; }
        p { margin: 0 0 4mm; text-align: justify; }
        .note { font-weight: bold; margin-top: 5mm; }
        .signature { margin-top: 18mm; page-break-inside: avoid; text-align: center; }
        .signature img { display: block; height: 18mm; margin: 0 auto 1mm; max-width: 70mm; object-fit: contain; }
        .signature-line { border-top: .7pt solid #111827; margin: 8mm auto 2mm; width: 70mm; }
        .footer { bottom: -15mm; color: #001b44; font-size: 7.5pt; left: 0; position: fixed; right: 0; text-align: center; }
        .footer-rule { border-top: .5pt solid #001b44; margin-bottom: 2mm; }
    </style>
</head>
<body>
    <img class="watermark" src="file://{{ public_path('assets/pdf/common/sol_gris.png') }}" alt="">
    <div class="header">
        <img class="accent" src="file://{{ public_path('assets/pdf/common/rectangulo_amarillo.png') }}" alt="">
        <table><tr>
            <td class="brand"><img src="file://{{ public_path('assets/pdf/common/vra.png') }}" alt="UNAH, Vicerrectoría Académica y Dirección de Vinculación Universidad-Sociedad"></td>
            <td class="contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070 Ext. 110576</td>
        </tr></table>
    </div>

    <div class="year">“Año Académico {{ $fechaGeneracion->year }} María Elena Bottazzi”</div>
    <h1>{{ $tipo === 'solicitud_practica' ? 'SOLICITUD DE PRÁCTICA' : 'AUTORIZACIÓN DE PPS' }}</h1>
    <div class="date">{{ $lugar }}, {{ $fechaEnEspanol($fechaGeneracion) }}</div>

    <div class="recipient">
        <strong>{{ $destinatario }}</strong><br>
        {{ $cargoDestinatario }}<br>
        {{ $institucion }}<br>
        Presente
    </div>

    @if($tipo === 'solicitud_practica')
        <p><strong>Estimado(a) señor(a):</strong></p>
        <p>Reciba de esta Coordinación muestras de respeto y consideración.</p>
        <p>Por este medio tengo el agrado de dirigirme a Usted, con el objetivo de manifestarle que un estudiante por egresar de la Carrera de <strong>{{ $carrera }}</strong> de la Universidad Nacional Autónoma de Honduras desea realizar la práctica profesional supervisada de <strong>{{ $valor('total_horas') }} horas</strong> en su institución, la cual será válida en modalidad <strong>{{ $modalidad }}</strong>.</p>
        <p>NOMBRE DEL ALUMNO: <strong>{{ $valor('nombre_estudiante') }}</strong><br>NÚMERO DE CUENTA: <strong>{{ $valor('numero_cuenta') }}</strong></p>
        <p>De ser favorecido el estudiante, agradeceré envíe por escrito el perfil del puesto a desempeñar, las funciones que desarrollará, fecha tentativa de inicio de la práctica, horario de trabajo y el nombre del Jefe Inmediato que se le asignará al practicante.</p>
        <p class="note">Observación: Esta solicitud no indica autorización de práctica. Posteriormente se realizará el análisis de las funciones del puesto para autorizar la práctica y la fecha oficial de comienzo.</p>
    @else
        <p>El suscrito Coordinador de la Carrera de <strong>{{ $carrera }}</strong> de <strong>{{ $centro }}</strong>, por este medio <strong>AUTORIZA</strong> al estudiante <strong>{{ $valor('nombre_estudiante') }}</strong>, con número de cuenta <strong>{{ $valor('numero_cuenta') }}</strong>, para realizar la Práctica Profesional Supervisada de <strong>{{ $valor('total_horas') }} horas</strong> en <strong>{{ $institucion }}</strong>, bajo la modalidad <strong>{{ $modalidad }}</strong>, iniciando el <strong>{{ $fechaEnEspanol($fechaInicio) }}</strong> y finalizando el <strong>{{ $fechaEnEspanol($fechaFinalizacion) }}</strong>.</p>
        <p>En virtud de haber cumplido con los requisitos académicos y administrativos exigidos por la UNAH, se extiende la presente autorización para los fines que el interesado convenga.</p>
    @endif

    <div class="signature">
        @if(!empty($coordinador['src']))
            <img src="{{ $coordinador['src'] }}" alt="Firma del coordinador">
        @else
            <div class="signature-line"></div>
        @endif
        <strong>{{ $coordinadorNombre }}</strong><br>
        {{ $coordinadorCargo }}<br>
        {{ $carrera }} · {{ $centro }}
    </div>

    <div class="footer"><div class="footer-rule"></div>Universidad Nacional Autónoma de Honduras · Dirección de Vinculación Universidad-Sociedad<br>“La Educación es la Primera Necesidad de la República”</div>
</body>
</html>
