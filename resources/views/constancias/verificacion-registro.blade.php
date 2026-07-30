<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de Constancia de Registro</title>
    <style>
        body { background: #f8fafc; color: #111827; font-family: Arial, Helvetica, sans-serif; margin: 0; }
        main { margin: 0 auto; max-width: 720px; padding: 48px 20px; }
        article { background: {{ $vigente ? '#ecfdf5' : '#fff1f2' }}; border: 1px solid {{ $vigente ? '#a7f3d0' : '#fecdd3' }}; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, .1); padding: 28px; }
        .status { color: {{ $vigente ? '#047857' : '#be123c' }}; font-size: 13px; font-weight: bold; letter-spacing: .06em; margin: 0; text-transform: uppercase; }
        h1 { font-size: 24px; margin: 10px 0 24px; }
        dl { display: table; font-size: 14px; margin: 0; width: 100%; }
        dl div { display: table-row; }
        dt, dd { display: table-cell; padding: 8px 12px 8px 0; vertical-align: top; }
        dt { color: #4b5563; font-weight: bold; width: 34%; }
        dd { margin: 0; }
        .download { background: #075985; border-radius: 6px; color: #fff; display: inline-block; font-size: 14px; font-weight: bold; margin-top: 24px; padding: 10px 16px; text-decoration: none; }
        .notice { color: #be123c; font-size: 14px; font-weight: bold; margin: 24px 0 0; }
    </style>
</head>
<body>
    <main>
        <article>
            <p class="status">{{ $vigente ? 'Constancia vigente' : 'Constancia no vigente' }}</p>
            <h1>Verificación de Constancia de Registro</h1>
            <dl>
                <div><dt>Número</dt><dd><strong>{{ $datos['numero'] }}</strong></dd></div>
                <div><dt>Tipo</dt><dd><strong>{{ $datos['tipo'] }}</strong></dd></div>
                <div><dt>Proyecto</dt><dd>{{ $datos['proyecto'] }}</dd></div>
                <div><dt>Código</dt><dd>{{ $datos['codigo'] }}</dd></div>
                <div><dt>Unidad académica</dt><dd>{{ $datos['unidad'] }}</dd></div>
                <div><dt>Coordinador</dt><dd>{{ $datos['coordinador'] }}</dd></div>
                <div><dt>Fecha de emisión</dt><dd>{{ $datos['fecha_emision'] }}</dd></div>
            </dl>
            @if($puedeDescargarPublicamente)
                <a href="{{ route('constancias.registro.verificar.pdf', ['token' => $token]) }}" class="download">Descargar constancia vigente</a>
            @elseif(! $vigente)
                <p class="notice">El documento no está publicado o ya no se encuentra vigente; no está disponible para descarga.</p>
            @endif
        </article>
    </main>
</body>
</html>
