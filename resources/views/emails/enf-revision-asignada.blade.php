<!doctype html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Revisión ENF pendiente</h2>
    <p>Se ha registrado una acción de Educación No Formal que requiere revisión.</p>
    <p><strong>Acción:</strong> {{ $accion->nombre_accion }}</p>
    <p><strong>Etapa:</strong> {{ $revision->etapa_nombre }}</p>
    <p><strong>Estado:</strong> {{ str_replace('_', ' ', $revision->estado) }}</p>
    <p>
        <a href="{{ $url }}" style="display:inline-block;background:#1d4ed8;color:#ffffff;padding:10px 14px;text-decoration:none;border-radius:6px;">
            Ver registro
        </a>
    </p>
</body>
</html>
