<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 40px 52px; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.55; }
        .top { border-bottom: 4px solid #001b66; padding-bottom: 16px; }
        .brand { color: #001b66; font-size: 20px; font-weight: 700; }
        .sub { color: #475569; font-size: 11px; text-transform: uppercase; }
        .number { color: #001b66; font-weight: 700; margin-top: 18px; text-align: right; }
        h1 { color: #001b66; font-size: 18px; margin: 38px 0 22px; text-align: center; text-transform: uppercase; }
        .content { font-size: 13px; text-align: justify; }
        .box { border: 1px solid #cbd5e1; margin: 24px 0; padding: 14px; }
        .row { margin-bottom: 8px; }
        .label { color: #001b66; font-weight: 700; }
        .footer { border-top: 1px solid #cbd5e1; bottom: 28px; color: #475569; font-size: 10px; left: 52px; position: fixed; right: 52px; padding-top: 10px; }
        .qr { margin-top: 34px; text-align: right; }
        .signature { margin-top: 34px; text-align: center; }
        .signature-assets { height: 86px; position: relative; }
        .signature-firma { max-height: 74px; max-width: 190px; }
        .signature-sello { max-height: 82px; max-width: 120px; margin-left: -26px; opacity: .92; }
        .signature-line { border-top: 1px solid #0f172a; display: inline-block; min-width: 260px; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="top">
        <div class="brand">UNAH - VRA - Direccion de Vinculacion Universidad Sociedad</div>
        <div class="sub">Constancia de finalizacion de educacion no formal</div>
    </div>

    <div class="number">{{ data_get($snapshot, 'constancia.numero') }}</div>

    <h1>Constancia de Finalizacion</h1>

    <div class="content">
        La Direccion de Vinculacion Universidad Sociedad hace constar que la accion de educacion no formal
        <strong>{{ data_get($snapshot, 'accion.nombre') }}</strong>, identificada como
        <strong>{{ data_get($snapshot, 'accion.codigo_formulario') }}</strong>, completo satisfactoriamente el flujo
        de cierre y su informe final fue aprobado.
    </div>

    <div class="box">
        <div class="row"><span class="label">Numero de registro:</span> {{ data_get($snapshot, 'accion.numero_registro') }}</div>
        <div class="row"><span class="label">Tipo de accion:</span> {{ data_get($snapshot, 'accion.tipo') }}</div>
        <div class="row"><span class="label">Unidad academica:</span> {{ data_get($snapshot, 'accion.unidad_academica') }}</div>
        <div class="row"><span class="label">Periodo de ejecucion:</span> {{ data_get($snapshot, 'accion.fecha_inicio') }} al {{ data_get($snapshot, 'accion.fecha_fin') }}</div>
        <div class="row"><span class="label">Fecha de aprobacion del informe final:</span> {{ data_get($snapshot, 'informe.fecha_aprobacion') }}</div>
        <div class="row"><span class="label">Responsable:</span> {{ data_get($snapshot, 'responsable.nombre') }}</div>
    </div>

    <p>
        Se extiende la presente constancia en {{ data_get($snapshot, 'constancia.ciudad_emision') }},
        a los {{ \Carbon\Carbon::parse(data_get($snapshot, 'constancia.fecha_emision'))->translatedFormat('d') }}
        dias del mes de {{ \Carbon\Carbon::parse(data_get($snapshot, 'constancia.fecha_emision'))->translatedFormat('F') }}
        de {{ \Carbon\Carbon::parse(data_get($snapshot, 'constancia.fecha_emision'))->translatedFormat('Y') }}.
    </p>

    <div class="signature">
        @if(filled($firma) || filled($sello))
            <div class="signature-assets">
                @if(filled($firma))
                    <img class="signature-firma" src="{{ $firma }}" alt="Firma">
                @endif
                @if(filled($sello))
                    <img class="signature-sello" src="{{ $sello }}" alt="Sello">
                @endif
            </div>
        @endif
        <div class="signature-line">
            <strong>{{ data_get($snapshot, 'autoridad.nombre') }}</strong><br>
            {{ data_get($snapshot, 'autoridad.cargo') }}<br>
            VRA-UNAH
        </div>
    </div>

    <div class="qr">
        <img src="{{ $qr }}" alt="Codigo QR" width="86" height="86">
        <div>Codigo: {{ data_get($snapshot, 'constancia.codigo_validacion') }}</div>
    </div>

    <div class="footer">{{ $verificationUrl }}</div>
</body>
</html>
