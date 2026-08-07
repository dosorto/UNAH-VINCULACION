<!doctype html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Revisión DAFT pendiente</h2>
    <p>Se le ha asignado un programa que requiere revisión.</p>
    <p><strong>Programa:</strong> {{ $programa?->nombre }}</p>
    <p><strong>Etapa:</strong> {{ $revision->etapa_nombre }}</p>
    <p>
        <a href="{{ $url }}" style="display:inline-block;background:#1d4ed8;color:#ffffff;padding:10px 14px;text-decoration:none;border-radius:6px;">
            Ver programa
        </a>
    </p>
</body>
</html>
