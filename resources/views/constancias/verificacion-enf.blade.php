<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
    <style>
        body { background: #f8fafc; color: #111827; font-family: Arial, Helvetica, sans-serif; margin: 0; }
        main { margin: 0 auto; max-width: 720px; padding: 48px 20px; }
        article { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, .1); padding: 28px; }
        .status { color: #047857; font-size: 13px; font-weight: bold; letter-spacing: .06em; margin: 0; text-transform: uppercase; }
        h1 { font-size: 24px; margin: 10px 0 24px; }
        dl { display: table; font-size: 14px; margin: 0; width: 100%; }
        dl div { display: table-row; }
        dt, dd { display: table-cell; padding: 8px 12px 8px 0; vertical-align: top; }
        dt { color: #4b5563; font-weight: bold; width: 34%; }
        dd { margin: 0; }
    </style>
</head>
<body>
    <main>
        <article>
            <p class="status">Constancia vigente</p>
            <h1>{{ $titulo }}</h1>
            <dl>
                <div><dt>Numero</dt><dd><strong>{{ $datos['numero'] }}</strong></dd></div>
                <div><dt>Tipo</dt><dd><strong>{{ $datos['tipo'] }}</strong></dd></div>
                <div><dt>Accion ENF</dt><dd>{{ $datos['accion'] }}</dd></div>
                <div><dt>Codigo</dt><dd>{{ $datos['codigo'] }}</dd></div>
                <div><dt>Unidad academica</dt><dd>{{ $datos['unidad'] }}</dd></div>
                <div><dt>Fecha de emision</dt><dd>{{ $datos['fecha_emision'] }}</dd></div>
            </dl>
        </article>
    </main>
</body>
</html>
