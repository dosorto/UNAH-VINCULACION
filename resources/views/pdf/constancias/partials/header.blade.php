<header class="constancia-header">
    <img class="constancia-header-brand" src="{{ $header }}" alt="Universidad Nacional Autónoma de Honduras, Vicerrectoría Académica y Dirección de Vinculación Universidad-Sociedad">
    <div class="constancia-contacto">Dirección de Vinculación Universidad-Sociedad<br>{{ $institucional['correo'] }}<br>Tel. {{ $institucional['telefono'] }}</div>
    <img class="constancia-qr" src="{{ $qr }}" alt="Código QR de verificación">
    <div class="constancia-accent" aria-hidden="true"></div>
    <div class="constancia-identificacion"><strong>{{ data_get($snapshot, 'constancia.numero') }}</strong><span>Fecha de emisión: {{ $fechaEmision->translatedFormat('d/m/Y') }} · Código: {{ data_get($snapshot, 'constancia.codigo_validacion') }}</span></div>
</header>
